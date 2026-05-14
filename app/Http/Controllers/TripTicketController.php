<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TripTicket;
use App\Models\Truck;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminSetting;
use Illuminate\Support\Str;

class TripTicketController extends Controller
{
    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function index()
    {
        // Available only — for the Add modal
        $drivers = Driver::where('is_archived', false)
            ->where('status', 'Available')
            ->orderBy('full_name')
            ->get();

        $trucks = Truck::where('status', 'Available')
            ->orderBy('truck_code')
            ->get();

        // All (non-archived) — for edit forms so current assignments still show
        $allDrivers = Driver::where('is_archived', false)
            ->orderBy('full_name')
            ->get();

        $allTrucks = Truck::orderBy('truck_code')->get();

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

        return view('trips', compact(
            'drivers', 'trucks', 'allDrivers', 'allTrucks',
            'tripTickets', 'archivedTripTickets'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_no'        => 'nullable|string|max:50|unique:trip_tickets,trip_no',
            'driver_id'      => 'required|exists:drivers,id',
            'truck_id'       => 'required|exists:trucks,id',
            'date_issued'    => 'nullable|date',
            'origin'         => 'nullable|string|max:255',
            'destination'    => 'nullable|string|max:255',
            'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
            'arrival_time'   => 'nullable|date_format:Y-m-d\TH:i',
            'distance_km'    => 'nullable|integer|min:0',
            'amount'         => 'nullable|numeric|min:0',
            'remarks'        => 'nullable|string',
        ]);

        // Guard: truck must be available
        $truck = Truck::find($validated['truck_id']);
        if ($truck && $truck->status !== 'Available') {
            return redirect()->back()->withErrors(['truck_id' => 'This truck is not available ('.$truck->status.').' ]);
        }

        // Guard: driver must be available
        $driver = Driver::find($validated['driver_id']);
        if ($driver && $driver->status !== 'Available') {
            return redirect()->back()->withErrors(['driver_id' => 'This driver is not available ('.$driver->status.').' ]);
        }

        $validated = $this->normalizeDateTimes($validated);
        $validated['status'] = 'Draft';

        if (empty($validated['trip_no'])) {
            $validated['trip_no'] = 'TRIP-' . strtoupper(Str::random(6));
        }

        $trip = TripTicket::create($validated);
        $this->syncStatusesAfterTripChange($trip);

        return redirect()->back()->with('success', 'Trip ticket saved.');
    }

    public function update(Request $request, TripTicket $trip)
    {
        $validated = $request->validate([
            'trip_no'        => 'required|string|max:50|unique:trip_tickets,trip_no,' . $trip->id,
            'driver_id'      => 'required|exists:drivers,id',
            'truck_id'       => 'required|exists:trucks,id',
            'date_issued'    => 'nullable|date',
            'origin'         => 'nullable|string|max:255',
            'destination'    => 'nullable|string|max:255',
            'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
            'arrival_time'   => 'nullable|date_format:Y-m-d\TH:i',
            'distance_km'    => 'nullable|integer|min:0',
            'amount'         => 'nullable|numeric|min:0',
            'remarks'        => 'nullable|string',
        ]);

        $validated = $this->normalizeDateTimes($validated);

        $oldDriverId = $trip->driver_id;
        $oldTruckId  = $trip->truck_id;
        $oldStatus   = $trip->status;

        // Preserve current status — editing details doesn't change status
        $validated['status'] = $trip->status;

        $trip->update($validated);
        $this->syncStatusesAfterTripChange($trip, $oldDriverId, $oldTruckId, $oldStatus);

        return redirect()->back()->with('success', 'Trip ticket updated.');
    }

