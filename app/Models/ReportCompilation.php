<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCompilation extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'completed_trip_count',
        'completed_trip_revenue',
        'tax_rate',
        'trip_tax',
        'driver_expenses',
        'maintenance_cost',
        'net_profit',
        'meta',
        'compiled_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'compiled_at' => 'datetime',
    ];
}

