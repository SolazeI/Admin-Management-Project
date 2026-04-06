<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_code',
        'plate_number',
        'model',
        'status',
        'notes',
    ];

    public function tripTickets()
    {
        return $this->hasMany(TripTicket::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
