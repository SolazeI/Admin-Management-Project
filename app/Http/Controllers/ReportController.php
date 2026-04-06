<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\TripTicket;
use App\Models\Truck;

class ReportController extends Controller
{
    public function index()
    {
        $totalRevenue = TripTicket::whereNotNull('amount')->sum('amount');
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');

        // MVP placeholders: you can expand later to true expense/profit logic.
        $driverExpenses = 0;
        $netProfit = $totalRevenue - ($driverExpenses + $totalMaintenanceCost);

        $driverTripRecords = Driver::where('is_archived', false)
            ->withCount(['trips as total_trips_count'])
            ->orderByDesc('total_trips_count')
            ->limit(50)
            ->get();

        $truckCount = Truck::count();
        $tripCount = TripTicket::count();

        return view('reports', compact(
            'totalRevenue',
            'driverExpenses',
            'totalMaintenanceCost',
            'netProfit',
            'driverTripRecords',
            'truckCount',
            'tripCount'
        ));
    }
}
