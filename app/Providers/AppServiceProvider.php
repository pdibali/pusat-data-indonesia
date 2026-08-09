<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL; 
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use App\Models\Klasifikasi;
use Illuminate\Support\Facades\Mail;
use App\Mail\Transport\MailtrapTransport;
use App\Services\AuditTrailService;
use App\Services\AnomalyDetectionService;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Services\StemmerService;
use App\Models\Transaksi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── AuditTrailService: singleton ─────────────────────────
        // Singleton agar IP/UserAgent di-resolve sekali per request,
        // dan bisa di-inject ke service lain tanpa re-instantiate.
        $this->app->singleton(AuditTrailService::class);
 
        // ── AnomalyDetectionService: singleton ───────────────────
        // Bergantung pada AuditTrailService (auto-resolved oleh container).
        $this->app->singleton(AnomalyDetectionService::class);
 
        // ── WorkflowService: singleton ───────────────────────────
        $this->app->singleton(WorkflowService::class);

        $this->app->singleton(StemmerService::class, fn () => new StemmerService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('mailtrap', function (array $config) {
            return new MailtrapTransport(
                apiKey: config('services.mailtrap.token'),
                inboxId: (int) config('services.mailtrap.inbox_id'),
            );
        });
        
        Carbon::setLocale('id');

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {

            $allKlasifikasi = Klasifikasi::query()
                ->whereHas('metadata', function ($q) {
                    $q->where('status', 2)
                    ->whereHas('data', fn($qd) => $qd->where('status', 1));
                })
                ->orderBy('nama_klasifikasi')
                ->pluck('nama_klasifikasi')
                ->map(fn ($k) => trim((string) $k))
                ->filter(function ($k) {
                    return $k !== ''
                        && $k !== '-'
                        && \Illuminate\Support\Str::slug($k) !== '';
                })
                ->values();

            $view->with('allKlasifikasi', $allKlasifikasi);

            $view->with(
                'pendingVerifikasiCount',
                Transaksi::where('status', 'menunggu_verifikasi')->count()
            );
        });

        // Hapus/comment composer khusus sidebar ini:
        // View::composer('layouts.partials.sidebar', function ($view) { ... });

        View::composer('layouts.partials.sidebar', function ($view) {
            $view->with(
                'pendingVerifikasiCount',
                Transaksi::where('status', 'menunggu_verifikasi')->count()
            );
        });

        $this->configureDefaults();

        Paginator::defaultView('Vendor.pagination.custom');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}