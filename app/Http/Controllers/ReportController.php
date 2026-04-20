<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\ReportCompilation;
use App\Models\TripTicket;
use App\Models\Truck;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Philippines VAT-based reporting from COMPLETED trips.
        $totalRevenue = TripTicket::where('status', 'Completed')->whereNotNull('amount')->sum('amount');
        $completedTripCount = TripTicket::where('status', 'Completed')->count();
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');

        // MVP placeholders: you can expand later to true expense/profit logic.
        $driverExpenses = 0;

        // PH VAT default 12%.
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

        $compiledReports = ReportCompilation::orderByDesc('compiled_at')
            ->limit(20)
            ->get();

        return view('reports', compact(
            'totalRevenue',
            'completedTripCount',
            'tripTax',
            'taxRate',
            'driverExpenses',
            'totalMaintenanceCost',
            'netProfit',
            'driverTripRecords',
            'maintenanceRecords',
            'compiledReports',
            'truckCount',
            'tripCount'
        ));
    }

    public function compile(Request $request)
    {
        $totalRevenue = TripTicket::where('status', 'Completed')->whereNotNull('amount')->sum('amount');
        $completedTripCount = TripTicket::where('status', 'Completed')->count();
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');
        $driverExpenses = 0;

        $taxRate = (float) config('app.trip_tax_rate', 0.12);
        if ($taxRate < 0) {
            $taxRate = 0;
        }

        $tripTax = round($totalRevenue * $taxRate, 2);
        $netProfit = $totalRevenue - ($driverExpenses + $totalMaintenanceCost + $tripTax);

        ReportCompilation::create([
            'label' => 'Compiled ' . now()->format('Y-m-d H:i'),
            'completed_trip_count' => $completedTripCount,
            'completed_trip_revenue' => $totalRevenue,
            'tax_rate' => $taxRate,
            'trip_tax' => $tripTax,
            'driver_expenses' => $driverExpenses,
            'maintenance_cost' => $totalMaintenanceCost,
            'net_profit' => $netProfit,
            'meta' => [
                'truck_count' => Truck::count(),
                'total_trip_count' => TripTicket::count(),
                'maintenance_records_count' => MaintenanceRecord::count(),
            ],
            'compiled_at' => now(),
        ]);

        return redirect()->route('reports.index')->with('success', 'Report compiled successfully.');
    }

    public function download(ReportCompilation $compilation)
    {
        $fileName = 'tax_report_' . optional($compilation->compiled_at)->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($compilation) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Field', 'Value']);
            fputcsv($out, ['Label', $compilation->label]);
            fputcsv($out, ['Compiled At', optional($compilation->compiled_at)->format('Y-m-d H:i:s')]);
            fputcsv($out, ['Completed Trip Count', $compilation->completed_trip_count]);
            fputcsv($out, ['Completed Trip Revenue', $compilation->completed_trip_revenue]);
            fputcsv($out, ['PH VAT Rate', round($compilation->tax_rate * 100, 2) . '%']);
            fputcsv($out, ['Trip Tax (VAT)', $compilation->trip_tax]);
            fputcsv($out, ['Driver Expenses', $compilation->driver_expenses]);
            fputcsv($out, ['Maintenance Cost', $compilation->maintenance_cost]);
            fputcsv($out, ['Net Profit', $compilation->net_profit]);

            if (is_array($compilation->meta) && !empty($compilation->meta)) {
                fputcsv($out, []); // spacing row
                fputcsv($out, ['Meta Key', 'Meta Value']);
                foreach ($compilation->meta as $key => $value) {
                    fputcsv($out, [(string) $key, is_scalar($value) ? (string) $value : json_encode($value)]);
                }
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
