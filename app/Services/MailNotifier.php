<?php

namespace App\Services;

use App\Mail\VerifikasiEmail;
use App\Mail\ResetPasswordMail;
use App\Mail\AccountLocked;
use App\Mail\NewDataReportSubmitted;
use App\Mail\NewDataRequestSubmitted;
use App\Models\DataReport;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * MailNotifier — satu pintu untuk semua pengiriman email transaksional.
 *
 * Kenapa ini dibuat:
 * Controller TIDAK perlu tahu apakah email dikirim lewat Google Apps Script
 * (HTTP relay), SMTP biasa, atau Mailtrap. Cukup panggil method di sini.
 * Provider aktif ditentukan langsung oleh MAIL_MAILER di .env:
 *   - MAIL_MAILER=gas     -> lewat Google Apps Script relay
 *   - MAIL_MAILER=smtp/mailtrap/resend/dst -> lewat Laravel Mail standar
 *
 * Ganti provider di masa depan = ganti 1 env var (MAIL_MAILER),
 * TIDAK perlu ubah controller ataupun file ini (kecuali menambah driver baru).
 */
class MailNotifier
{
    public function kirimDataRequest(DataRequest $dataRequest, string $recipient): bool
    {
        if (config('mail.default') === 'gas') {
            return $this->panggilGas('data_request_submitted', $recipient, [
                'namaData' => $dataRequest->nama_data,
                'deskripsi' => $dataRequest->deskripsi,
                'instansi' => $dataRequest->instansi_perkiraan,
                'reviewLink' => route('admin.data_requests.show', $dataRequest),
            ]);
        }

        Mail::to($recipient)->queue(new NewDataRequestSubmitted($dataRequest));
        return true;
    }

    public function kirimDataReport(DataReport $dataReport, string $recipient): bool
    {
        if (config('mail.default') === 'gas') {
            return $this->panggilGas('data_report_submitted', $recipient, [
                'namaData' => $dataReport->nama_data,
                'produsenData' => $dataReport->produsen_data,
                'deskripsiKesalahan' => $dataReport->deskripsi_kesalahan,
                'reviewLink' => route('admin.data_reports.show', $dataReport),
            ]);
        }

        Mail::to($recipient)->queue(new NewDataReportSubmitted($dataReport));
        return true;
    }

    /**
     * Kirim email verifikasi akun ke user baru.
     */
    public function kirimVerifikasi(User $user, string $token): bool
    {
        $mailer = config('mail.default');

        try {
            return match ($mailer) {
                'gas'   => $this->kirimVerifikasiViaGas($user, $token),
                default => $this->kirimVerifikasiViaLaravelMail($user, $token),
            };
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Gagal kirim email verifikasi', [
                'mailer' => $mailer,
                'email'  => $user->email,
            ]);
            return false;
        }
    }

    /**
     * Kirim email reset password.
     */
    public function kirimResetPassword(User $user, string $token): bool
    {
        $mailer = config('mail.default');

        try {
            return match ($mailer) {
                'gas'   => $this->kirimResetPasswordViaGas($user, $token),
                default => $this->kirimResetPasswordViaLaravelMail($user, $token),
            };
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Gagal kirim email reset password', [
                'mailer' => $mailer,
                'email'  => $user->email,
            ]);
            return false;
        }
    }

    /**
     * Kirim email notifikasi akun terkunci + link unlock.
     */
    public function kirimAccountLocked(User $user, string $token): bool
    {
        $mailer = config('mail.default');

        try {
            return match ($mailer) {
                'gas'   => $this->kirimAccountLockedViaGas($user, $token),
                default => $this->kirimAccountLockedViaLaravelMail($user, $token),
            };
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Gagal kirim email account locked', [
                'mailer' => $mailer,
                'email'  => $user->email,
            ]);
            return false;
        }
    }

    // ── Driver: Google Apps Script relay ──────────────────────

    protected function kirimVerifikasiViaGas(User $user, string $token): bool
    {
        return $this->panggilGas('verifikasi_email', $user->email, [
            'nama' => $user->name,
            'link' => route('verify.email', $token),
        ]);
    }

    protected function kirimResetPasswordViaGas(User $user, string $token): bool
    {
        return $this->panggilGas('reset_password', $user->email, [
            'nama' => $user->name,
            'link' => route('password.reset', ['token' => $token, 'email' => $user->email]),
        ]);
    }

    protected function kirimAccountLockedViaGas(User $user, string $token): bool
    {
        // Catatan: perlu tambah template 'account_locked' di MailRelay.gs (Apps Script)
        return $this->panggilGas('account_locked', $user->email, [
            'nama'      => $user->name,
            'unlockUrl' => route('account.unlock', ['token' => $token]),
        ]);
    }

    protected function panggilGas(string $template, string $to, array $data): bool
    {
        $response = Http::post(config('services.gas.mail_url'), [
            'secret'   => config('services.gas.secret'),
            'to'       => $to,
            'template' => $template,
            'data'     => $data,
        ]);

        $sukses = $response->successful() && $response->json('success') === true;

        if (!$sukses) {
            report(new \Exception("GAS relay gagal ({$template}): " . $response->body()));
        }

        return $sukses;
    }

    // ── Driver: SMTP / Mailtrap / Resend / mailer Laravel standar lainnya ──

    protected function kirimVerifikasiViaLaravelMail(User $user, string $token): bool
    {
        Mail::to($user->email)->send(new VerifikasiEmail($user, $token));
        return true;
    }

    protected function kirimResetPasswordViaLaravelMail(User $user, string $token): bool
    {
        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));
        return true;
    }

    protected function kirimAccountLockedViaLaravelMail(User $user, string $token): bool
    {
        Mail::to($user->email)->send(new AccountLocked($user, $token));
        return true;
    }
}