<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Layanan;
use App\Models\Klasifikasi;
use App\Models\Metadata;
use App\Models\Data;
use App\Models\ProdusenData;
use App\Services\SubscriptionAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SearchExpansionService;

class LandingController extends Controller
{
    /** Daftar semua klasifikasi yang diakui sistem */
    private $allKlasifikasi;
    private SearchExpansionService $expansion;

    public function __construct(SearchExpansionService $expansion)
    {
        $this->expansion = $expansion;
        $this->allKlasifikasi = Klasifikasi::query()
            ->whereHas('metadata', function ($q) {
                $q->where('status', 2)
                ->whereHas('data', fn($qd) => $qd->where('status', 1)->where('location_id', 0));
            })
            ->orderBy('nama_klasifikasi')
            ->pluck('nama_klasifikasi')
            ->map(fn ($k) => trim((string) $k))
            ->filter(function ($k) {
                return $k !== ''
                    && $k !== '-'
                    && Str::slug($k) !== '';
            })
            ->values();
    }

    /**
     * Build kondisi WHERE ...LIKE... dari hasil expand kata (stem + sinonim),
     * broaden pakai OR supaya satu kata yang tidak match tidak menggagalkan seluruh baris.
     */
    private function applyExpandedSearch($queryBuilder, string $column, array $expandedKeywords): void
    {
        $queryBuilder->where(function ($outer) use ($column, $expandedKeywords) {
            foreach ($expandedKeywords as $group) {
                $terms = array_filter(array_merge(
                    [$group['original'], $group['stemmed']],
                    $group['sinonim']
                ));

                $outer->orWhere(function ($inner) use ($column, $terms) {
                    foreach ($terms as $t) {
                        $inner->orWhere($column, 'like', "%{$t}%");
                    }
                });
            }
        });
    }

