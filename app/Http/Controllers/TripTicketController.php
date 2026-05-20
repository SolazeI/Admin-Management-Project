<?php
namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\TripTicket;
use App\Models\Truck;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TripTicketController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    /** Maintenance statuses that make a truck ineligible for a trip ticket. */
    private const BLOCKING_MAINTENANCE_STATUSES = ['Pending', 'In-Progress'];


    private function assertDriverNotArchived(int $driverId): ?JsonResponse
    {
        $driver = Driver::find($driverId);

        if ($driver && $driver->is_archived) {
            return response()->json([
                'message' => "This driver has been archived and cannot be assigned to a trip ticket.",
                'errors'  => ['driver_id' => ["This driver has been archived and cannot be assigned to a trip ticket."]],
            ], 422);
        }

        return null;
    }
    // -------------------------------------------------------------------------
    // Public endpoints
    // -------------------------------------------------------------------------

    public function index()
    {
        try {
            $drivers = Driver::where('is_archived', false)->where('status', 'Available')->orderBy('full_name')->get();
            $trucks  = Truck::where('status', 'Available')->orderBy('truck_code')->get();
            $allDrivers = Driver::where('is_archived', false)->orderBy('full_name')->get();
            $allTrucks  = Truck::orderBy('truck_code')->get();
            $tripTickets = TripTicket::with(['driver', 'truck'])
                ->where('is_archived', false)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
            $archivedTripTickets = TripTicket::with(['driver', 'truck'])
                ->where('is_archived', true)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            return view('trips', compact('drivers', 'trucks', 'allDrivers', 'allTrucks', 'tripTickets', 'archivedTripTickets'));
        } catch (\Exception $e) {
            Log::error('Failed to load trip tickets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // index() renders a Blade view — return a view-level error, not JSON
            return back()->withErrors(['error' => 'Unable to load trip tickets. Please try again.']);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'trip_no'        => 'nullable|string|max:50|unique:trip_tickets,trip_no',
                'driver_id'      => 'required|exists:drivers,id',
                'truck_id'       => 'required|exists:trucks,id',
                'date_issued'    => 'nullable|date',
                'origin'         => 'nullable|string|max:255',
                'destination'    => 'nullable|string|max:255',
                'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
                'arrival_time'   => 'nullable|date_format:Y-m-d\TH:i',
                'distance_km'    => 'nullable|numeric|min:0',
                'amount'         => 'nullable|numeric|min:0',
                'remarks'        => 'nullable|string',
            ]);

            // Cross-module gate: block if truck is under active maintenance
            if ($error = $this->assertTruckNotUnderMaintenance($validated['truck_id'])) {
                return $error; // 422
            }

            if ($error = $this->assertDriverNotArchived($validated['driver_id'])) {
                return $error;
            }

            $truck = Truck::find($validated['truck_id']);
            if ($truck && $truck->status !== 'Available') {
                return response()->json([
                    'message' => "This truck is not available ({$truck->status}).",
                    'errors'  => ['truck_id' => ["This truck is not available ({$truck->status})."]],
                ], 422);
            }

            $driver = Driver::find($validated['driver_id']);
            if ($driver && $driver->status !== 'Available') {
                return response()->json([
                    'message' => "This driver is not available ({$driver->status}).",
                    'errors'  => ['driver_id' => ["This driver is not available ({$driver->status})."]],
                ], 422);
            }

            $validated           = $this->normalizeDateTimes($validated);
            $validated['status'] = 'Draft';

            if (empty($validated['trip_no'])) {
                $validated['trip_no'] = 'TRIP-' . strtoupper(Str::random(6));
            }

            $trip = TripTicket::create($validated);
            $trip->load('driver');
            $this->syncStatusesAfterTripChange($trip);

            $this->writeLog('created', 'trip_ticket', $trip->id, $this->tripLabel($trip), null, $this->modelSnapshot($trip), null, $request);

            return response()->json($trip->load(['driver', 'truck']), 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create trip ticket', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while saving the trip ticket. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $trip        = TripTicket::with('driver')->findOrFail($id); // throws ModelNotFoundException → 404
            $oldSnapshot = $this->modelSnapshot($trip);

            $validated = $request->validate([
                'trip_no'        => 'required|string|max:50|unique:trip_tickets,trip_no,' . $trip->id,
                'driver_id'      => 'required|exists:drivers,id',
                'truck_id'       => 'required|exists:trucks,id',
                'date_issued'    => 'nullable|date',
                'origin'         => 'nullable|string|max:255',
                'destination'    => 'nullable|string|max:255',
                'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
                'arrival_time'   => 'nullable|date_format:Y-m-d\TH:i',
                'distance_km'    => 'nullable|numeric|min:0',
                'amount'         => 'nullable|numeric|min:0',
                'remarks'        => 'nullable|string',
            ]);

            // Only re-check cross-module constraint if the truck is being swapped
            if ((int) $validated['truck_id'] !== (int) $trip->truck_id) {
                if ($error = $this->assertTruckNotUnderMaintenance($validated['truck_id'])) {
                    return $error; // 422
                }
            }

            if ((int) $validated['driver_id'] !== (int) $trip->driver_id) {
                if ($error = $this->assertDriverNotArchived($validated['driver_id'])) {
                    return $error;
                }
            }

            $validated           = $this->normalizeDateTimes($validated);
            $oldDriverId         = $trip->driver_id;
            $oldTruckId          = $trip->truck_id;
            $oldStatus           = $trip->status;
            $validated['status'] = $trip->status;

            $trip->update($validated);
            $trip->load('driver');
            $this->syncStatusesAfterTripChange($trip, $oldDriverId, $oldTruckId, $oldStatus);

            $this->writeLog('updated', 'trip_ticket', $trip->id, $this->tripLabel($trip), $oldSnapshot, $this->modelSnapshot($trip->fresh()), null, $request);

            return response()->json($trip->load(['driver', 'truck']), 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip ticket not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update trip ticket', [
                'trip_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while updating the trip ticket. Please try again.',
            ], 500);
        }
    }

