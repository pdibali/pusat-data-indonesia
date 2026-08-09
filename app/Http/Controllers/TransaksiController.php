<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    // ── Checkout: Buat transaksi & ambil Snap Token ────────────
    public function checkout(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk berlangganan.');
        }

        $request->validate(['layanan_id' => 'required|exists:layanan,layanan_id']);

        $user    = Auth::user();
        $layanan = Layanan::where('layanan_id', $request->layanan_id)->where('status', 'publish')->firstOrFail();

        $existing = Transaksi::where('user_id', $user->user_id)
                            ->where('layanan_id', $layanan->layanan_id)
                            ->where('status', 'success')
                            ->where(function ($q) {
                                $q->whereNull('aktif_sampai')->orWhere('aktif_sampai', '>=', now());
                            })->first();

        if ($existing) {
            return redirect()->route('transaksi.riwayat')->with('error', 'Kamu sudah memiliki langganan aktif untuk layanan ini.');
        }

        Transaksi::where('user_id', $user->user_id)
            ->whereIn('status', ['pending', 'menunggu_verifikasi'])
            ->update(['status' => 'cancelled']);

        $transaksi = Transaksi::create([
            'user_id'      => $user->user_id,
            'layanan_id'   => $layanan->layanan_id,
            'nama_layanan' => $layanan->nama_layanan,
            'harga'        => $layanan->harga,
            'durasi'       => $layanan->durasi,
            'durasi_type'  => $layanan->durasi_type,
            'order_id'     => Transaksi::generateOrderId($user->user_id),
            'status'       => 'pending',
        ]);

        return redirect()->route('transaksi.pilih_metode', $transaksi);
    }

    public function pilihMetode(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) abort(403);
        if ($transaksi->status !== 'pending') return redirect()->route('transaksi.riwayat');

        $transaksi->load('layanan');
        return view('pages.transaksi.pilih-metode', compact('transaksi'));
    }

    public function bayarMidtrans(Transaksi $transaksi)
    {
        if (!\App\Support\Setting::get('midtrans_enabled', true)) {
            return back()->with('error', 'Fitur pembayaran Midtrans belum tersedia.');
        }
        
        if ($transaksi->user_id !== Auth::id()) abort(403);
        if ($transaksi->status !== 'pending') return redirect()->route('transaksi.riwayat');

        $layanan = $transaksi->layanan;
        $snapToken = $this->getSnapToken($transaksi, Auth::user(), $layanan);

        if (! $snapToken) {
            return back()->with('error', 'Gagal menghubungi payment gateway. Coba lagi.');
        }

        $transaksi->update(['snap_token' => $snapToken, 'metode_pembayaran' => 'midtrans']);

        return view('pages.transaksi.checkout', compact('transaksi', 'layanan', 'snapToken'));
    }

    public function pilihManual(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) abort(403);
        if ($transaksi->status !== 'pending') return redirect()->route('transaksi.riwayat');

        $transaksi->update(['metode_pembayaran' => 'manual']);

        return redirect()->route('transaksi.manual.form', $transaksi);
    }

    // ── Form upload bukti transfer manual ──────────────────────
    public function manualForm(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) abort(403);
        if (! $transaksi->isManual()) abort(404);
        if ($transaksi->status !== 'pending') {
            return redirect()->route('transaksi.riwayat');
        }

        $transaksi->load('layanan');
        return view('pages.transaksi.manual-upload', compact('transaksi'));
    }

    // ── Simpan bukti transfer yang diupload user ────────────────
    public function uploadBukti(Request $request, Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) abort(403);

        if (! $transaksi->isManual() || $transaksi->status !== 'pending') {
            return back()->with('error', 'Transaksi ini tidak valid untuk upload bukti transfer.');
        }

        $request->validate([
            'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'nama_pengirim'  => 'required|string|max:255',
            'bank_pengirim'  => 'required|string|max:100',
        ]);

        $path = $request->file('bukti_transfer')->store('bukti-transfer', 'railway');

        $transaksi->update([
            'bukti_transfer' => $path,
            'nama_pengirim'  => $request->nama_pengirim,
            'bank_pengirim'  => $request->bank_pengirim,
            'status'         => 'menunggu_verifikasi',
        ]);

        return redirect()->route('transaksi.riwayat')
            ->with('success', 'Bukti transfer berhasil dikirim. Menunggu verifikasi admin (maks 1x24 jam).');
    }

    // ── Bayar Ulang: Ambil Snap Token baru untuk transaksi pending ────
    public function bayarUlang(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) {
            abort(403);
        }

        if ($transaksi->status !== 'pending') {
            return response()->json([
                'message' => 'Transaksi ini sudah tidak berstatus menunggu pembayaran.',
            ], 422);
        }

        // Guard: tolak retry kalau created_at sudah lewat window EXPIRY_HOURS,
        // walaupun status di DB masih 'pending' (command jam-an belum sempat menyapunya).
        // Ini yang mencegah transaksi lama tetap bisa dibayar dari riwayat.
        if ($transaksi->isExpired()) {
            $transaksi->update(['status' => 'expired']);

            return response()->json([
                'message' => 'Transaksi ini sudah kedaluwarsa (lebih dari 24 jam). Silakan buat pesanan baru.',
            ], 422);
        }

        $layanan = Layanan::findOrFail($transaksi->layanan_id);
        $user    = Auth::user();

        // Snap token lama kemungkinan sudah kedaluwarsa di sisi Midtrans, minta token baru
        // dengan order_id yang sama — tapi window pembayarannya TETAP dihitung dari
        // created_at transaksi asli (lihat getSnapToken), bukan dari waktu retry ini.
        $snapToken = $this->getSnapToken($transaksi, $user, $layanan);

        if (! $snapToken) {
            return response()->json([
                'message' => 'Gagal menghubungi payment gateway. Coba lagi.',
            ], 500);
        }

        $transaksi->update(['snap_token' => $snapToken]);

        return response()->json(['snap_token' => $snapToken]);
    }

    // ── Notification: Webhook dari Midtrans ───────────────────
    public function notification(Request $request)
    {
        $payload = $request->all();

        Log::info('Midtrans Notification Received', [
            'order_id' => $payload['order_id'] ?? '',
            'transaction_status' => $payload['transaction_status'] ?? '',
            'payment_type' => $payload['payment_type'] ?? '',
        ]);

        $orderId      = trim((string) ($payload['order_id'] ?? ''));
        $statusCode   = trim((string) ($payload['status_code'] ?? ''));
        $grossAmount  = trim((string) ($payload['gross_amount'] ?? ''));
        $serverKey    = trim((string) config('midtrans.server_key'));
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payloadSignature = trim((string) ($payload['signature_key'] ?? ''));
        if ($signatureKey !== $payloadSignature) {
            Log::warning('Midtrans: Invalid Signature', [
                'order_id' => $orderId,
                'expected' => $signatureKey,
                'received' => $payloadSignature,
                'gross_amount' => $grossAmount,
                'status_code' => $statusCode,
                'raw_payload' => $payload,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaksi = Transaksi::where('order_id', $orderId)->first();

        if (! $transaksi) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transaksi->update(['midtrans_payload' => $payload]);

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus       = $payload['fraud_status'] ?? '';
        $paymentType       = $payload['payment_type'] ?? null;

        if ($transactionStatus === 'capture') {
            $newStatus = ($fraudStatus === 'accept') ? 'success' : 'failed';
        } elseif ($transactionStatus === 'settlement') {
            $newStatus = 'success';
        } elseif ($transactionStatus === 'deny') {
            $newStatus = 'failed';
        } elseif ($transactionStatus === 'expire') {
            // Selaraskan dengan status 'expired' kita sendiri (sama seperti yang
            // ditulis ExpirePendingTransaksi) — beda dari 'cancelled'.
            $newStatus = 'expired';
        } elseif ($transactionStatus === 'failure') {
            $newStatus = 'failed';
        } elseif ($transactionStatus === 'cancel') {
            $newStatus = 'cancelled';
        } elseif ($transactionStatus === 'pending') {
            $newStatus = 'pending';
        } else {
            $newStatus = $transaksi->status;
        }

        $updateData = [
            'status'                   => $newStatus,
            'payment_type'             => $paymentType,
            'midtrans_transaction_id'  => $payload['transaction_id'] ?? null,
        ];

        if ($newStatus === 'success' && ! $transaksi->isSuccess()) {
            $mulai = Carbon::now();
            $updateData['aktif_mulai']  = $mulai;
            $updateData['aktif_sampai'] = Transaksi::hitungAktifSampai(
                $transaksi->durasi_type,
                $transaksi->durasi,
                $mulai
            );
        }

        $transaksi->update($updateData);

        Log::info('Midtrans: Transaction Updated Successfully', [
            'order_id' => $orderId,
            'old_status' => $transaksi->status,
            'new_status' => $newStatus,
            'transaction_id' => $payload['transaction_id'] ?? null,
        ]);

        return response()->json(['message' => 'OK']);
    }

    // ── Status: Cek status transaksi (AJAX polling) ────────────
    public function status(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) {
            abort(403);
        }

        // Jaring pengaman on-the-fly, jaga-jaga command jam-an belum sempat jalan.
        // Cukup panggil isExpired() (bukan cek status==='pending' manual) karena
        // isExpired() sudah otomatis false begitu status sudah literal 'expired'.
        if ($transaksi->status === 'pending' && $transaksi->isExpired()) {
            $transaksi->update(['status' => 'expired']);
        }

        return response()->json([
            'status'       => $transaksi->status,
            'status_label' => match($transaksi->status) {
                'success'   => 'Pembayaran Berhasil',
                'pending'   => 'Menunggu Pembayaran',
                'failed'    => 'Pembayaran Gagal',
                'expired'   => 'Kedaluwarsa',
                'cancelled' => 'Dibatalkan',
                default     => $transaksi->status,
            },
        ]);
    }

    // ── Riwayat: Daftar transaksi milik user yang login ────────
    public function riwayat(Request $request)
    {
        $user = Auth::user();

        // Sapu bersih transaksi pending milik user ini yang sudah lewat window
        // sebelum ditampilkan — jaring pengaman kalau command jam-an sempat telat.
        Transaksi::where('user_id', $user->user_id)
            ->expiredPending()
            ->update(['status' => 'expired']);

        $query = Transaksi::where('user_id', $user->user_id)
                          ->with('layanan')
                          ->whereIn('status', ['pending', 'success', 'failed', 'expired', 'cancelled'])
                          ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transaksis = $query->paginate(10)->withQueryString();

        $stats = [
            'total'    => Transaksi::where('user_id', $user->user_id)->count(),
            'aktif'    => Transaksi::where('user_id', $user->user_id)->success()
                            ->where(function ($q) {
                                $q->whereNull('aktif_sampai')
                                  ->orWhere('aktif_sampai', '>=', now());
                            })->count(),
            'pending'  => Transaksi::where('user_id', $user->user_id)->pending()->count(),
            'total_bayar' => Transaksi::where('user_id', $user->user_id)
                                ->success()->sum('harga'),
        ];

        return view('pages.transaksi.riwayat', compact('transaksis', 'stats'));
    }

    // ── Detail: Detail satu transaksi milik user ───────────────
    public function detail(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) {
            abort(403);
        }

        $transaksi->load('layanan');

        return view('pages.transaksi.detail', compact('transaksi'));
    }

    // ── Private: Request Snap Token ke Midtrans ────────────────
    private function getSnapToken(Transaksi $transaksi, $user, Layanan $layanan): ?string
    {
        $serverKey = config('midtrans.server_key');
        $isProduction = config('midtrans.is_production');

        $snapUrl = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id'     => $transaksi->order_id,
                'gross_amount' => (int) $transaksi->harga,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
            ],
            'item_details' => [
                [
                    'id'       => 'LAYANAN-' . $layanan->layanan_id,
                    'price'    => (int) $layanan->harga,
                    'quantity' => 1,
                    'name'     => $layanan->nama_layanan . ' (' . $layanan->durasi_label . ')',
                ],
            ],
            'callbacks' => [
                'finish' => route('transaksi.riwayat'),
            ],
            // Kunci window pembayaran ke created_at transaksi, BUKAN ke waktu request ini.
            // Ini yang membuat countdown di Snap popup tidak "restart" walau user
            // klik "Bayar" berkali-kali dari halaman riwayat — durasinya sama persis
            // dengan yang dipakai Transaksi::EXPIRY_HOURS di seluruh aplikasi.
            'expiry' => [
                'start_time' => $transaksi->created_at->format('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => Transaksi::EXPIRY_HOURS,
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->timeout(30)
                ->post($snapUrl, $payload);

            if ($response->successful()) {
                return $response->json('token');
            }

            Log::error('Midtrans Snap Error', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Midtrans HTTP Exception: ' . $e->getMessage());
            return null;
        }
    }

    // ── Debug: Test Webhook Endpoint ──────────────────────────
    public function webhookTest(Request $request)
    {
        $payload = $request->all();
        $jsonPayload = $request->json()->all();
        $rawBody = $request->getContent();

        Log::debug('Webhook Test Received', [
            'payload' => $payload,
            'json_payload' => $jsonPayload,
            'raw_body' => $rawBody,
            'headers' => $request->headers->all(),
        ]);

        return response()->json([
            'message' => 'Webhook received',
            'payload' => $payload,
            'json_payload' => $jsonPayload,
            'raw_body' => $rawBody,
            'timestamp' => now(),
            'log_file' => storage_path('logs/laravel.log'),
        ]);
    }

    public function sukses(Transaksi $transaksi)
    {
        if ($transaksi->user_id !== Auth::id()) abort(403);
        if (! $transaksi->isSuccess()) return redirect()->route('transaksi.riwayat');

        $transaksi->load('layanan');

        return view('pages.transaksi.sukses', compact('transaksi'));
    }

    // ── Debug: Generate Test Signature ────────────────────────
    public function signatureTest(Request $request)
    {
        $orderId = $request->get('order_id', 'TEST001');
        $statusCode = $request->get('status_code', '200');
        $grossAmount = (int) $request->get('gross_amount', 10000);

        $serverKey = config('midtrans.server_key');
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return response()->json([
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'server_key' => substr($serverKey ?? '', 0, 10) . '...',
            'signature_key' => $signatureKey,
            'raw_string' => $orderId . $statusCode . $grossAmount . $serverKey,
            'test_curl' => "curl -X POST https://yourdomain.com/transaksi/webhook-test \\
  -H 'Content-Type: application/json' \\
  -d '{\"order_id\":\"$orderId\",\"status_code\":\"$statusCode\",\"gross_amount\":$grossAmount,\"signature_key\":\"$signatureKey\",\"transaction_status\":\"settlement\",\"transaction_id\":\"test-123\",\"payment_type\":\"bank_transfer\"}'",
        ]);
    }
}