<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProdusenController;
use App\Http\Controllers\RujukanController;
use App\Http\Controllers\KlasifikasiController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\MetadataImportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\WaktuController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateExportController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\AdminTransaksiController;
use App\Http\Controllers\AnomalyControlController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\SettingController;

    // ── Auth ─────────────────────────────────────────────────────
    Route::get('/login',  [AuthController::class, 'loginView'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register',           [RegisterController::class, 'registerView'])->name('register');
    Route::post('/register',          [RegisterController::class, 'register']);
    Route::get('/invitation/accept/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
    Route::get('/verify-email/{token}', [RegisterController::class, 'verify'])->name('verify.email');
    Route::get('/unlock-account/{token}', [AccountSecurityController::class, 'unlock'])->name('account.unlock');

    // ── Password Reset ────────────────────────────────────────────
    Route::get('/forgot-password',        [PasswordResetController::class, 'requestView'])->name('password.request');
    Route::post('/forgot-password',       [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetView'])->name('password.reset');
    Route::post('/reset-password',        [PasswordResetController::class, 'reset'])->name('password.update');

    Route::post('/logout',[AuthController::class, 'logout']);

    require __DIR__.'/settings.php';

    Route::get('/statistik/{metadataId}', [LandingController::class, 'dataShow'])
        ->name('landing.data.show')
        ->whereNumber('metadataId');

    // Transaksi notification (Midtrans callback)   
    Route::post('/transaksi/notification', [TransaksiController::class, 'notification'])
        ->name('transaksi.notification')
        ->withoutMiddleware([VerifyCsrfToken::class]);
    Route::get('/transaksi/{transaksi}/bayar-ulang', [TransaksiController::class, 'bayarUlang'])
    ->name('transaksi.bayar-ulang');

    // Debug endpoints (HAPUS di PRODUCTION!)
    Route::post('/transaksi/webhook-test', [TransaksiController::class, 'webhookTest'])
        ->name('transaksi.webhook-test')
        ->withoutMiddleware([VerifyCsrfToken::class]);
    
    Route::get('/transaksi/signature-test', [TransaksiController::class, 'signatureTest'])
        ->name('transaksi.signature-test');
        

    Route::get('/', [LandingController::class, 'index'])->name('landing');
    Route::get('/search-metadata', [LandingController::class, 'searchMetadata'])->name('search_metadata');
    Route::get('/autocomplete',    [LandingController::class, 'autocomplete'])->name('autocomplete');
    Route::get('/search',       [LandingController::class, 'searchResults'])->name('search');

    Route::get('/langganan', [LandingController::class, 'langganan'])->name('langganan');
    Route::get('/bantuan', [LandingController::class, 'bantuan'])->name('bantuan');
    Route::get('/klasifikasi', [LandingController::class, 'klasifikasiIndex'])
        ->name('klasifikasi.index');
    Route::get('/klasifikasi/{klasifikasi}', [LandingController::class, 'klasifikasiShow'])
        ->name('klasifikasi.show');
    Route::get('/data-series', [LandingController::class, 'dataSeries'])->name('landing.data.series');
        
    
    // ─────────────────────────────────────────────────────────────
    // Halaman Data dan TEMPLATE TAMPILAN — Sebagian bisa diakses tanpa login
    // ─────────────────────────────────────────────────────────────
    //
    
    // Halaman index data (daftar data) bisa diakses tanpa login, tapi untuk akses detail data tetap harus login.
    Route::get('/data',[DataController::class, 'index'])->name('data.index');

    Route::get('/metadata/{id}/detail-api', [MetadataController::class, 'detailApi'])
                ->name('metadata.detail_api');
                
    // ── Template Tampilan ─────────────────────────────────────────────────
    Route::prefix('template-tampilan')->name('template.')->group(function () {
    
        // Halaman pilih jenis template
        Route::get('/',                   [TemplateController::class, 'index'])->name('index');
        Route::get('/restore-state', [TemplateController::class, 'restoreState'])->name('restore_state');
        Route::get('/available-periods', [TemplateController::class, 'getAvailablePeriods'])->name('available_periods');
        Route::get('/freq-counts', [TemplateController::class, 'getFreqCounts'])->name('freq_counts');
        Route::post('/table-data',       [TemplateController::class, 'fetchTableData'])->name('table_data');
        // Guest endpoints (tanpa auth)
        Route::post('/table-data-guest',  [TemplateController::class, 'fetchTableDataGuest'])->name('table_data_guest');
        Route::post('/freq-counts-guest', [TemplateController::class, 'getFreqCountsGuest'])->name('freq_counts_guest');
        Route::get('/create',             [TemplateController::class, 'create'])->name('create');
    
        // Form per jenis
        Route::get('/create/metadata',    [TemplateController::class, 'createByMetadata'])->name('create.metadata');
        Route::get('/create/klasifikasi', [TemplateController::class, 'createByKlasifikasi'])->name('create.klasifikasi');
        Route::get('/create/wilayah',     [TemplateController::class, 'createByWilayah'])->name('create.wilayah');
    
        // AJAX helpers
        Route::get('/search-metadata',    [TemplateController::class, 'searchMetadata'])->name('search_metadata');
        Route::post('/fetch-preview',     [TemplateController::class, 'fetchMetadataPreview'])->name('fetch_preview');
        Route::post('/fetch-klasifikasi', [TemplateController::class, 'fetchByKlasifikasi'])->name('fetch_klasifikasi');
        Route::post('/fetch-wilayah',     [TemplateController::class, 'fetchByWilayah'])->name('fetch_wilayah');
        Route::get('/child-locations',    [TemplateController::class, 'getChildLocations'])->name('child_locations');
        Route::get('/fetch-data',         [TemplateController::class, 'fetchData'])->name('fetch_data');
    
        // Cascade dropdown wilayah (Template Wilayah)
        Route::get('/get-provinsi',       [TemplateController::class, 'getProvinsi'])->name('get_provinsi');
        Route::get('/get-kabupaten',      [TemplateController::class, 'getKabupaten'])->name('get_kabupaten');
        Route::get('/get-kecamatan',      [TemplateController::class, 'getKecamatan'])->name('get_kecamatan_wil');
        Route::get('/get-desa',           [TemplateController::class, 'getDesa'])->name('get_desa_wil');
    
        // halaman grafik
        Route::get('/grafik', [TemplateController::class, 'showGrafik'])->name('grafik');

        // CRUD
        Route::post('/',                   [TemplateController::class, 'store'])->name('store');
        Route::get('/{tampilan}/edit',     [TemplateController::class, 'edit'])->name('edit');
        Route::put('/{tampilan}',          [TemplateController::class, 'update'])->name('update');
        Route::delete('/{tampilan}',       [TemplateController::class, 'destroy'])->name('destroy');
    
        // Show (AJAX) — untuk panel di halaman index data
        Route::get('/{tampilan}/show',    [TemplateController::class, 'show'])->name('show');
    });

    // ── Template Export: Excel / PDF / JSON ───────────────────────
    Route::prefix('template-export')->name('template.export.')->group(function () {
        Route::post('/excel', [TemplateExportController::class, 'excel'])->name('excel');
        Route::post('/pdf',   [TemplateExportController::class, 'pdf'])->name('pdf');
        Route::post('/json',  [TemplateExportController::class, 'json'])->name('json');
    });
    
// ─────────────────────────────────────────────────────────────
// AUTHENTICATED ROUTES
// ─────────────────────────────────────────────────────────────

Route::middleware(['is.login', 'is.pengelola', 'is.customer'])->group(function () {

    Route::get('/session/security-notifications', [\App\Http\Controllers\SessionSecurityController::class, 'securityNotifications'])
        ->name('session.security_notifications');
    Route::post('/session/verification/{id}/approve', [\App\Http\Controllers\SessionSecurityController::class, 'approveVerification'])
        ->name('session.verification.approve');
    Route::post('/session/verification/{id}/reject', [\App\Http\Controllers\SessionSecurityController::class, 'rejectVerification'])
        ->name('session.verification.reject');
    Route::post('/session/pending-login/{id}/approve', [\App\Http\Controllers\SessionSecurityController::class, 'approvePendingLogin'])
        ->name('session.pending_login.approve');
    Route::post('/session/pending-login/{id}/reject', [\App\Http\Controllers\SessionSecurityController::class, 'rejectPendingLogin'])
        ->name('session.pending_login.reject');
    Route::get('/session/pending-login/{id}/status', [\App\Http\Controllers\SessionSecurityController::class, 'pendingLoginStatus'])
        ->name('session.pending_login.status');
    Route::get('/session/pending-login/{id}/wait', [\App\Http\Controllers\SessionSecurityController::class, 'pendingLoginWait'])
        ->name('session.pending_login.wait');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users',    UserController::class);
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::resource('groups',   GroupController::class);
        Route::resource('produsen', ProdusenController::class);
        Route::resource('rujukan',  RujukanController::class);
        Route::resource('klasifikasi', KlasifikasiController::class);
        Route::resource('satuan', SatuanController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('layanan', LayananController::class);
        Route::get('organizations', [\App\Http\Controllers\AdminOrganizationController::class, 'index'])->name('organizations.index');
        Route::get('organizations/{organization}', [\App\Http\Controllers\AdminOrganizationController::class, 'show'])->name('organizations.show');
    
        // Toggle Status for Master Data
        Route::post('users/{user}/toggle-status',        [UserController::class, 'toggleStatus'])->name('users.toggle_status');
        Route::post('groups/{group}/toggle-status',      [GroupController::class, 'toggleStatus'])->name('groups.toggle_status');
        Route::post('produsen/{produsen}/toggle-status', [ProdusenController::class, 'toggleStatus'])->name('produsen.toggle_status');
        Route::post('rujukan/{rujukan}/toggle-status',   [RujukanController::class, 'toggleStatus'])->name('rujukan.toggle_status');
        Route::post('klasifikasi/{klasifikasi}/toggle-status', [KlasifikasiController::class, 'toggleStatus'])->name('klasifikasi.toggle_status');
        
        // Status actions
        Route::post('layanan/{layanan}/publish',       [LayananController::class, 'publish'])->name('layanan.publish');
        Route::post('layanan/{layanan}/takedown',      [LayananController::class, 'takedown'])->name('layanan.takedown');
        Route::post('layanan/{layanan}/draft',         [LayananController::class, 'draft'])->name('layanan.draft');
        Route::post('layanan/{layanan}/toggle-popular',[LayananController::class, 'togglePopular'])->name('layanan.toggle_popular');

        // ADMIN TRANSAKSI
        Route::prefix('transaksi-admin')->name('transaksi.')->group(function () {
            Route::get('/dashboard', [AdminTransaksiController::class, 'dashboard'])->name('dashboard');
            Route::get('/',          [AdminTransaksiController::class, 'index'])->name('index');
            Route::get('/{transaksi}', [AdminTransaksiController::class, 'show'])->name('show');
            Route::get('/{transaksi}/bukti', [AdminTransaksiController::class, 'lihatBukti'])->name('bukti');
            Route::post('/{transaksi}/verify', [AdminTransaksiController::class, 'verify'])->name('verify');
            Route::post('/{transaksi}/reject', [AdminTransaksiController::class, 'reject'])->name('reject');
        });

        Route::post('/settings/toggle-midtrans', [SettingController::class, 'toggleMidtrans'])
            ->name('settings.toggle_midtrans');
    });

    // TRANSAKSI — User (memerlukan login)
    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::post('/checkout',                [TransaksiController::class, 'checkout'])->name('checkout');
        Route::get('/riwayat',                  [TransaksiController::class, 'riwayat'])->name('riwayat');
        Route::get('/{transaksi}/sukses',       [TransaksiController::class, 'sukses'])->name('sukses');   
        Route::get('/{transaksi}/detail',       [TransaksiController::class, 'detail'])->name('detail');
        Route::get('/{transaksi}/status',       [TransaksiController::class, 'status'])->name('status');
        Route::get('/{transaksi}/pilih-metode', [TransaksiController::class, 'pilihMetode'])->name('pilih_metode');
        Route::post('/{transaksi}/bayar-midtrans',[TransaksiController::class, 'bayarMidtrans'])->name('bayar_midtrans');
        Route::post('/{transaksi}/pilih-manual', [TransaksiController::class, 'pilihManual'])->name('pilih_manual');
        Route::get('/{transaksi}/manual',        [TransaksiController::class, 'manualForm'])->name('manual.form');
        Route::post('/{transaksi}/upload-bukti', [TransaksiController::class, 'uploadBukti'])->name('upload_bukti');
    });

    Route::prefix('organization')->name('organizations.')->group(function () {
        Route::get('/create', [OrganizationController::class, 'create'])->name('create');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('/team', [OrganizationController::class, 'team'])->name('team');
        Route::get('/{organization}/manage-team', [OrganizationController::class, 'manageTeam'])->name('manage-team');
        Route::post('/{organization}/invite-member', [OrganizationController::class, 'inviteMember'])->name('invite-member');
        Route::delete('/{organization}/members/{member}', [OrganizationController::class, 'removeMember'])->name('remove-member');
    });

    // ── Dimensi Lokasi ──────────────────────────────────────
    Route::prefix('dimensi_lokasi')->name('dimensi_lokasi.')->group(function () {
        Route::get('/',       [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/auto',   [LocationController::class, 'store'])->name('store');
        Route::post('/manual', [LocationController::class, 'store2'])->name('store2');
        Route::post('{location}/toggle-status', [LocationController::class, 'toggleStatus'])->name('toggle_status');
    });

    // ── Dimensi Waktu ────────────────────────────────────────
    Route::prefix('dimensi_waktu')->name('dimensi_waktu.')->group(function () {
        Route::get('/',       [WaktuController::class, 'index'])->name('index');
        Route::get('/create', [WaktuController::class, 'create'])->name('create');
        Route::post('/',      [WaktuController::class, 'store'])->name('store');
        Route::post('{waktu}/toggle-status', [WaktuController::class, 'toggleStatus'])->name('toggle_status');
    });

    // ── Data ─────────────────────────────────────────────────
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('/create', [DataController::class, 'create'])->name('create');
        // Ambil produsen_id dari rujukan_id
        Route::get('/get-produsen-by-rujukan', [DataController::class, 'getProdusenByRujukan'])->name('get_produsen_by_rujukan');
        Route::post('/',      [DataController::class, 'store'])->name('store');

        // AJAX (di atas wildcard)
        Route::get('/search-wilayah',  [DataController::class, 'searchWilayah'])->name('search_wilayah');
        Route::get('/kecamatan',        [DataController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/desa',             [DataController::class, 'getDesa'])->name('desa');
        Route::get('/search-metadata',  [DataController::class, 'searchMetadata'])->name('search_metadata');
        Route::get('/search-year',      [DataController::class, 'searchYear'])->name('search_year');

        // Template
        Route::post('/template',              [DataController::class, 'storeTemplate'])->name('template.store');
        Route::delete('/template/{tampilan}', [DataController::class, 'deleteTemplate'])->name('template.delete');

        // Excel
        Route::post('/preview-excel',  [DataController::class, 'previewExcel'])->name('preview_excel');
        Route::post('/import-excel',   [DataController::class, 'importExcel'])->name('import_excel');
        Route::get('/template-excel',  [DataController::class, 'downloadTemplateExcel'])->name('template_excel');

        // Export Data (Excel / PDF / JSON)
        Route::get('/export/excel', [DataExportController::class, 'excel'])->name('export.excel');
        Route::get('/export/pdf',   [DataExportController::class, 'pdf'])->name('export.pdf');
        Route::get('/export/json',  [DataExportController::class, 'json'])->name('export.json');

        // Approval
        Route::get('/approval',         [DataController::class, 'approval'])->name('approval');
        Route::post('/bulk-approve',    [DataController::class, 'bulkApprove'])->name('bulk_approve');
        Route::get('/{datum}/edit',     [DataController::class, 'edit'])->name('edit');
        Route::put('/{datum}',          [DataController::class, 'update'])->name('update');
        Route::post('/{datum}/approve', [DataController::class, 'approve'])->name('approve');
        Route::post('/{datum}/reject',  [DataController::class, 'reject'])->name('reject');

        // Wildcard paling bawah
        Route::get('/{datum}', [DataController::class, 'show'])->name('show');
    });

    Route::prefix('anomaly')->name('anomaly.')->group(function () {
    
        // ── Halaman Control Data Anomali ──────────────────────────
        Route::prefix('control')->name('control.')->group(function () {
    
            // Index: daftar semua anomali
            Route::get('/',           [AnomalyControlController::class, 'index'])->name('index');
    
            // Detail satu anomali + histori review
            Route::get('/{anomaly}',  [AnomalyControlController::class, 'show'])->name('show');

            // Export laporan PDF
            Route::get('/{anomaly}/export-report', [AnomalyControlController::class, 'exportReport'])->name('export_report');
    
            // Review satu anomali (POST dari modal)
            Route::post('/{anomaly}/review', [AnomalyControlController::class, 'review'])->name('review');
    
            // Bulk review (POST dari halaman index)
            Route::post('/bulk-review', [AnomalyControlController::class, 'bulkReview'])->name('bulk_review');
    
            // Submit data ke Under Review (POST)
            Route::post('/data/{datum}/submit', [AnomalyControlController::class, 'submitForReview'])->name('submit_review');
    
            // ── AJAX ─────────────────────────────────────────────
            // Perbandingan antar sumber data
            Route::get('/ajax/compare-sources', [AnomalyControlController::class, 'compareSources'])->name('compare_sources');
    
            // Histori audit trail satu data
            Route::get('/ajax/audit/{dataId}',  [AnomalyControlController::class, 'auditHistory'])->name('audit_history');
    
            // Stats & trend untuk grafik
            Route::get('/ajax/trend-stats',     [AnomalyControlController::class, 'trendStats'])->name('trend_stats');

            Route::post('/ajax/scan-all', [AnomalyControlController::class, 'scanAll'])->name('scan_all');
        });
    
        // ── Anomaly Rules (threshold) ─────────────────────────────
        Route::get('/rules',            [AnomalyControlController::class, 'rules'])->name('control.rules');
        Route::put('/rules/{rule}',     [AnomalyControlController::class, 'updateRule'])->name('control.rules.update');
    });

    // ── Metadata ─────────────────────────────────────────────
    Route::get( '/metadata',            [MetadataController::class, 'index'])->name('metadata.index');
    Route::get( '/metadata/create',     [MetadataController::class, 'create'])->name('metadata.create');
    Route::get('/metadata/search-for-group', [MetadataController::class, 'searchForGroupBy'])
        ->name('metadata.search_for_group');
    Route::post('/metadata',            [MetadataController::class, 'store'])->name('metadata.store');
    Route::get( '/metadata/check-nama', [MetadataController::class, 'checkNama'])->name('metadata.check_nama');
    Route::get( '/metadata/approval',   [MetadataController::class, 'approval'])->name('metadata.approval');
    Route::get('metadata/{id}/coverage', [MetadataController::class, 'coverageData'])
        ->name('metadata.coverage');

    Route::get('/metadata/export/count', [MetadataController::class, 'exportCount'])->name('metadata.export.count');
    Route::get('/metadata/export',       [MetadataController::class, 'export'])->name('metadata.export');
    Route::get('/metadata/template',     [MetadataController::class, 'exportTemplate'])->name('metadata.template');
    
    // ── Import Excel ──────────────────────────────────────────
    Route::post('/metadata/import/preview', [MetadataImportController::class, 'preview'])->name('metadata.import.preview');
    Route::post('/metadata/import/store',   [MetadataImportController::class, 'store'])->name('metadata.import.store');
    
    // ── Bulk Approve ───────────────────────────────────────────
    Route::post('/metadata/bulk-approve',     [MetadataController::class, 'bulkApprove'])->name('metadata.bulk_approve');
    Route::post('/metadata/bulk-approve-all', [MetadataController::class, 'bulkApproveAll'])->name('metadata.bulk_approve_all');
    Route::post('/metadata/{metadata}/toggle-free', [MetadataController::class, 'toggleFree'])->name('metadata.toggle_free');

    // ── Edit / Update ─────────────────────────────────────────
    Route::get( '/metadata/{metadata}/edit', [MetadataController::class, 'edit'])->name('metadata.edit');
    Route::put( '/metadata/{metadata}',      [MetadataController::class, 'update'])->name('metadata.update');
    
    // ── Wildcard /{metadata} — paling bawah ──────────────────
    Route::post('/metadata/{metadata}/approve',    [MetadataController::class, 'approve'])->name('metadata.approve');
    Route::post('/metadata/{metadata}/reject',     [MetadataController::class, 'reject'])->name('metadata.reject');
    Route::post('/metadata/{metadata}/reactivate', [MetadataController::class, 'reactivate'])->name('metadata.reactivate');
    Route::get( '/metadata/{metadata}',            [MetadataController::class, 'detail'])->name('metadata.detail');

});