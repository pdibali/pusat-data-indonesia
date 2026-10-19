<?php

namespace App\Services;

use App\Models\AuditTrail;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditTrailService
{
    // ══════════════════════════════════════════════════════════
    // CORE RECORD METHOD
    // ══════════════════════════════════════════════════════════

    /**
     * Catat satu aksi ke audit trail.
     *
     * @param  string      $tableName   Nama tabel yang diaudit (mis: 'data')
     * @param  int|string  $recordId    PK record yang diaudit
     * @param  string      $actionType  Konstanta AuditTrail::ACTION_*
     * @param  array|null  $oldValue    Snapshot nilai sebelum perubahan
     * @param  array|null  $newValue    Snapshot nilai sesudah perubahan
     * @param  string|null $reason      Alasan perubahan (wajib untuk aksi sensitif)
     * @param  int|null    $userId      Override user_id — default: user yang sedang login
     */
    public function record(
        string     $tableName,
        int|string $recordId,
        string     $actionType,
        ?array     $oldValue  = null,
        ?array     $newValue  = null,
        ?string    $reason    = null,
        ?int       $userId    = null,
    ): AuditTrail {
        return AuditTrail::record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: $actionType,
            oldValue:   $oldValue,
            newValue:   $newValue,
            userId:     $userId ?? $this->resolveUserId(),
            reason:     $reason,
            ipAddress:  $this->resolveIp(),
            userAgent:  $this->resolveUserAgent(),
        );
    }

    // ══════════════════════════════════════════════════════════
    // SHORTCUT METHODS — dipanggil dari controller/service lain
    // ══════════════════════════════════════════════════════════

    /**
     * Catat pembuatan record baru.
     */
    public function recordCreated(
        string     $tableName,
        int|string $recordId,
        array      $newValue,
        ?string    $reason  = null,
        ?int       $userId  = null,
    ): AuditTrail {
        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_CREATED,
            oldValue:   null,
            newValue:   $newValue,
            reason:     $reason,
            userId:     $userId,
        );
    }

    /**
     * Catat perubahan nilai field.
     * Hanya menyimpan field yang benar-benar berubah (diff).
     */
    public function recordUpdated(
        string     $tableName,
        int|string $recordId,
        array      $oldValue,
        array      $newValue,
        ?string    $reason  = null,
        ?int       $userId  = null,
    ): AuditTrail {
        // Simpan hanya field yang berubah agar audit trail ringkas
        $changedOld = [];
        $changedNew = [];

        foreach ($newValue as $key => $val) {
            $oldVal = $oldValue[$key] ?? null;
            // Cast ke string untuk perbandingan aman (decimal, integer, dll.)
            $hasChanged = is_array($oldVal) || is_array($val)
                ? $oldVal !== $val
                : (string) $oldVal !== (string) $val;
            if ($hasChanged) {
                $changedOld[$key] = $oldVal;
                $changedNew[$key] = $val;
            }
        }

        // Jika tidak ada yang berubah, tetap catat agar histori lengkap
        if (empty($changedNew)) {
            $changedOld = $oldValue;
            $changedNew = $newValue;
        }

        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_UPDATED,
            oldValue:   $changedOld,
            newValue:   $changedNew,
            reason:     $reason,
            userId:     $userId,
        );
    }

    /**
     * Catat approval data.
     */
    public function recordApproved(
        string     $tableName,
        int|string $recordId,
        ?string    $note   = null,
        ?int       $userId = null,
    ): AuditTrail {
        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_APPROVED,
            reason:     $note,
            userId:     $userId,
        );
    }

    /**
     * Catat penolakan data.
     */
    public function recordRejected(
        string     $tableName,
        int|string $recordId,
        string     $reason,
        ?int       $userId = null,
    ): AuditTrail {
        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_REJECTED,
            reason:     $reason,
            userId:     $userId,
        );
    }

    /**
     * Catat hasil screening otomatis oleh sistem.
     */
    public function recordScreened(
        string     $tableName,
        int|string $recordId,
        array      $screeningResult,
    ): AuditTrail {
        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_SCREENED,
            newValue:   $screeningResult,
            reason:     'Screening otomatis sistem',
            userId:     null, // sistem, bukan user
        );
    }

    /**
     * Catat import massal dari Excel.
     */
    public function recordImported(
        string $tableName,
        int    $recordId,
        array  $importedData,
        ?int   $userId = null,
    ): AuditTrail {
        return $this->record(
            tableName:  $tableName,
            recordId:   $recordId,
            actionType: AuditTrail::ACTION_IMPORTED,
            newValue:   $importedData,
            reason:     'Import dari file Excel',
            userId:     $userId,
        );
    }

    // ══════════════════════════════════════════════════════════
    // QUERY HELPERS — untuk halaman histori
    // ══════════════════════════════════════════════════════════

    /**
     * Ambil histori lengkap satu record.
     */
    public function getHistory(string $tableName, int|string $recordId): \Illuminate\Database\Eloquent\Collection
    {
        return AuditTrail::with('user')
            ->where('table_name', $tableName)
            ->where('record_id', (string) $recordId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Ambil semua aksi oleh satu user.
     */
    public function getByUser(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditTrail::where('id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Statistik audit trail untuk dashboard.
     * Mengembalikan jumlah aksi per tipe dalam rentang hari terakhir.
     */
    public function getDashboardStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        $counts = AuditTrail::where('created_at', '>=', $since)
            ->selectRaw('action_type, COUNT(*) as total')
            ->groupBy('action_type')
            ->pluck('total', 'action_type')
            ->toArray();

        return [
            'total'    => array_sum($counts),
            'by_type'  => $counts,
            'period'   => "{$days} hari terakhir",
        ];
    }

    // ══════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════

    private function resolveUserId(): ?int
    {
        try {
            return Auth::check() ? (int) Auth::user()->user_id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveIp(): ?string
    {
        try {
            return RequestFacade::ip();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveUserAgent(): ?string
    {
        try {
            return RequestFacade::userAgent();
        } catch (\Throwable) {
            return null;
        }
    }
}