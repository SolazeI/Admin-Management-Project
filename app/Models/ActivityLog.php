<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'performed_by',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'logged_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->logged_at)) {
                $model->logged_at = now();
            }
        });
    }
}