<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\TripTicket;
use App\Models\Truck;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTrucks        = Truck::count();
        $totalDrivers       = Driver::where('is_archived', false)->count();
        $availableTrucks    = Truck::where('status', 'Available')->count();
        $availableDrivers   = Driver::where('is_archived', false)->where('status', 'Available')->count();
        $pendingMaintenance = MaintenanceRecord::where('status', 'Pending')->count();

        $activeTrips = TripTicket::with(['truck', 'driver'])
            ->where('status', 'In-Transit')
            ->orderByDesc('departure_time')
            ->limit(5)
            ->get();

        $revenueData = $this->getMonthlyRevenue(12);

        return view('dashboard', compact(
            'totalTrucks',
            'totalDrivers',
            'availableTrucks',
            'availableDrivers',
            'pendingMaintenance',
            'activeTrips',
            'revenueData'
        ));
    }

    private function getMonthlyRevenue(int $months = 12): array
    {
        $labels  = [];
        $revenue = [];
        $profit  = [];

        $taxRate = (float) config('app.trip_tax_rate', 0.12);

        for ($i = $months - 1; $i >= 0; $i--) {
            $date  = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end   = $date->copy()->endOfMonth();

            // Mirror ReportController: status=Completed + whereNotNull(amount)
            $monthRevenue = TripTicket::where('status', 'Completed')
                ->whereNotNull('amount')
                ->whereBetween('updated_at', [$start, $end])
                ->sum('amount') ?? 0;

            $maintenanceCost = MaintenanceRecord::whereNotNull('cost')
                ->whereBetween('created_at', [$start, $end])
                ->sum('cost') ?? 0;

            $tripTax = round((float) $monthRevenue * $taxRate, 2);
            $netProfit = max(0, (float) $monthRevenue - $maintenanceCost - $tripTax);

            $labels[]  = $date->format('M');
            $revenue[] = (float) $monthRevenue;
            $profit[]  = (float) $netProfit;
        }

        return compact('labels', 'revenue', 'profit');
    }
}