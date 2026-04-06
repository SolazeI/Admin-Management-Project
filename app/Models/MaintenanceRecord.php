<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_id',
        'issue_description',
        'start_date',
        'status',
        'notes',
        'cost',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
