<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone_number',
        'license_number',
        'license_expiry_date',
        'address',
        'emergency_contact',
        'file_path',
        'assigned_truck',
        'status',
        'is_archived',
        'total_trips',
        'last_trip',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'last_trip' => 'date',
        'is_archived' => 'boolean',
    ];
}

