<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\TripTicket;
use App\Models\Truck;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTrucks = Truck::count();
        $totalDrivers = Driver::where('is_archived', false)->count();
        $availableTrucks = Truck::where('status', 'Available')->count();
        $availableDrivers = Driver::where('is_archived', false)->where('status', 'Available')->count();
        $pendingMaintenance = MaintenanceRecord::where('status', 'Pending')->count();

        $activeTrips = TripTicket::with(['truck', 'driver'])
            ->where('status', 'In-Transit')
            ->orderByDesc('departure_time')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalTrucks',
            'totalDrivers',
            'availableTrucks',
            'availableDrivers',
            'pendingMaintenance',
            'activeTrips'
        ));
    }
}