    /**
     * Hitung skor tambahan berdasarkan expanded keywords.
     * Match kata asli dibobot lebih tinggi daripada match via stem/sinonim.
     */
    private function scoreExpandedMatch(string $nama, array $expandedKeywords): int
    {
        $nama = strtolower($nama);
        $score = 0;

        foreach ($expandedKeywords as $group) {
            if (str_contains($nama, $group['original'])) {
                $score += 50; // match kata asli — bobot penuh
                continue;
            }
            if ($group['stemmed'] && str_contains($nama, $group['stemmed'])) {
                $score += 35; // match kata dasar hasil stem
                continue;
            }
            foreach ($group['sinonim'] as $s) {
                if (str_contains($nama, $s)) {
                    $score += 20; // match via sinonim — bobot paling rendah
                    break;
                }
            }
        }

        return $score;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LANDING PAGE
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        // Klasifikasi yang benar-benar ada data-nya (maks 10 untuk badges di hero)
        $klasifikasiAktif = Klasifikasi::whereHas('metadata', function ($q) {
            $q->where('status', 2)
            ->whereHas('data', fn($qd) => $qd->where('status', 1)->where('location_id', 0));
        })
        ->orderBy('nama_klasifikasi')
        ->take(10)
        ->get();

        // Statistik
        $jumlahData     = Data::where('status', 1)->count();
        $jumlahMetadata = Metadata::where('status', 2)->count();
        $jumlahProdusen = ProdusenData::count();

        // Produk unggulan: hanya metadata yang benar-benar punya data
        // Produk unggulan: hanya metadata yang is_free = 1
        $produkUnggulan = Metadata::query()
            ->where('status', 2)
            ->where('is_free', 1)  // ← ganti ini
            ->whereHas('data', function ($q) {
                $q->where('status', 1)->where('location_id', 0);
            })
            ->with([
                'klasifikasi',
                'data' => function ($q) {
                    $q->where('status', 1)->where('location_id', 0)->with('location', 'rujukan.produsen')->limit(1);
                }
            ])
            ->orderBy('date_inputed', 'desc')
            ->orderBy('metadata_id', 'desc')
            ->limit(3)
            ->get([
                'metadata_id',
                'nama',
                'klasifikasi_id',
                'satuan_data',
                'frekuensi_penerbitan',
                'tahun_mulai_data'
            ]);

        // ── Rentang tahun untuk produk unggulan ────────────────────────────────
        $unggulanIds = $produkUnggulan->pluck('metadata_id')->all();

        $yearRangesUnggulan = DB::table('data')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->whereIn('data.metadata_id', $unggulanIds)
            ->where('data.status', 1)
            ->whereNotNull('data.number_value')
            ->groupBy('data.metadata_id')
            ->selectRaw('data.metadata_id, MIN(time.year) as min_year, MAX(time.year) as max_year')
            ->get()
            ->keyBy('metadata_id')
            ->map(fn($r) => $r->min_year . '-' . $r->max_year);
            
        // Paket layanan untuk section pricing di landing
        $pricings = Layanan::with('fiturs')
            ->where('status', 'publish')
            ->orderBy('urutan')
            ->orderBy('layanan_id')
            ->get();

        return view('pages.landing.index', compact(
            'klasifikasiAktif',
            'jumlahData',
            'jumlahMetadata',
            'jumlahProdusen',
            'produkUnggulan',
            'pricings',
            'yearRangesUnggulan',   // ← baru
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN LANGGANAN
    // ─────────────────────────────────────────────────────────────────────────

    public function langganan()
    {
        $layanans = Layanan::where('status', 'publish')
            ->with(['fiturs' => fn($q) => $q->orderBy('urutan')])
            ->orderBy('urutan')
            ->orderBy('harga')
            ->get();

        return view('pages.landing.langganan', compact('layanans'));
    }

    public function bantuan()
    {
        $topics = [
            [
                'title' => 'Mencari Data',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" /></svg>',
                'intro' => 'Gunakan fitur pencarian untuk menemukan data statistik yang Anda butuhkan dengan cepat.',
                'steps' => [
                    'Klik kolom pencarian.',
                    'Ketikkan kata kunci, misalnya nama topik data atau klasifikasi data.',
                    'Pilih hasil pada daftar saran otomatis, atau tekan <b>Enter</b> untuk melihat semua hasil.',
                ],
                'tip' => 'Gunakan kata kunci yang spesifik agar hasil pencarian lebih akurat.',
            ],
            [
                'title' => 'Template Tampilan Data',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>',
                'intro' => 'Template Tampilan Data memungkinkan Anda menyusun beberapa data sekaligus ke dalam satu tabel pivot yang bisa disesuaikan rentang waktunya, lalu diekspor kapan saja tanpa perlu mengulang filter dari awal.',
                'steps' => [
                    'Buka menu <b>Produk → Template Tampilan Data</b> dari navigasi utama.',
                    'Klik <b>Buat Template</b>, lalu pilih salah satu jenis template: <b>Metadata</b> (pilih data secara manual satu per satu), <b>Klasifikasi</b> (ambil semua data dalam satu klasifikasi topik), atau <b>Wilayah</b> (ambil semua data yang tersedia untuk wilayah tertentu).',
                    'Pilih data dan/atau wilayah sesuai kebutuhan, lalu beri nama template dan simpan.',
                    'Setelah tersimpan, Anda akan diarahkan ke halaman <b>Template Tampilan Data</b>. Pilih template yang baru dibuat pada <b>Langkah 1: Pilih Template</b>.',
                    'Pilih <b>Frekuensi Rentang Waktu</b> (Tahunan, Semesteran, Kuartal, Bulanan, dsb) sesuai frekuensi data yang tersedia — frekuensi yang tidak memiliki data akan otomatis dinonaktifkan.',
                    'Tentukan <b>Rentang Periode</b> (misalnya dari tahun 2020 sampai 2024), lalu klik <b>Tampilkan Data</b> untuk melihat hasilnya dalam bentuk tabel.',
                    'Gunakan tombol <b>Export</b> di atas tabel untuk mengunduh hasil dalam format Excel, PDF, atau JSON.',
                ],
                'tip' => 'Template yang Anda buat tanpa login akan tersimpan sementara dan bisa hilang jika cache dibersihkan. Login terlebih dahulu agar template tersimpan permanen di akun Anda dan bisa diakses dari perangkat lain.',
            ],
            [
                'title' => 'Melihat Detail Data',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4m0 0l-4-4m4 4l-4 4M3 7h4v4H3V7z" /></svg>',
                'intro' => 'Setiap data memiliki halaman detail berisi tabel, grafik, dan metadata lengkap.',
                'steps' => [
                    'Buka menu <b>Data Series</b>.',
                    'Klik data yang ingin dilihat untuk membuka halaman detail dari data yang dipilih.',
                    'Tabel dan grafik akan menampilkan data berdasarkan rentang tahun yang tersedia.',
                ],
                'tip' => 'Beberapa data memerlukan langganan aktif untuk dapat diakses secara penuh.',
            ],
            [
                'title' => 'Klasifikasi Data',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" /></svg>',
                'intro' => 'Data dikelompokkan berdasarkan klasifikasi agar lebih mudah dijelajahi.',
                'steps' => [
                    'Buka menu <b>Klasifikasi</b> dari navigasi utama.',
                    'Pilih salah satu klasifikasi untuk melihat daftar data di dalamnya.',
                    'Pilih data yang ingin dilihat.',
                ],
                'tip' => null,
            ],
            [
                'title' => 'Langganan & Akses Premium',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
                'intro' => 'Sebagian data bersifat premium dan membutuhkan langganan aktif untuk diakses.',
                'steps' => [
                    'Buka menu <b>Langganan</b> untuk melihat paket yang tersedia.',
                    'Pilih paket sesuai kebutuhan, lalu lakukan pembayaran.',
                    'Setelah pembayaran berhasil, akses data premium akan otomatis terbuka.',
                ],
                'tip' => 'Anda bisa memeriksa status transaksi di halaman Riwayat Transaksi setelah login.',
            ],
            [
                'title' => 'Akun & Login',
                'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>',
                'intro' => 'Buat akun untuk mengakses fitur langganan dan menyimpan preferensi Anda.',
                'steps' => [
                    'Klik tombol <b>Daftar</b> pada navigasi untuk membuat akun baru.',
                    'Isi formulir pendaftaran dan verifikasi email Anda melalui tautan yang dikirimkan.',
                    'Setelah terverifikasi, login menggunakan email dan kata sandi Anda.',
                    'Jika lupa kata sandi, gunakan fitur <b>Lupa Kata Sandi</b> di halaman login.',
                ],
                'tip' => 'Periksa folder spam jika email verifikasi tidak muncul di kotak masuk.',
            ],
        ];

        return view('pages.landing.bantuan', compact('topics'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN DAFTAR KLASIFIKASI
    // ─────────────────────────────────────────────────────────────────────────

    public function klasifikasiIndex()
    {
        $counts = Metadata::with('klasifikasi')
            ->where('status', 2)
            ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0))
            ->get()
            ->groupBy(fn ($m) => $m->klasifikasi?->nama_klasifikasi)
            ->map(fn ($items) => $items->count());

        $klasifikasiList = Klasifikasi::whereHas('metadata', function ($q) {
                $q->where('status', 2)
                  ->whereHas('data', fn($qd) => $qd->where('status', 1)->where('location_id', 0));
            })
            ->orderBy('nama_klasifikasi')
            ->get(['nama_klasifikasi', 'icon'])
            ->map(function ($item) use ($counts) {
                $slug = Str::slug($item->nama_klasifikasi);
                if (!$slug) return null;
                return [
                    'nama'  => $item->nama_klasifikasi,
                    'slug'  => $slug,
                    'total' => $counts->get($item->nama_klasifikasi, 0),
                    'icon'  => $item->icon,
                ];
            })
            ->filter()
            ->values();

        return view('pages.landing.klasifikasi.index', compact('klasifikasiList'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN DETAIL KLASIFIKASI → daftar metadata
    // ─────────────────────────────────────────────────────────────────────────

    public function klasifikasiShow(string $klasifikasi)
    {
        $nama = Klasifikasi::orderBy('nama_klasifikasi')
            ->pluck('nama_klasifikasi')
            ->map(fn($k) => trim((string) $k))
            ->filter(fn($k) => $k !== '' && $k !== '-' && Str::slug($k) !== '')
            ->first(fn($k) => Str::slug($k) === $klasifikasi);

        abort_if(is_null($nama), 404);

        $isLimited   = $this->isLimitedUser();
        $freeIds     = $this->getFreeIds();
        $maxFreeBait = 2; // maksimal data gratis yang ditampilkan sebagai "pemancing"

        // ── Data gratis yang benar-benar milik klasifikasi ini ──────────────
        $freeIdsInKlasifikasi = [];
        if ($isLimited && !empty($freeIds)) {
            $freeIdsInKlasifikasi = Metadata::where('status', 2)
                ->where('is_free', 1)
                ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0))
                ->whereHas('klasifikasi', fn($q) => $q->where('nama_klasifikasi', $nama))
                ->orderBy('nama')
                ->pluck('metadata_id')
                ->all();
        }

        $recommendedFree = collect();
        $allowedFreeIds  = [];

        if ($isLimited) {
            if (!empty($freeIdsInKlasifikasi)) {
                // Ada data gratis di klasifikasi ini → batasi maksimal $maxFreeBait sebagai pemancing
                $allowedFreeIds = array_slice($freeIdsInKlasifikasi, 0, $maxFreeBait);
            } elseif (!empty($freeIds)) {
                // Tidak ada data gratis di klasifikasi ini → pinjam dari klasifikasi lain
                $recommendedFree = Metadata::with([
                        'klasifikasi',
                        'data' => fn($q) => $q->where('status', 1)
                            ->where('location_id', 0)
                            ->with('rujukan.produsen')
                            ->limit(1),
                    ])
                    ->where('status', 2)
                    ->where('is_free', 1)
                    ->whereIn('metadata_id', $freeIds)
                    ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0))
                    ->inRandomOrder()
                    ->limit($maxFreeBait)
                    ->get();

                $allowedFreeIds = $recommendedFree->pluck('metadata_id')->all();
            }
        }

        $query = Metadata::with(['klasifikasi', 'produsen', 'data' => function ($q) {
                $q->where('status', 1)
                ->where('location_id', 0)
                ->with('rujukan.produsen')
                ->limit(1);
            },])
            ->where('status', 2)
            ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0))
            ->whereHas('klasifikasi', function ($q) use ($nama) {
                $q->where('nama_klasifikasi', $nama);
            });

        // Pemancing (maks 2) ditaruh di atas jika limited
        if ($isLimited && !empty($allowedFreeIds)) {
            $ids = implode(',', array_map('intval', $allowedFreeIds));
            $query->orderByRaw("FIELD(metadata_id, {$ids}) = 0 ASC")
                ->orderByRaw("FIELD(metadata_id, {$ids}) ASC");
        }

        $query->orderBy('nama');

        $metadataList = $query->paginate(10);

        return view('pages.landing.klasifikasi.show', compact(
            'nama', 'metadataList', 'freeIds', 'isLimited', 'allowedFreeIds', 'recommendedFree'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: autocomplete / search metadata
    // - Mendukung filter per klasifikasi via ?klasifikasi=
    // - Mencari di seluruh DB, bukan hanya halaman saat ini
    // ─────────────────────────────────────────────────────────────────────────

    public function searchMetadata(Request $request)
    {
        $q           = trim($request->input('q', ''));
        $klasifikasi = trim($request->input('klasifikasi', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $isLimited = $this->isLimitedUser();
        $freeIds   = $this->getFreeIds();

        $keywords = collect(explode(' ', $q))
            ->map(fn($k) => trim(strtolower($k)))
            ->filter(fn($k) => strlen($k) >= 2)
            ->values();

        // ── BARU: expand kata → stem + sinonim ──────────────────────
        $expandedKeywords = $this->expansion->expand($keywords->all());

        $results = Metadata::where('status', 2)
            ->whereHas('data', fn($query) => $query->where('status', 1)->where('location_id', 0))
            ->where(function ($qb) use ($q, $expandedKeywords) {
                $qb->where('nama', 'like', "%{$q}%")
                    ->orWhere(function ($inner) use ($expandedKeywords) {
                        $this->applyExpandedSearch($inner, 'nama', $expandedKeywords);
                    });
            })
            ->when($klasifikasi, function ($query) use ($klasifikasi) {
                $query->whereHas('klasifikasi', fn($q) => $q->where('nama_klasifikasi', $klasifikasi));
            })
            ->with(['klasifikasi', 'produsen'])
            ->select('metadata_id', 'nama', 'klasifikasi_id', 'sumber_metadata_pertama as produsen_id', 'satuan_data', 'frekuensi_penerbitan', 'tahun_mulai_data', 'tag')
            ->limit(150) // naikkan dikit karena hasil sekarang lebih luas
            ->get();

        $qLower = strtolower($q);

        $scored = $results->map(function ($item) use ($q, $qLower, $expandedKeywords) {
            $nama  = strtolower($item->nama);
            $score = 0;

            if ($nama === $qLower)               $score += 1000;
            if (str_starts_with($nama, $qLower)) $score += 500;
            if (str_contains($nama, $qLower))    $score += 300;

            // ── BARU: skor dari hasil expand, gantikan blok "5. Hitung berapa kata..." lama
            $score += $this->scoreExpandedMatch($item->nama, $expandedKeywords);

            similar_text($qLower, $nama, $similarity);
            $score += (int) $similarity;
            $score -= (int) (strlen($nama) / 10);

            $item->_score = $score;
            return $item;
        });

        $sorted = $scored->sortByDesc('_score')->take(50)->values();

        return response()->json(
            $sorted->map(fn($item) => [
                'metadata_id'          => $item->metadata_id,
                'nama'                 => $item->nama,
                'klasifikasi'          => $item->klasifikasi?->nama_klasifikasi,
                'klasifikasi_slug'     => Str::slug($item->klasifikasi?->nama_klasifikasi),
                'satuan_data'          => $item->satuan_data,
                'frekuensi_penerbitan' => $item->frekuensi_penerbitan,
                'tahun_mulai_data'     => $item->tahun_mulai_data,
                'is_locked'            => $isLimited && !in_array($item->metadata_id, $freeIds),
                'produsen'             => $item->produsen?->nama ?? $item->produsen?->nama_produsen,
            ])
        );
    }

    public function autocomplete(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $keywords = collect(explode(' ', $q))
            ->map(fn($k) => trim(strtolower($k)))
            ->filter(fn($k) => strlen($k) >= 2)
            ->values();

        // ── BARU: expand kata → stem (Sastrawi) + sinonim ───────────────
        $expandedKeywords = $this->expansion->expand($keywords->all());

        $results = Metadata::where('status', 2)
            ->whereHas('data', fn($query) =>
                $query->where('status', 1)->where('location_id', 0)
            )
            ->where(function ($qb) use ($q, $expandedKeywords) {
                // exact phrase tetap dicoba dulu
                $qb->where('nama', 'like', "%{$q}%")
                    ->orWhere(function ($inner) use ($expandedKeywords) {
                        $this->applyExpandedSearch($inner, 'nama', $expandedKeywords);
                    });

                // field pendukung lain tetap dicek exact-phrase seperti semula
                $qb->orWhere('konsep',      'like', "%{$q}%")
                ->orWhere('definisi',    'like', "%{$q}%")
                ->orWhere('satuan_data', 'like', "%{$q}%")
                ->orWhere('tag',         'like', "%{$q}%");
            })
            ->select('metadata_id', 'nama', 'klasifikasi_id', 'konsep', 'definisi', 'satuan_data', 'tag')
            ->with('klasifikasi:klasifikasi_id,nama_klasifikasi')
            ->limit(120) // dinaikkan sedikit dari 80 karena hasil sekarang lebih luas
            ->get();

        $qLower = strtolower($q);

        $scored = $results->map(function ($item) use ($q, $qLower, $expandedKeywords) {
            $nama = strtolower($item->nama);

            // BARU: hitung sekali, dipakai untuk skor dan untuk deteksi found_in
            $expandedScore = $this->scoreExpandedMatch($item->nama, $expandedKeywords);

            $score = 0;
            if ($nama === $qLower)               $score += 1000;
            if (str_starts_with($nama, $qLower)) $score += 500;
            if (str_contains($nama, $qLower))    $score += 300;
            $score += $expandedScore;

            similar_text($qLower, $nama, $similarity);
            $score += (int) $similarity;
            $score -= (int) (strlen($nama) / 10);

            // Deteksi "ditemukan di" field mana — hanya jika nama sendiri (asli/stem/sinonim) tidak match
            $foundIn = null;
            if (!str_contains($nama, $qLower) && $expandedScore === 0) {
                if ($item->konsep       && str_contains(strtolower($item->konsep),      $qLower)) $foundIn = 'konsep';
                elseif ($item->definisi    && str_contains(strtolower($item->definisi),    $qLower)) $foundIn = 'definisi';
                elseif ($item->satuan_data && str_contains(strtolower($item->satuan_data), $qLower)) $foundIn = 'satuan data';
                elseif ($item->tag         && str_contains(strtolower($item->tag),         $qLower)) $foundIn = 'tag';
            }

            $item->_score    = $score;
            $item->_found_in = $foundIn;
            return $item;
        });

        $sorted = $scored->sortByDesc('_score')->take(8)->values();

        return response()->json(
            $sorted->map(fn($item) => [
                'label'       => $item->nama,
                'klasifikasi' => $item->klasifikasi?->nama_klasifikasi,
                'found_in'    => $item->_found_in,
                'q'           => $item->nama,
            ])
        );
    }

    public function searchResults(Request $request)
    {
        $q = trim($request->input('q', ''));

        $isLimited = $this->isLimitedUser();
        $freeIds   = $this->getFreeIds();

        if (strlen($q) < 2) {
            return view('pages.landing.search', [
                'q'          => $q,
                'sorted'     => collect(),
                'freeIds'    => $freeIds,
                'isLimited'  => $isLimited,
                'totalFound' => 0,
                'realFound'  => 0,
                'isFallback' => false,
            ]);
        }

        $keywords = collect(explode(' ', $q))
            ->map(fn($k) => trim(strtolower($k)))
            ->filter(fn($k) => strlen($k) >= 2)
            ->values();

        // ── BARU: expand kata → stem (Sastrawi) + sinonim ───────────────
        $expandedKeywords = $this->expansion->expand($keywords->all());

        $results = Metadata::where('status', 2)
            ->whereHas('data', fn($query) =>
                $query->where('status', 1)->where('location_id', 0)
            )
            ->where(function ($qb) use ($q, $expandedKeywords) {
                $qb->where('nama', 'like', "%{$q}%")
                    ->orWhere(function ($inner) use ($expandedKeywords) {
                        $this->applyExpandedSearch($inner, 'nama', $expandedKeywords);
                    });

                $qb->orWhere('konsep',      'like', "%{$q}%")
                ->orWhere('definisi',    'like', "%{$q}%")
                ->orWhere('satuan_data', 'like', "%{$q}%")
                ->orWhere('tag',         'like', "%{$q}%");
            })
            ->with([
                'klasifikasi:klasifikasi_id,nama_klasifikasi',
                'data' => fn($q) => $q->where('status', 1)
                    ->where('location_id', 0)
                    ->with('rujukan.produsen')
                    ->limit(1),
            ])
            ->select('metadata_id', 'nama', 'klasifikasi_id', 'satuan_data',
                    'frekuensi_penerbitan', 'tahun_mulai_data', 'is_free',
                    'konsep', 'definisi', 'tag')
            ->limit(250) // dinaikkan sedikit dari 200 karena hasil sekarang lebih luas
            ->get();

        $qLower = strtolower($q);

        $scored = $results->map(function ($item) use ($q, $qLower, $expandedKeywords, $freeIds, $isLimited) {
            $nama  = strtolower($item->nama);
            $score = 0;

            if ($nama === $qLower)               $score += 1000;
            if (str_starts_with($nama, $qLower)) $score += 500;
            if (str_contains($nama, $qLower))    $score += 300;

            // BARU: gantikan blok "4. Semua kata keyword ada di nama" + "5. Hitung berapa kata..." lama
            $score += $this->scoreExpandedMatch($item->nama, $expandedKeywords);

            similar_text($qLower, $nama, $similarity);
            $score += (int) $similarity;
            $score -= (int) (strlen($nama) / 10);

            if ($item->is_free) $score += 30;

            $item->is_locked = $isLimited && !in_array($item->metadata_id, $freeIds);
            $item->_score    = $score;
            return $item;
        })->sortByDesc('_score')->values();

        $realCount   = $scored->count();
        $isFallback  = $realCount === 0;
        $targetTotal = 10;
        $maxFreeBait = 2;

        if ($isLimited) {
            if ($isFallback) {
                $freeSlot    = $this->randomMetadata($maxFreeBait, $q, true, $freeIds, []);
                $premiumSlot = $this->randomMetadata($targetTotal - $maxFreeBait, $q, false, [], $freeSlot->pluck('metadata_id')->all());
                $sorted = $freeSlot->concat($premiumSlot)->values();
            } else {
                // Pisahkan hasil match asli: mana yang gratis (unlocked), mana yang premium (locked)
                // $scored sudah ter-sort by _score sebelumnya, jadi urutan di dalam masing-masing grup tetap relevan
                $freeMatched    = $scored->filter(fn($item) => !$item->is_locked)->values();
                $premiumMatched = $scored->filter(fn($item) => $item->is_locked)->values();

                // Bait maksimal 2, ambil yang paling relevan duluan dari yang match asli
                $freeBait = $freeMatched->take($maxFreeBait);

                // BARU: kalau tidak ada satupun match asli yang gratis, pinjam dari pool gratis umum
                // (pola sama seperti borrow di klasifikasiShow())
                if ($freeBait->isEmpty() && !empty($freeIds)) {
                    $excludeIds = $scored->pluck('metadata_id')->all();
                    $freeBait = $this->randomMetadata($maxFreeBait, $q, true, $freeIds, $excludeIds);
                }

                // Free match asli yang kelebihan kuota bait tetap dikunci, digabung ke daftar premium
                $extraLockedFree = $freeMatched->slice($maxFreeBait)->values()
                    ->each(fn($item) => $item->is_locked = true);

                $lockedCombined = $premiumMatched->concat($extraLockedFree)->sortByDesc('_score')->values();

                $needed     = max(0, $targetTotal - $freeBait->count() - $lockedCombined->count());
                $excludeIds = $scored->pluck('metadata_id')->all();
                $padding    = $needed > 0
                    ? $this->randomMetadata($needed, $q, false, [], $excludeIds)
                    : collect();

                // BARU: urutan final — gratis SELALU di paling awal, baru premium (sorted by relevansi), baru padding
                $sorted = $freeBait->concat($lockedCombined)->concat($padding)->values();
            }
        } else {
            // Full access (sudah langganan) — urutan MURNI berdasar relevansi, tidak ada perlakuan khusus gratis/premium
            if ($isFallback) {
                $sorted = $this->randomMetadata($targetTotal, $q, null, [], []);
            } else {
                $matched    = $scored->each(fn($item) => $item->is_locked = false);
                $needed     = max(0, $targetTotal - $matched->count());
                $excludeIds = $matched->pluck('metadata_id')->all();
                $padding    = $needed > 0
                    ? $this->randomMetadata($needed, $q, null, [], $excludeIds)
                    : collect();
                $sorted = $matched->concat($padding)->values();
            }
        }

        $totalFound = $sorted->count();
        $realFound  = $realCount;

        $perPage     = 10;
        $currentPage = max(1, (int) $request->input('page', 1));
        $offset      = ($currentPage - 1) * $perPage;

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->slice($offset, $perPage)->values(),
            $totalFound,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.landing.search', [
            'q'          => $q,
            'sorted'     => $paginated,
            'isLimited'  => $isLimited,
            'freeIds'    => $freeIds,
            'totalFound' => $totalFound,
            'realFound'  => $realFound,
            'isFallback' => $isFallback,
        ]);
    }

/**
 * Ambil metadata random sebagai bahan padding/fallback hasil pencarian.
 * Kalau $q diisi, diprioritaskan yang similarity-nya ke keyword lebih tinggi
 * (bukan murni acak) supaya tetap "mendekati keyword" walau bukan match persis.
 *
 * @param int         $count
 * @param string      $q
 * @param bool|null   $isFree  true = hanya gratis, false = hanya premium, null = campur (dipakai utk full access)
 * @param array       $freeIds
 * @param array       $excludeIds
 */
private function randomMetadata(int $count, string $q, ?bool $isFree, array $freeIds, array $excludeIds)
{
    if ($count <= 0) {
        return collect();
    }

    $query = Metadata::where('status', 2)
        ->whereHas('data', fn($qd) => $qd->where('status', 1)->where('location_id', 0))
        ->with(['klasifikasi', 'data' => fn($qd) => $qd->where('status', 1)->where('location_id', 0)->with('rujukan.produsen')->limit(1)]);

    if ($isFree === true) {
        $query->where('is_free', 1);
        if (!empty($freeIds)) {
            $query->whereIn('metadata_id', $freeIds);
        }
    } elseif ($isFree === false) {
        $query->where('is_free', 0);
    }

    if (!empty($excludeIds)) {
        $query->whereNotIn('metadata_id', $excludeIds);
    }

    // Ambil pool lebih besar dari yang dibutuhkan, lalu urutkan berdasarkan
    // kemiripan ke keyword (similar_text) supaya hasil padding tetap "nyerempet" relevan
    $pool = $query->inRandomOrder()->limit(max($count * 5, 30))->get();

    if ($q === '' || $pool->isEmpty()) {
        return $pool->take($count)->each(function ($item) use ($isFree) {
            $item->is_locked  = $isFree === false ? true : ($isFree === true ? false : false);
            $item->_score     = 0;
            $item->_is_random = true;
        })->values();
    }

    $qLower = strtolower($q);

    $scored = $pool->map(function ($item) use ($qLower, $isFree) {
        similar_text($qLower, strtolower($item->nama), $similarity);
        $item->_score     = $similarity;
        $item->is_locked  = $isFree === false ? true : ($isFree === true ? false : false);
        $item->_is_random = true;
        return $item;
    });

    return $scored->sortByDesc('_score')->take($count)->values();
}

    private function isLimitedUser(): bool
    {
        if (!auth()->check()) {
            return true;
        }

        $user = auth()->user();

        if ($user->group_id != 3) {
            return false; // bukan customer (admin/pengelola) → tidak dibatasi
        }

        return !app(SubscriptionAccessService::class)->hasActiveAccess($user);
    }

    /**
     * Semua metadata_id yang ditandai is_free = 1.
     */
    private function getFreeIds(): array
    {
        return Metadata::where('status', 2)
            ->where('is_free', 1)
            ->pluck('metadata_id')
            ->all();
    }

    /**
     * Rekomendasi data gratis: minimal 3, maksimal 6 item.
     * Maksimal 3 item boleh berasal dari rujukan (sumber) yang sama.
     */
    private function getFreeRecommendations(int $min = 3, int $max = 20, int $maxPerSource = 20)
    {
        $pool = Metadata::with([
                'klasifikasi',
                'data' => fn($q) => $q->where('status', 1)
                    ->where('location_id', 0)
                    ->with('location', 'rujukan.produsen')
                    ->limit(1),
            ])
            ->where('status', 2)
            ->where('is_free', 1)
            ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0))
            ->orderBy('date_inputed', 'desc')
            ->orderBy('metadata_id', 'desc')
            ->get();

        $perSource = [];
        $selected  = collect();

        foreach ($pool as $m) {
            if ($selected->count() >= $max) {
                break;
            }

            $sourceKey = $m->data->first()?->rujukan_id ?? 'meta-' . $m->metadata_id;
            $perSource[$sourceKey] ??= 0;

            if ($perSource[$sourceKey] >= $maxPerSource) {
                continue;
            }

            $perSource[$sourceKey]++;
            $selected->push($m);
        }

        return $selected->count() >= $min ? $selected : collect();
    }

    public function dataSeries(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $perPage = in_array($perPage, [12, 24, 48]) ? $perPage : 12;

        $freeIds   = $this->getFreeIds();
        $isLimited = $this->isLimitedUser();

        // ── Rekomendasi data gratis: min 3, max 6, max 3 per sumber sama ──
        $rekomendasiGratis = $isLimited ? $this->getFreeRecommendations(3, 6, 3) : collect();
        $rekomendasiIds    = $rekomendasiGratis->pluck('metadata_id')->all();

        // Base query — "Semua Data" (exclude yang sudah masuk rekomendasi)
        $query = Metadata::with([
            'klasifikasi',
            'produsen',
            'data' => function ($q) {           
                $q->where('status', 1)
                ->where('location_id', 0)
                ->with('rujukan.produsen')
                ->limit(1);
            },
        ])
        ->where('status', 2)
        ->whereHas('data', fn($q) => $q->where('status', 1)->where('location_id', 0));

        if (!empty($rekomendasiIds)) {
            $query->whereNotIn('metadata_id', $rekomendasiIds);
        }

        if ($q = trim($request->input('q', ''))) {
            $query->where(function ($qb) use ($q) {
                $qb->where('nama', 'like', "%{$q}%")
                ->orWhere('alias', 'like', "%{$q}%")
                ->orWhere('tag', 'like', "%{$q}%");
            });
        }

        if ($klas = trim($request->input('klasifikasi', ''))) {
            $query->whereHas('klasifikasi', fn($qb) =>
                $qb->where('nama_klasifikasi', $klas)
            );
        }

        if ($freq = trim($request->input('frekuensi', ''))) {
            $query->where('frekuensi_penerbitan', $freq);
        }

        if ($tipe = trim($request->input('tipe', ''))) {
            $query->where('tipe_data', $tipe);
        }

        // "Semua Data": premium dulu, gratis ditaruh paling belakang
        match ($request->input('sort', 'terbaru')) {
            'az'    => $query->orderByRaw('metadata.is_free ASC')->orderBy('nama', 'asc'),
            'za'    => $query->orderByRaw('metadata.is_free ASC')->orderBy('nama', 'desc'),
            default => $query->orderByRaw('metadata.is_free ASC')
                            ->orderBy('date_inputed', 'desc')
                            ->orderBy('metadata_id', 'desc'),
        };

        $metadataList = $query->paginate($perPage)->withQueryString();

        // Year ranges (batch) — gabungkan id "Semua Data" + id rekomendasi
        $metadataIds = array_merge(
            $metadataList->pluck('metadata_id')->all(),
            $rekomendasiIds
        );

        $yearRanges = DB::table('data')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->whereIn('data.metadata_id', $metadataIds)
            ->where('data.status', 1)
            ->whereNotNull('data.number_value')
            ->groupBy('data.metadata_id')
            ->selectRaw('data.metadata_id, MIN(time.year) as min_year, MAX(time.year) as max_year')
            ->get()
            ->keyBy('metadata_id')
            ->map(fn($r) => $r->min_year . '-' . $r->max_year);

        $klasifikasiList = Klasifikasi::orderBy('nama_klasifikasi')
            ->pluck('nama_klasifikasi')->filter()->values();

        $frekuensiList = Metadata::where('status', 2)
            ->whereNotNull('frekuensi_penerbitan')->distinct()
            ->orderBy('frekuensi_penerbitan')->pluck('frekuensi_penerbitan')->filter();

        $tipeList = Metadata::where('status', 2)
            ->whereNotNull('tipe_data')->distinct()
            ->orderBy('tipe_data')->pluck('tipe_data')->filter();

        $totalMetadata    = Metadata::where('status', 2)->count();
        $totalKlasifikasi = Klasifikasi::count();
        $totalProdusen    = ProdusenData::count();

        return view('pages.landing.data_series', compact(
            'metadataList',
            'klasifikasiList',
            'frekuensiList',
            'tipeList',
            'totalMetadata',
            'totalKlasifikasi',
            'totalProdusen',
            'freeIds',
            'yearRanges',
            'isLimited',
            'rekomendasiGratis',
        ));
    }

    public function dataShow(int $metadataId)
    {
        $metadata = Metadata::with(['klasifikasi', 'produsen'])
            ->where('status', 2)
            ->findOrFail($metadataId);

        // Gate
        if ($this->isLimitedUser()) {
            $freeIds = $this->getFreeIds();
            if (!in_array($metadataId, $freeIds)) {
                return redirect()->route('langganan')
                    ->with('info', 'Akses data ini memerlukan langganan aktif.');
            }
        }

        $yearRange = DB::table('data')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->where('data.metadata_id', $metadataId)
            ->where('data.status', 1)
            ->where('data.location_id', 0)
            ->whereNotNull('data.number_value')
            ->selectRaw('MIN(time.year) as min_year, MAX(time.year) as max_year')
            ->first();

        $yearStart = $yearRange?->min_year ?? (now()->year - 5);
        $yearEnd   = $yearRange?->max_year ?? (now()->year - 1);

        $rawData = Data::with(['time', 'location', 'rujukan.produsen'])
            ->where('metadata_id', $metadataId)
            ->where('status', 1)
            ->where('location_id', 0)
            ->whereHas('time', fn($q) => $q->whereBetween('year', [$yearStart, $yearEnd]))
            ->get();

        $grouped = $rawData
            ->sortBy(fn($d) => $d->time?->year)
            ->groupBy(fn($d) => $d->time?->year);

        $tableRows   = [];
        $chartLabels = [];
        $chartValues = [];

        foreach ($grouped as $year => $items) {
            $val = $items->pluck('number_value')->filter(fn($v) => !is_null($v))->first();
            $tableRows[]   = ['period' => (string) $year, 'value' => $val, 'year' => $year];
            $chartLabels[] = (string) $year;
            $chartValues[] = $val !== null ? (float) $val : null;
        }

        $yearsWithData = array_column($tableRows, 'year');
        for ($y = $yearStart; $y <= $yearEnd; $y++) {
            if (!in_array($y, $yearsWithData)) {
                $tableRows[]   = ['period' => (string)$y, 'value' => null, 'year' => $y];
                $chartLabels[] = (string)$y;
                $chartValues[] = null;
            }
        }

        usort($tableRows, fn($a, $b) => $a['year'] <=> $b['year']);
        array_multisort(array_column($tableRows, 'year'), SORT_ASC, $tableRows);

        $chartLabels = array_column($tableRows, 'period');
        $chartValues = array_map(fn($r) => $r['value'] !== null ? (float)$r['value'] : null, $tableRows);
        $tableRows   = collect($tableRows);

        $firstData = $rawData->first();
        $rujukan   = $firstData?->rujukan;

        return view('pages.landing.data_show', compact(
            'metadata', 'tableRows', 'chartLabels', 'chartValues',
            'yearStart', 'yearEnd', 'rujukan',
        ));
    }
}