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
        // Revenue is based on trip ticket amounts (MVP).
        // If you only want completed trips to count, change this to ->where('status', 'Completed').
        $totalRevenue = TripTicket::whereNotNull('amount')->sum('amount');
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');

        // MVP placeholders: you can expand later to true expense/profit logic.
        $driverExpenses = 0;

        // Tax (MVP): configurable rate; defaults to 12% (PH VAT-style).
        $taxRate = (float) config('app.trip_tax_rate', 0.12);
        if ($taxRate < 0) {
            $taxRate = 0;
        }
        $tripTax = round($totalRevenue * $taxRate, 2);

        $netProfit = $totalRevenue - ($driverExpenses + $totalMaintenanceCost + $tripTax);

        $driverTripRecords = Driver::where('is_archived', false)
            ->withCount(['trips as total_trips_count'])
            ->orderByDesc('total_trips_count')
            ->limit(50)
            ->get();

        $truckCount = Truck::count();
        $tripCount = TripTicket::count();

        $maintenanceRecords = MaintenanceRecord::with('truck')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('reports', compact(
            'totalRevenue',
            'tripTax',
            'taxRate',
            'driverExpenses',
            'totalMaintenanceCost',
            'netProfit',
            'driverTripRecords',
            'maintenanceRecords',
            'truckCount',
            'tripCount'
        ));
    }
}
