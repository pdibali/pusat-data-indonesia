<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRequest extends Model
{
    protected $fillable = [
        'user_id', 'nama_data', 'location_id', 'deskripsi',
        'instansi_perkiraan', 'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function location() { return $this->belongsTo(Location::class, 'location_id', 'location_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by', 'user_id'); }
}