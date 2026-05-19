<?php
namespace App\Http\Controllers;
use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\ReportCompilation;
use App\Models\TripTicket;
use App\Models\Truck;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
class ReportController extends Controller
{
    use LogsActivity;
    public function index()
    {
        $totalRevenue         = TripTicket::where('status', 'Completed')->whereNotNull('amount')->sum('amount');
        $completedTripCount   = TripTicket::where('status', 'Completed')->count();
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');
        $taxRate = (float) config('app.trip_tax_rate', 0.12);
        if ($taxRate < 0) $taxRate = 0;
        $tripTax   = round($totalRevenue * $taxRate, 2);
        $netProfit = $totalRevenue - ($totalMaintenanceCost + $tripTax);
        $driverTripRecords = Driver::where('is_archived', false)
            ->withCount(['trips as total_trips_count'])
            ->withSum(['trips as total_revenue' => function ($q) {
                $q->where('status', 'Completed')->whereNotNull('amount');
            }], 'amount')
            ->orderByDesc('total_trips_count')
            ->limit(50)
            ->get();
        $truckCount         = Truck::count();
        $tripCount          = TripTicket::count();
        $maintenanceRecords = MaintenanceRecord::with('truck')->orderByDesc('created_at')->limit(50)->get();
        $compiledReports    = ReportCompilation::orderByDesc('compiled_at')->limit(20)->get();
        return view('reports', compact(
            'totalRevenue', 'completedTripCount', 'tripTax', 'taxRate',
            'totalMaintenanceCost', 'netProfit',
            'driverTripRecords', 'maintenanceRecords', 'compiledReports',
            'truckCount', 'tripCount'
        ));
    }
    public function compile(Request $request)
    {
        $totalRevenue         = TripTicket::where('status', 'Completed')->whereNotNull('amount')->sum('amount');
        $completedTripCount   = TripTicket::where('status', 'Completed')->count();
        $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');
        $taxRate = (float) config('app.trip_tax_rate', 0.12);
        if ($taxRate < 0) $taxRate = 0;
        $tripTax   = round($totalRevenue * $taxRate, 2);
        $netProfit = $totalRevenue - ($totalMaintenanceCost + $tripTax);
        $label = 'Compiled ' . now()->format('Y-m-d H:i');
        $compilation = ReportCompilation::create([
            'label'                   => $label,
            'completed_trip_count'    => $completedTripCount,
            'completed_trip_revenue'  => $totalRevenue,
            'tax_rate'                => $taxRate,
            'trip_tax'                => $tripTax,
            'driver_expenses'         => 0,
            'maintenance_cost'        => $totalMaintenanceCost,
            'net_profit'              => $netProfit,
            'meta'                    => [
                'truck_count'               => Truck::count(),
                'total_trip_count'          => TripTicket::count(),
                'maintenance_records_count' => MaintenanceRecord::count(),
            ],
            'compiled_at' => now(),
        ]);
        $this->writeLog(
            'compiled',
            'report_compilation',
            $compilation->id,
            $label,
            null,
            [
                'completed_trip_count'   => $completedTripCount,
                'completed_trip_revenue' => $totalRevenue,
                'trip_tax'               => $tripTax,
                'maintenance_cost'       => $totalMaintenanceCost,
                'net_profit'             => $netProfit,
            ],
            null,
            $request
        );
        return redirect()->route('reports.index')->with('success', 'Report compiled successfully.');
    }
    public function download(ReportCompilation $compilation)
    {
        $fileName = 'tax_report_' . optional($compilation->compiled_at)->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($compilation) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Field', 'Value']);
            fputcsv($out, ['Label',                  $compilation->label]);
            fputcsv($out, ['Compiled At',             optional($compilation->compiled_at)->format('Y-m-d H:i:s')]);
            fputcsv($out, ['Completed Trip Count',    $compilation->completed_trip_count]);
            fputcsv($out, ['Completed Trip Revenue',  $compilation->completed_trip_revenue]);
            fputcsv($out, ['PH VAT Rate',             round($compilation->tax_rate * 100, 2) . '%']);
            fputcsv($out, ['Trip Tax (VAT)',           $compilation->trip_tax]);
            fputcsv($out, ['Maintenance Cost',         $compilation->maintenance_cost]);
            fputcsv($out, ['Net Profit',               $compilation->net_profit]);
            if (is_array($compilation->meta) && !empty($compilation->meta)) {
                fputcsv($out, []);
                fputcsv($out, ['Meta Key', 'Meta Value']);
                foreach ($compilation->meta as $key => $value) {
                    fputcsv($out, [(string) $key, is_scalar($value) ? (string) $value : json_encode($value)]);
                }
            }
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function exportDriver(Driver $driver)
    {
        $driver->loadCount(['trips as total_trips_count']);
        $driver->loadSum(['trips as total_revenue' => function ($q) {
            $q->where('status', 'Completed')->whereNotNull('amount');
        }], 'amount');
        $driver->load(['trips' => function ($q) {
            $q->with('truck')->orderByDesc('created_at')->limit(100);
        }]);

        $referenceNo = 'DR-' . strtoupper(substr(md5($driver->id . now()->format('Ymd')), 0, 8));

        $this->writeLog(
            'exported',
            'driver',
            $driver->id,
            $driver->full_name,
            null,
            null,
            "Driver report exported (Ref: {$referenceNo})"
        );

        return view('exports.driver-report', compact('driver', 'referenceNo'));
    }

    public function exportMaintenance(Request $request)
    {
        $statuses = $request->input('statuses', ['pending','inprogress','completed','cancelled']);

        $statusMap = [
            'pending'     => 'Pending',
            'inprogress'  => 'In-Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
        ];

        $mappedStatuses = collect($statuses)
            ->map(fn($s) => $statusMap[$s] ?? null)
            ->filter()
            ->values()
            ->toArray();

        $records = MaintenanceRecord::with('truck')
            ->whereIn('status', $mappedStatuses)
            ->orderByDesc('start_date')
            ->get();

        $totalCost   = $records->whereNotNull('cost')->sum('cost');
        $referenceNo = 'MR-' . strtoupper(substr(md5(now()->format('YmdHi')), 0, 8));

        $this->writeLog(
            'exported',
            'maintenance_record',
            null,
            null,
            null,
            null,
            "Maintenance report exported — " . implode(', ', $mappedStatuses) . " (Ref: {$referenceNo})",
            $request
        );

        return view('exports.maintenance-report', compact('records', 'totalCost', 'referenceNo'));
    }
}