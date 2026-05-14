<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_no',
        'driver_id',
        'truck_id',
        'date_issued',
        'origin',
        'destination',
        'departure_time',
        'arrival_time',
        'distance_km',
        'amount',
        'status',
        'remarks',
        'is_archived',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
