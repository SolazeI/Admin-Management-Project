<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TripTicket;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TripTicketController extends Controller
{
    public function index()
    {
        $drivers = Driver::where('is_archived', false)->orderBy('full_name')->get();
        $trucks = Truck::orderBy('truck_code')->get();

        $tripTickets = TripTicket::with(['driver', 'truck'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('trips', compact('drivers', 'trucks', 'tripTickets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_no' => 'nullable|string|max:50|unique:trip_tickets,trip_no',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'date_issued' => 'nullable|date',
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
            'arrival_time' => 'nullable|date_format:Y-m-d\TH:i',
            'distance_km' => 'nullable|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:Draft,In-Transit,Completed,Cancelled',
            'remarks' => 'nullable|string',
        ]);

        $validated = $this->normalizeDateTimes($validated);

        if (empty($validated['trip_no'])) {
            $validated['trip_no'] = 'TRIP-' . strtoupper(Str::random(6));
        }

        TripTicket::create($validated);
        return redirect()->back()->with('success', 'Trip ticket saved.');
    }

    public function update(Request $request, TripTicket $trip)
    {
        $validated = $request->validate([
            'trip_no' => 'required|string|max:50|unique:trip_tickets,trip_no,' . $trip->id,
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'date_issued' => 'nullable|date',
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'departure_time' => 'nullable|date_format:Y-m-d\TH:i',
            'arrival_time' => 'nullable|date_format:Y-m-d\TH:i',
            'distance_km' => 'nullable|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:Draft,In-Transit,Completed,Cancelled',
            'remarks' => 'nullable|string',
        ]);

        $validated = $this->normalizeDateTimes($validated);

        $trip->update($validated);
        return redirect()->back()->with('success', 'Trip ticket updated.');
    }

    public function destroy(TripTicket $trip)
    {
        $trip->delete();
        return redirect()->back()->with('success', 'Trip ticket deleted.');
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
}
