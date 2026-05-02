<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecord;
use App\Models\Truck;
use Illuminate\Http\Request;

class MaintenanceRecordController extends Controller
{
    public function index()
    {
        $trucks = Truck::orderBy('truck_code')->get();
        $records = MaintenanceRecord::with('truck')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('maintenance', compact('trucks', 'records'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_id'          => 'required|exists:trucks,id',
            'issue_description' => 'required|string|max:255',
            'start_date'        => 'nullable|date',
            'status'            => 'required|in:Pending,In-Progress,Completed,Cancelled',
            'notes'             => 'nullable|string|max:255',
            'cost'              => 'nullable|numeric|min:0',
        ]);

        MaintenanceRecord::create($validated);
        $this->syncTruckStatus($validated['truck_id']);

        return redirect()->back()->with('success', 'Maintenance record added.');
    }

    public function update(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'truck_id'          => 'required|exists:trucks,id',
            'issue_description' => 'required|string|max:255',
            'start_date'        => 'nullable|date',
            'status'            => 'required|in:Pending,In-Progress,Completed,Cancelled',
            'notes'             => 'nullable|string|max:255',
            'cost'              => 'nullable|numeric|min:0',
        ]);

        $oldTruckId = $record->truck_id;
        $record->update($validated);

        // If truck was reassigned, sync both old and new
        $this->syncTruckStatus($validated['truck_id']);
        if ($oldTruckId !== $validated['truck_id']) {
            $this->syncTruckStatus($oldTruckId);
        }

        return redirect()->back()->with('success', 'Maintenance record updated.');
    }

    public function destroy(MaintenanceRecord $record)
    {
        $truckId = $record->truck_id;
        $record->delete();
        $this->syncTruckStatus($truckId);

        return redirect()->back()->with('success', 'Maintenance record deleted.');
    }

    /**
     * Derive and apply the correct truck status based on active maintenance records.
     * In-Transit is handled by trip tickets — don't override it here.
     */
    private function syncTruckStatus(int $truckId): void
    {
        $truck = Truck::find($truckId);
        if (!$truck) return;

        // Don't touch trucks currently on a trip
        if ($truck->status === 'In-Transit') return;

        $hasActive = MaintenanceRecord::where('truck_id', $truckId)
            ->whereIn('status', ['Pending', 'In-Progress'])
            ->exists();

        $truck->update([
            'status' => $hasActive ? 'Maintenance' : 'Available',
        ]);
    }
}