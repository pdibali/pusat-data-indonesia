<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $table = 'user_activity_logs';
    protected $primaryKey = 'user_activity_log_id';
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'method',
        'route_name',
        'request_path',
        'status_code',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