    public function transition(Request $request, TripTicket $trip)
    {
        $validated = $request->validate([
            'status' => 'required|in:In-Transit,Completed,Cancelled',
        ]);

        // Enforce valid transitions
        $allowed = [
            'Draft'      => ['In-Transit', 'Cancelled'],
            'In-Transit' => ['Completed', 'Cancelled'],
        ];

        $current = $trip->status;
        if (!isset($allowed[$current]) || !in_array($validated['status'], $allowed[$current])) {
            return redirect()->back()->with('error', "Cannot transition from {$current} to {$validated['status']}.");
        }

        // Guard: when dispatching, truck & driver must be available
        if ($validated['status'] === 'In-Transit') {
            $truck = Truck::find($trip->truck_id);
            if ($truck && $truck->status !== 'Available') {
                return redirect()->back()->withErrors(['truck_id' => 'Truck is not available ('.$truck->status.').']);
            }
            $driver = Driver::find($trip->driver_id);
            if ($driver && $driver->status !== 'Available') {
                return redirect()->back()->withErrors(['driver_id' => 'Driver is not available ('.$driver->status.').']);
            }
        }

        $oldStatus = $trip->status;
        $trip->update(['status' => $validated['status']]);
        $this->syncStatusesAfterTripChange($trip, null, null, $oldStatus);

        return redirect()->back()->with('success', 'Trip status updated.');
    }

    public function archive(Request $request, TripTicket $trip)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$this->checkAdminPassword($validated['password'])) {
            return redirect()->back()->with('error', 'Incorrect password.');
        }

        $oldDriverId = $trip->driver_id;
        $oldTruckId  = $trip->truck_id;

        $trip->update(['is_archived' => true]);

        // Re-sync availability since the trip is no longer active
        if ($oldDriverId) {
            $this->syncDriverFromActiveTrips($oldDriverId);
        }
        if ($oldTruckId) {
            $this->syncTruckFromActiveTrips($oldTruckId);
        }

        return redirect()->back()->with('success', 'Trip ticket archived.');
    }

    public function unarchive(TripTicket $trip)
    {
        $trip->update(['is_archived' => false]);

        return redirect()->back()->with('success', 'Trip ticket restored.');
    }

    public function destroy(Request $request, TripTicket $trip)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$this->checkAdminPassword($validated['password'])) {
            return redirect()->back()->with('error', 'Incorrect password.');
        }

        $oldDriverId = $trip->driver_id;
        $oldTruckId  = $trip->truck_id;

        $trip->delete();

        // Re-evaluate availability after removing a trip assignment.
        if ($oldDriverId) {
            $this->syncDriverFromActiveTrips($oldDriverId);
        }
        if ($oldTruckId) {
            $this->syncTruckFromActiveTrips($oldTruckId);
        }

        return redirect()->back()->with('success', 'Trip ticket deleted.');
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

    private function syncStatusesAfterTripChange(
        TripTicket $trip,
        ?int $oldDriverId = null,
        ?int $oldTruckId = null,
        ?string $oldStatus = null
    ): void {
        // If assignment changed, re-sync old entities first.
        if ($oldDriverId && $oldDriverId !== $trip->driver_id) {
            $this->syncDriverFromActiveTrips($oldDriverId);
        }
        if ($oldTruckId && $oldTruckId !== $trip->truck_id) {
            $this->syncTruckFromActiveTrips($oldTruckId);
        }

        $driver = Driver::find($trip->driver_id);
        $truck  = Truck::find($trip->truck_id);
        if (!$driver || !$truck) {
            return;
        }

        if ($trip->status === 'In-Transit') {
            $driver->update([
                'status'         => 'Covering',
                'assigned_truck' => $truck->truck_code,
            ]);
            $truck->update(['status' => 'In-Transit']);
            return;
        }

        // Count trip once when it transitions into Completed.
        if ($trip->status === 'Completed' && $oldStatus !== 'Completed') {
            $completedAt = $trip->arrival_time
                ? Carbon::parse($trip->arrival_time)->toDateString()
                : Carbon::now()->toDateString();

            $driver->update([
                'total_trips' => ($driver->total_trips ?? 0) + 1,
                'last_trip'   => $completedAt,
            ]);
        }

        // For Draft/Completed/Cancelled, sync based on other active trips.
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

        if ($activeTrip) {
            $driver->update([
                'status'         => 'Covering',
                'assigned_truck' => optional($activeTrip->truck)->truck_code,
            ]);
            return;
        }

        $driver->update([
            'status'         => 'Available',
            'assigned_truck' => null,
        ]);
    }

    private function syncTruckFromActiveTrips(int $truckId): void
    {
        $truck = Truck::find($truckId);
        if (!$truck) return;

        $hasActiveTrip = TripTicket::where('truck_id', $truckId)
            ->where('status', 'In-Transit')
            ->where('is_archived', false)
            ->exists();

        $truck->update([
            'status' => $hasActiveTrip ? 'In-Transit' : 'Available',
        ]);
    }
}