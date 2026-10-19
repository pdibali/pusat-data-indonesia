<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Data extends Model
{
    protected $table      = 'data';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    // ─── STATUS CONSTANTS (verifikasi admin lama) ─────────────
    const STATUS_PENDING   = 0;
    const STATUS_AVAILABLE = 1;
    const STATUS_REJECTED  = 2;

    // ─── WORKFLOW STATUS CONSTANTS ────────────────────────────
    const WORKFLOW_DRAFT             = 'draft';
    const WORKFLOW_WARNING           = 'warning';
    const WORKFLOW_UNDER_REVIEW      = 'under_review';
    const WORKFLOW_APPROVED          = 'approved';
    const WORKFLOW_APPROVED_WITH_NOTE= 'approved_with_note';
    const WORKFLOW_REJECTED          = 'rejected';
    const WORKFLOW_REVISED           = 'revised';

    protected $fillable = [
        'user_id',
        'metadata_id',
        'location_id',
        'rujukan_id',
        'time_id',
        'number_value',
        'status',
        'date_inputed',
        'workflow_status',
        'reviewer_note',
        'reviewed_by',
        'reviewed_at',
        'produsen_id',
        'satuan_id',
        'satuan_asal_id',
    ];

    protected $casts = [
        'date_inputed'  => 'datetime',
        'reviewed_at'   => 'datetime',
        'number_value'  => 'decimal:2',
    ];

    // ─── RELASI (existing) ────────────────────────────────────

    public function metadata(): BelongsTo
    {
        return $this->belongsTo(Metadata::class, 'metadata_id', 'metadata_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function time(): BelongsTo
    {
        return $this->belongsTo(Waktu::class, 'time_id', 'time_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function rujukan(): BelongsTo
    {
        return $this->belongsTo(Rujukan::class, 'rujukan_id', 'rujukan_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'satuan_id');
    }

    public function satuanAsal(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_asal_id', 'satuan_id');
    }

    // ─── RELASI BARU (data quality) ───────────────────────────

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class, 'id', 'id')
                    ->orderBy('detected_at', 'desc');
    }

    public function latestAnomaly(): HasOne
    {
        return $this->hasOne(Anomaly::class, 'id', 'id')
                    ->latest('detected_at');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function produsen(): BelongsTo
    {
        return $this->belongsTo(ProdusenData::class, 'produsen_id', 'produsen_id');
    }

    public function produsens(): BelongsToMany
    {
        return $this->belongsToMany(ProdusenData::class, 'data_produsen', 'data_id', 'produsen_id')
            ->orderByPivot('produsen_id');
    }

    public function getProdusenDisplayAttribute(): string
    {
        $names = $this->produsens->pluck('nama_produsen');
        if ($names->isEmpty() && $this->produsen) {
            $names->push($this->produsen->nama_produsen);
        }

        return $names->implode('; ');
    }

    // ─── SCOPES ───────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeWithWorkflow($query, string $workflowStatus)
    {
        return $query->where('workflow_status', $workflowStatus);
    }

    public function scopeHasAnomaly($query)
    {
        return $query->whereIn('workflow_status', [
            self::WORKFLOW_WARNING,
            self::WORKFLOW_UNDER_REVIEW,
        ]);
    }

    public function scopeNeedsReview($query)
    {
        return $query->whereIn('workflow_status', [
            self::WORKFLOW_WARNING,
            self::WORKFLOW_UNDER_REVIEW,
        ]);
    }

    // ─── ACCESSORS ────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_REJECTED  => 'Ditolak',
            default                => 'Pending',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_REJECTED  => 'red',
            default                => 'yellow',
        };
    }

    public function getWorkflowStatusLabelAttribute(): string
    {
        return match ($this->workflow_status) {
            self::WORKFLOW_DRAFT             => 'Draft',
            self::WORKFLOW_WARNING           => 'Warning',
            self::WORKFLOW_UNDER_REVIEW      => 'Under Review',
            self::WORKFLOW_APPROVED          => 'Approved',
            self::WORKFLOW_APPROVED_WITH_NOTE=> 'Approved with Note',
            self::WORKFLOW_REJECTED          => 'Rejected',
            self::WORKFLOW_REVISED           => 'Revised',
            default                          => ucfirst(str_replace('_', ' ', $this->workflow_status ?? 'draft')),
        };
    }

    public function getWorkflowStatusStyleAttribute(): string
    {
        return match ($this->workflow_status) {
            self::WORKFLOW_DRAFT             => 'background:#f3f4f6; color:#6b7280;',
            self::WORKFLOW_WARNING           => 'background:#fef9c3; color:#a16207;',
            self::WORKFLOW_UNDER_REVIEW      => 'background:#e0f2fe; color:#0369a1;',
            self::WORKFLOW_APPROVED          => 'background:#dcfce7; color:#15803d;',
            self::WORKFLOW_APPROVED_WITH_NOTE=> 'background:#d1fae5; color:#065f46;',
            self::WORKFLOW_REJECTED          => 'background:#fee2e2; color:#b91c1c;',
            self::WORKFLOW_REVISED           => 'background:#f3e8ff; color:#6d28d9;',
            default                          => 'background:#f3f4f6; color:#374151;',
        };
    }

    // ─── HELPERS ──────────────────────────────────────────────

    public function hasActiveAnomaly(): bool
    {
        return in_array($this->workflow_status, [
            self::WORKFLOW_WARNING,
            self::WORKFLOW_UNDER_REVIEW,
        ]);
    }

    public function isApproved(): bool
    {
        return in_array($this->workflow_status, [
            self::WORKFLOW_APPROVED,
            self::WORKFLOW_APPROVED_WITH_NOTE,
        ]);
    }

    /**
     * Semua opsi workflow status untuk dropdown/filter
     */
    public static function workflowStatusOptions(): array
    {
        return [
            self::WORKFLOW_DRAFT             => 'Draft',
            self::WORKFLOW_WARNING           => 'Warning',
            self::WORKFLOW_UNDER_REVIEW      => 'Under Review',
            self::WORKFLOW_APPROVED          => 'Approved',
            self::WORKFLOW_APPROVED_WITH_NOTE=> 'Approved with Note',
            self::WORKFLOW_REJECTED          => 'Rejected',
            self::WORKFLOW_REVISED           => 'Revised',
        ];
    }
}