//----------Search/Filter----------------

    public function search(Request $request): JsonResponse
    {
        try {
            $query = trim($request->get('q', ''));

            if ($query === '') {
                return response()->json(['message' => 'Search query cannot be empty.'], 422);
            }

            $trips = TripTicket::with(['driver', 'truck'])
                ->where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->where('trip_no', 'like', "%{$query}%")
                    ->orWhereHas('driver', fn($d) => $d->where('full_name', 'like', "%{$query}%"))
                    ->orWhereHas('truck',  fn($t) => $t->where('truck_code',  'like', "%{$query}%"))
                    ->orWhere('destination', 'like', "%{$query}%")
                    ->orWhere('origin',      'like', "%{$query}%");
                })
                ->orderByDesc('created_at')
                ->get();

            if ($trips->isEmpty()) {
                return response()->json([
                    'message' => "No trip tickets found matching \"{$query}\".",
                    'data'    => [],
                ], 404);
            }

            return response()->json(['data' => $trips], 200);

        } catch (\Exception $e) {
            Log::error('Trip search failed', [
                'query' => $request->get('q'),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }

    public function filterByStatus(Request $request): JsonResponse
    {
        try {
            $statuses = $request->query('statuses', []);
            $validStatuses = ['Draft', 'In-Transit', 'Completed', 'Cancelled'];

            if (!empty($statuses)) {
                $invalid = array_diff($statuses, $validStatuses);
                if (!empty($invalid)) {
                    return response()->json([
                        'message' => 'Invalid status value(s) provided.',
                        'errors'  => ['statuses' => ['Allowed values: Draft, In-Transit, Completed, Cancelled.']],
                    ], 422);
                }
            }

            $trips = TripTicket::with(['driver', 'truck'])
                ->where('is_archived', false)
                ->when(!empty($statuses), fn($q) => $q->whereIn('status', $statuses))
                ->orderByDesc('created_at')
                ->get();

            if ($trips->isEmpty()) {
                $label = !empty($statuses) ? implode(', ', $statuses) : 'any status';
                return response()->json([
                    'message' => "No trip tickets found for status: {$label}.",
                    'data'    => [],
                ], 404); // ← change 200 to 404
            }

            return response()->json(['data' => $trips], 200);

        } catch (\Exception $e) {
            Log::error('Trip filter by status failed', [
                'statuses' => $request->get('statuses'),
                'error'    => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Filter failed. Please try again.',
            ], 500);
        }
    }

    public function transition(Request $request, int $id): JsonResponse
    {
        try {
            $trip = TripTicket::with('driver')->findOrFail($id); // throws ModelNotFoundException → 404

            $validated = $request->validate([
                'status' => 'required|in:In-Transit,Completed,Cancelled',
            ]);

            $allowed = [
                'Draft'      => ['In-Transit', 'Cancelled'],
                'In-Transit' => ['Completed',  'Cancelled'],
            ];

            $current = $trip->status;

            if (!isset($allowed[$current]) || !in_array($validated['status'], $allowed[$current], true)) {
                return response()->json([
                    'message' => "Invalid status transition: cannot move from \"{$current}\" to \"{$validated['status']}\".",
                    'errors'  => ['status' => ["Cannot transition from \"{$current}\" to \"{$validated['status']}\"."]],
                ], 422);
            }

            if ($validated['status'] === 'In-Transit') {
                // Cross-module gate: block dispatch if truck is under active maintenance
                if ($error = $this->assertTruckNotUnderMaintenance($trip->truck_id)) {
                    return $error; // 422
                }

                $truck = Truck::find($trip->truck_id);
                if ($truck && $truck->status !== 'Available') {
                    return response()->json([
                        'message' => "Truck is not available ({$truck->status}).",
                        'errors'  => ['truck_id' => ["Truck is not available ({$truck->status})."]],
                    ], 422);
                }

                $driver = Driver::find($trip->driver_id);
                if ($driver && $driver->status !== 'Available') {
                    return response()->json([
                        'message' => "Driver is not available ({$driver->status}).",
                        'errors'  => ['driver_id' => ["Driver is not available ({$driver->status})."]],
                    ], 422);
                }
            }

            $oldStatus = $trip->status;
            $trip->update(['status' => $validated['status']]);
            $this->syncStatusesAfterTripChange($trip, null, null, $oldStatus);

            $this->writeLog(
                'status_changed',
                'trip_ticket',
                $trip->id,
                $this->tripLabel($trip),
                ['status' => $oldStatus],
                ['status' => $validated['status']],
                "{$oldStatus} → {$validated['status']}",
                $request
            );

            return response()->json($trip->load(['driver', 'truck']), 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip ticket not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to transition trip ticket status', [
                'trip_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while updating the trip status. Please try again.',
            ], 500);
        }
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed archive attempt — incorrect admin password', [
                    'trip_id' => $id,
                    'ip'      => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect admin password.',
                ], 403);
            }

            $trip        = TripTicket::with('driver')->findOrFail($id); // throws ModelNotFoundException → 404
            $oldDriverId = $trip->driver_id;
            $oldTruckId  = $trip->truck_id;

            $this->writeLog('archived', 'trip_ticket', $trip->id, $this->tripLabel($trip), null, null, null, $request);

            $trip->update(['is_archived' => true]);

            if ($oldDriverId) $this->syncDriverFromActiveTrips($oldDriverId);
            if ($oldTruckId)  $this->syncTruckFromActiveTrips($oldTruckId);

            return response()->json([
                'message' => 'Trip ticket archived successfully.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip ticket not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive trip ticket', [
                'trip_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while archiving the trip ticket. Please try again.',
            ], 500);
        }
    }

    public function unarchive(Request $request, int $id): JsonResponse
    {
        try {
            $trip = TripTicket::with('driver')->findOrFail($id); // throws ModelNotFoundException → 404

            $this->writeLog('restored', 'trip_ticket', $trip->id, $this->tripLabel($trip), null, null, null, $request);

            $trip->update(['is_archived' => false]);

            return response()->json([
                'message' => 'Trip ticket restored successfully.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip ticket not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive trip ticket', [
                'trip_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while restoring the trip ticket. Please try again.',
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed delete attempt — incorrect admin password', [
                    'trip_id' => $id,
                    'ip'      => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect admin password.',
                ], 403);
            }

            $trip        = TripTicket::with('driver')->findOrFail($id); // throws ModelNotFoundException → 404
            $snapshot    = $this->modelSnapshot($trip);
            $oldDriverId = $trip->driver_id;
            $oldTruckId  = $trip->truck_id;
            $label       = $this->tripLabel($trip);

            $trip->delete();

            if ($oldDriverId) $this->syncDriverFromActiveTrips($oldDriverId);
            if ($oldTruckId)  $this->syncTruckFromActiveTrips($oldTruckId);

            $this->writeLog('deleted', 'trip_ticket', $id, $label, $snapshot, null, null, $request);

            return response()->json([
                'message' => 'Trip ticket deleted successfully.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip ticket not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete trip ticket', [
                'trip_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while deleting the trip ticket. Please try again.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns 422 if the truck has a non-archived maintenance record
     * in Pending or In-Progress status; null if the truck is clear.
     */
    private function assertTruckNotUnderMaintenance(int $truckId): ?JsonResponse
    {
        $activeMaintenance = MaintenanceRecord::where('truck_id', $truckId)
            ->whereIn('status', self::BLOCKING_MAINTENANCE_STATUSES)
            ->where('is_archived', false)
            ->first();

        if (!$activeMaintenance) {
            return null;
        }

        $code    = optional(Truck::find($truckId))->truck_code ?? "ID {$truckId}";
        $status  = $activeMaintenance->status;

        $message = "Truck {$code} cannot be assigned to a trip because it currently has an active "
                 . "maintenance record with status \"{$status}\". "
                 . "Complete or cancel the maintenance record first.";

        return response()->json([
            'message' => $message,
            'errors'  => ['truck_id' => [$message]],
        ], 422);
    }

    private function tripLabel(TripTicket $trip): string
    {
        $firstName = optional($trip->driver)->full_name
            ? explode(' ', trim($trip->driver->full_name))[0]
            : '—';

        return $trip->trip_no . ' - ' . $firstName;
    }

    private function checkAdminPassword(string $password): bool
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();

        if (!$setting || !is_string($setting->value) || $setting->value === '') {
            return false;
        }

        return Hash::check($password, $setting->value);
    }

    private function normalizeDateTimes(array $validated): array
    {
        foreach (['departure_time', 'arrival_time'] as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = str_replace('T', ' ', $validated[$field]) . ':00';
            }
        }
        return $validated;
    }

    private function syncStatusesAfterTripChange(TripTicket $trip, ?int $oldDriverId = null, ?int $oldTruckId = null, ?string $oldStatus = null): void
    {
        if ($oldDriverId && $oldDriverId !== $trip->driver_id) $this->syncDriverFromActiveTrips($oldDriverId);
        if ($oldTruckId  && $oldTruckId  !== $trip->truck_id)  $this->syncTruckFromActiveTrips($oldTruckId);

        $driver = Driver::find($trip->driver_id);
        $truck  = Truck::find($trip->truck_id);

        if (!$driver || !$truck) return;

        if ($trip->status === 'In-Transit') {
            $driver->update(['status' => 'Covering', 'assigned_truck' => $truck->truck_code]);
            $truck->update(['status' => 'In-Transit']);
            return;
        }

        if ($trip->status === 'Completed' && $oldStatus !== 'Completed') {
            $completedAt = $trip->arrival_time
                ? Carbon::parse($trip->arrival_time)->toDateString()
                : Carbon::now()->toDateString();

            $driver->update([
                'total_trips' => ($driver->total_trips ?? 0) + 1,
                'last_trip'   => $completedAt,
            ]);
        }

        $this->syncDriverFromActiveTrips($trip->driver_id);
        $this->syncTruckFromActiveTrips($trip->truck_id);
    }

    private function syncDriverFromActiveTrips(int $driverId): void
    {
        $driver = Driver::find($driverId);
        if (!$driver) return;

        $activeTrip = TripTicket::with('truck')
            ->where('driver_id', $driverId)
            ->where('status', 'In-Transit')
            ->where('is_archived', false)
            ->latest('updated_at')
            ->first();

        $driver->update($activeTrip
            ? ['status' => 'Covering', 'assigned_truck' => optional($activeTrip->truck)->truck_code]
            : ['status' => 'Available', 'assigned_truck' => null]
        );
    }

    private function syncTruckFromActiveTrips(int $truckId): void
    {
        $truck = Truck::find($truckId);
        if (!$truck) return;

        $hasActiveTrip = TripTicket::where('truck_id', $truckId)
            ->where('status', 'In-Transit')
            ->where('is_archived', false)
            ->exists();

        $truck->update(['status' => $hasActiveTrip ? 'In-Transit' : 'Available']);
    }
}