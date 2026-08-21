<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => view('pages.auth.login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => view('pages.auth.register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(3)->by($throttleKey);
        });
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $username = $request->input(Fortify::username());
            $password = (string) $request->password;

            $user = \App\Models\User::where('email', $username)
                ->orWhere('username', $username)
                ->first();

            if (! $user) {
                return null;
            }

            // If account is locked, prevent login
            if ($user->locked_at) {
                return null;
            }

            if (\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
                // successful login -> clear rate limiter
                $throttleKey = Str::transliterate(Str::lower($username).'|'.$request->ip());
                RateLimiter::clear($throttleKey);
                return $user;
            }

            // failed login -> increment and possibly lock
            $throttleKey = Str::transliterate(Str::lower($username).'|'.$request->ip());
            RateLimiter::hit($throttleKey);

            if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
                // lock account and send unlock email
                $user->update([
                    'locked_at' => now(),
                    'unlock_token' => $token = Str::random(64),
                    'unlock_token_expires_at' => now()->addHours(24),
                ]);

                try {
                    app(\App\Services\MailNotifier::class)
                        ->kirimAccountLocked($user, $token);
                } catch (\Throwable $e) {
                    // swallow mail errors
                }
            }

            // If reCAPTCHA is enabled, require token verification for failed attempts
            $recaptchaSecret = env('RECAPTCHA_SECRET');
            if ($recaptchaSecret) {
                $token = $request->input('g-recaptcha-response');
                if (! $token) {
                    return null;
                }

                try {
                    $resp = \Illuminate\Support\Facades\Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => $recaptchaSecret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ]);

                    if (! $resp->successful() || ! ($resp->json('success') ?? false)) {
                        return null;
                    }
                } catch (\Throwable $e) {
                    return null;
                }
            }

            return null;
        });
    }
}
