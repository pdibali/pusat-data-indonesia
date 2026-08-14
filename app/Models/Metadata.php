<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Metadata extends Model
{
    protected $table      = 'metadata';
    protected $primaryKey = 'metadata_id';
    public $timestamps    = false;
    const STATUS_PENDING  = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    protected $fillable = [
        'nama',
        'sub_nama_metadata',
        'alias',
        'konsep',
        'definisi',
        'klasifikasi_id',
        'asumsi',
        'metodologi',
        'penjelasan_metodologi',
        'tipe_data',
        'satuan_data',
        'tahun_mulai_data',
        'frekuensi_penerbitan',
        'tahun_pertama_rilis',
        'bulan_pertama_rilis',
        'tanggal_rilis',
        'sumber_metadata_pertama',
        'tahun_metadata',
        'tag',
        'flag_desimal',
        'tipe_group',
        'group_by',
        'status',
        'is_free',                 // ← tambah
        'tahun_data_tersedia',     // ← tambah
        'date_inputed',
        'user_id',
    ];

    protected $casts = [
        'date_inputed'        => 'datetime',
        'bulan_pertama_rilis' => 'integer',
        'tanggal_rilis'       => 'integer',
        'tahun_pertama_rilis' => 'integer',
        'tipe_group'          => 'integer',
        'flag_desimal'        => 'integer',
        'status'              => 'integer',
        'klasifikasi_id'      => 'integer',
        'sumber_metadata_pertama'         => 'integer',
        'tahun_metadata'        => 'integer',
        'group_by'            => 'integer',
        'user_id'             => 'integer',
        'is_free'             => 'boolean',
        'sub_nama_metadata' => 'array',
    ];

    // ── RELASI ────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'klasifikasi_id', 'klasifikasi_id');
    }

    public function produsen()
    {
        return $this->belongsTo(ProdusenData::class, 'sumber_metadata_pertama', 'produsen_id');
    }

    public function groupParent()
    {
        return $this->belongsTo(Metadata::class, 'group_by', 'metadata_id');
    }

    public function groupChildren()
    {
        return $this->hasMany(Metadata::class, 'group_by', 'metadata_id');
    }

    public function data()
    {
        return $this->hasMany(Data::class, 'metadata_id', 'metadata_id');
    }

    // ── SCOPES ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    // ── HELPERS ───────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            default               => 'Pending',
        };
    }

    public function getStatusStyleAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'background:#dcfce7; color:#15803d;',
            self::STATUS_INACTIVE => 'background:#f3f4f6; color:#6b7280;',
            default               => 'background:#fef3c7; color:#b45309;',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // tambahkan helper accessor:
    /**
     * Ambil label sub-nama metadata untuk satuan tertentu.
     * Fallback ke nama metadata biasa jika belum ada sub-nama untuk satuan itu.
     */
    public function getSubNamaForSatuan(int $satuanId): string
    {
        $subs = $this->sub_nama_metadata ?? [];
        return $subs[$satuanId] ?? $this->nama;
    }

    /**
     * Set/update label sub-nama metadata untuk satuan tertentu.
     */
    public function setSubNamaForSatuan(int $satuanId, string $label): void
    {
        $subs = $this->sub_nama_metadata ?? [];
        $subs[$satuanId] = $label;
        $this->sub_nama_metadata = $subs;
        $this->save();
    }

    // ── COMPUTED: Tahun Mulai Data & Tahun Data Tersedia ──────

    /**
     * Tahun paling awal yang memiliki data (single year).
     * Contoh: 2021
     * Akses via: $metadata->tahun_mulai
     */
    public function getTahunMulaiAttribute(): ?int
    {
        $min = DB::table('data')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->where('data.metadata_id', $this->metadata_id)
            ->where('data.status', 1)
            ->whereNotNull('data.number_value')
            ->min('time.year');

        return $min ? (int) $min : null;
    }

    public function getTahunDataTersediaAttribute(): ?string
    {
        // Kalau kolom sudah terisi, pakai itu
        if (!empty($this->attributes['tahun_data_tersedia'])) {
            return $this->attributes['tahun_data_tersedia'];
        }

        // Fallback: hitung dari data (untuk data lama yang belum diisi)
        $result = DB::table('data')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->where('data.metadata_id', $this->metadata_id)
            ->where('data.status', 1)
            ->whereNotNull('data.number_value')
            ->selectRaw('MIN(time.year) as min_year, MAX(time.year) as max_year')
            ->first();

        if (!$result || !$result->min_year) return null;

        return $result->min_year === $result->max_year
            ? (string) $result->min_year
            : $result->min_year . '-' . $result->max_year;
    }
}