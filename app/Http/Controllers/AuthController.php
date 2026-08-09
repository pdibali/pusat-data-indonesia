<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use App\Services\OrganizationInvitationService;
use App\Services\SessionLimitService;
use App\Services\MailNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Services\RecaptchaService;

class AuthController extends Controller
{
    public function loginView()
    {
        if (Auth::check()){
            return back();
        }
        return view('pages.auth.login');
    }

    public function login(Request $request, RecaptchaService $recaptcha, MailNotifier $mailNotifier)
    {
        if (Auth::check()){
            return back();
        }
        
        $throttleKey = Str::transliterate(Str::lower($request->input('username', 'guest')).'|'.$request->ip());

        if (! app()->environment('testing') && $recaptcha->isEnabled()) {
            $token = $request->input('g-recaptcha-response');

            if (! $recaptcha->verify($token, 'login', $request->ip())) {
                return back()->withErrors([
                    'recaptcha' => 'Verifikasi keamanan gagal, silakan coba lagi.'
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
            'invitation_token' => ['nullable', 'string'],
            'invitation_email' => ['nullable', 'email'],
        ]);

        // Cari user berdasarkan username
        $user = User::where('username', $validated['username'])->first();

        if ($user && $user->locked_at) {
            return back()->withErrors(['username' => 'Akun Anda saat ini terkunci. Silakan cek email untuk membuka kembali.']);
        }

        if (!$user) {
            RateLimiter::hit($throttleKey, 3600);
            return back()
                ->withErrors(['username' => 'Username tidak ditemukan.'])
                ->withInput();
        }

        // ── Tambahan: blokir akun nonaktif ──
        if ((int) $user->status === 0) {
            return back()
                ->withErrors(['username' => 'Akun Anda nonaktif. Silakan hubungi admin.'])
                ->withInput();
        }

        if (!Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 3600);

            if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
                $token = Str::random(64);
                $user->update([
                    'locked_at' => now(),
                    'unlock_token' => $token,
                    'unlock_token_expires_at' => now()->addHours(24),
                ]);

                // Kirim lewat MailNotifier — otomatis pakai driver GAS/SMTP/Mailtrap
                // sesuai MAIL_MAILER, dan errornya di-report ke log (tidak ditelan diam-diam)
                $mailNotifier->kirimAccountLocked($user, $token);

                return back()
                    ->withErrors(['username' => 'Akun dikunci karena terlalu banyak percobaan login. Silakan cek email untuk membuka kembali.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['password' => 'Password yang Anda masukkan salah.'])
                ->withInput();
        }

        RateLimiter::clear($throttleKey);

        // Jika status tidak aktif
        if ($user->activation !== 'activated') {
            return back()
                ->withErrors(['username' => 'Akun belum aktif.'])
                ->withInput();
        }

        // Login berhasil
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->filled('invitation_token')) {
            $invitationService = app(OrganizationInvitationService::class);
            $membership = $invitationService->acceptInvitationForUser($request->input('invitation_token'), $user);

            if ($membership) {
                return redirect('/data')->with('success', 'Undangan organisasi berhasil diterima. Selamat bergabung!');
            }

            $invitation = $invitationService->findPendingInvitationByToken($request->input('invitation_token'));

            if ($invitation && $invitation->email !== $user->email) {
                return redirect('/data')->withErrors(['invitation' => 'Silakan login menggunakan akun yang diundang untuk menerima undangan organisasi.']);
            }

            return redirect('/data')->withErrors(['invitation' => 'Tidak dapat menerima undangan organisasi. Silakan periksa kembali tautan undangan atau hubungi pemilik organisasi.']);
        }

        if ((int) $user->group_id === 3) {
            $limitsService = app(SessionLimitService::class);
            $result = $limitsService->handleLoginAttempt($user, $request);

            if ($result['status'] === 'pending_login') {
                return redirect()->route('session.pending_login.wait', ['id' => $result['pending_login']->id]);
            }

            if ($result['status'] === 'error') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['login' => $result['message']]);
            }
        }

        return redirect('/data');
    }

    public function logout(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        Auth::logout();

        if ($request->hasSession()) {
            UserSession::where('session_id', $request->session()->getId())->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}