<?php
namespace App\Http\Controllers;
use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\ReportCompilation;
use App\Models\TripTicket;
use App\Models\Truck;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    use LogsActivity;

    public function index()
    {
        try {
            $totalRevenue         = TripTicket::where('status', 'Completed')->whereNotNull('amount')->sum('amount');
            $completedTripCount   = TripTicket::where('status', 'Completed')->count();
            $totalMaintenanceCost = MaintenanceRecord::whereNotNull('cost')->sum('cost');
            $taxRate = (float) config('app.trip_tax_rate', 0.12);
            if ($taxRate < 0) $taxRate = 0;
            $tripTax   = round($totalRevenue * $taxRate, 2);
            $netProfit = $totalRevenue - ($totalMaintenanceCost + $tripTax);
            $driverTripRecords = Driver::where('is_archived', false)
                ->withCount(['trips as total_trips_count' => function ($q) {
                    $q->where('status', 'Completed');
                }])
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
        } catch (\Exception $e) {
            Log::error('Failed to load reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'We couldn\'t load the reports page. Please refresh and try again.']);
        }
    }

    public function compile(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to compile report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('reports.index')->with('error', 'We couldn\'t compile the report right now. Please try again in a moment.');
        }
    }

    public function download(ReportCompilation $compilation)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to download report compilation', [
                'compilation_id' => $compilation->id,
                'error'          => $e->getMessage(),
            ]);
            return back()->with('error', 'We couldn\'t generate the download right now. Please try again.');
        }
    }

    public function exportDriver(Driver $driver)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to export driver report', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
            return back()->with('error', 'We couldn\'t export this driver\'s report right now. Please try again.');
        }
    }

    public function exportMaintenance(Request $request)
    {
        try {
            $statuses = $request->input('statuses', ['pending', 'inprogress', 'completed', 'cancelled']);
            $statusMap = [
                'pending'    => 'Pending',
                'inprogress' => 'In-Progress',
                'completed'  => 'Completed',
                'cancelled'  => 'Cancelled',
            ];
            $mappedStatuses = collect($statuses)
                ->map(fn($s) => $statusMap[$s] ?? null)
                ->filter()
                ->values()
                ->toArray();
            if (empty($mappedStatuses)) {
                return back()->with('error', 'No valid statuses selected. Please select at least one status to export.');
            }
            $records     = MaintenanceRecord::with('truck')->whereIn('status', $mappedStatuses)->orderByDesc('start_date')->get();
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
        } catch (\Exception $e) {
            Log::error('Failed to export maintenance report', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'We couldn\'t export the maintenance report right now. Please try again.');
        }
    }

    public function driverInfo(Driver $driver)
    {
        try {
            $driver->loadCount(['trips as total_trips_count']);
            $driver->loadSum(['trips as total_revenue' => function ($q) {
                $q->where('status', 'Completed')->whereNotNull('amount');
            }], 'amount');
            $driver->load(['trips' => function ($q) {
                $q->with('truck')->orderByDesc('created_at')->limit(100);
            }]);
            $referenceNo = 'DR-' . strtoupper(substr(md5($driver->id . now()->format('Ymd')), 0, 8));
            return response()->json(array_merge(
                $driver->toArray(),
                ['ref_no' => $referenceNo]
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load driver info panel', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'We couldn\'t load this driver\'s information right now. Please try again.',
            ], 500);
        }
    }

    public function searchDrivers(Request $request)
    {
        try {
            $query = trim($request->get('q', ''));
            if ($query === '') {
                return response()->json([
                    'message' => 'Please enter a driver name or license number to search.',
                ], 422);
            }
            if (strlen($query) > 100) {
                return response()->json([
                    'message' => 'Search query is too long. Please shorten it and try again.',
                ], 422);
            }
            $drivers = Driver::where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->where('first_name',     'like', "%{$query}%")
                      ->orWhere('last_name',     'like', "%{$query}%")
                      ->orWhere('full_name',     'like', "%{$query}%")
                      ->orWhere('license_number','like', "%{$query}%")
                      ->orWhere('phone_number',  'like', "%{$query}%");
                })
                ->withCount(['trips as total_trips_count' => function ($q) {
                    $q->where('status', 'Completed');
                }])
                ->orderBy('first_name')
                ->get();
            if ($drivers->isEmpty()) {
                return response()->json([
                    'message' => "No drivers found matching \"{$query}\". Try a different name or license number.",
                    'data'    => [],
                ], 404);
            }
            return response()->json(['data' => $drivers]);
        } catch (\Exception $e) {
            Log::error('Driver search failed', [
                'query' => $request->get('q'),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }

    public function searchMaintenance(Request $request)
    {
        try {
            $query    = trim($request->get('q', ''));
            $statuses = $request->query('statuses', []);

            if ($query === '' && empty($statuses)) {
                return response()->json([
                    'message' => 'Please enter a keyword or select at least one status filter.',
                ], 422);
            }
            if (strlen($query) > 100) {
                return response()->json([
                    'message' => 'Search query is too long. Please shorten it and try again.',
                ], 422);
            }

            $statusMap = [
                'pending'    => 'Pending',
                'inprogress' => 'In-Progress',
                'completed'  => 'Completed',
                'cancelled'  => 'Cancelled',
            ];
            $mappedStatuses = collect($statuses)
                ->map(fn($s) => $statusMap[$s] ?? null)
                ->filter()
                ->values()
                ->toArray();

            $validStatuses = array_values($statusMap);
            if (!empty($statuses)) {
                $invalid = array_diff($statuses, array_keys($statusMap));
                if (!empty($invalid)) {
                    return response()->json([
                        'message' => 'One or more selected filters are invalid. Please refresh and try again.',
                        'errors'  => ['statuses' => ['Allowed values: pending, inprogress, completed, cancelled.']],
                    ], 422);
                }
            }

            $records = MaintenanceRecord::with('truck')
                ->when($query !== '', function ($q) use ($query) {
                    $q->where(function ($q2) use ($query) {
                        $q2->where('issue_description', 'like', "%{$query}%")
                           ->orWhere('notes',           'like', "%{$query}%")
                           ->orWhereHas('truck', fn($t) => $t->where('truck_code', 'like', "%{$query}%"));
                    });
                })
                ->when(!empty($mappedStatuses), fn($q) => $q->whereIn('status', $mappedStatuses))
                ->orderByDesc('start_date')
                ->get();

            if ($records->isEmpty()) {
                $statusLabel = !empty($mappedStatuses) ? implode(', ', $mappedStatuses) : null;
                $message = $query !== '' && $statusLabel
                    ? "No maintenance records match \"{$query}\" with status: {$statusLabel}."
                    : ($query !== ''
                        ? "No maintenance records found matching \"{$query}\". Try a different keyword."
                        : "No maintenance records found with status: {$statusLabel}.");
                return response()->json([
                    'message' => $message,
                    'data'    => [],
                ], 404);
            }

            return response()->json(['data' => $records]);
        } catch (\Exception $e) {
            Log::error('Maintenance search failed', [
                'query'    => $request->get('q'),
                'statuses' => $request->get('statuses'),
                'error'    => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }
}