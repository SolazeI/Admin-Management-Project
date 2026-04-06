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
            'truck_id' => 'required|exists:trucks,id',
            'issue_description' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'status' => 'required|in:Pending,In-Progress,Completed,Cancelled',
            'notes' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
        ]);

        MaintenanceRecord::create($validated);
        return redirect()->back()->with('success', 'Maintenance record added.');
    }

    public function update(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'issue_description' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'status' => 'required|in:Pending,In-Progress,Completed,Cancelled',
            'notes' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $record->update($validated);
        return redirect()->back()->with('success', 'Maintenance record updated.');
    }

    public function destroy(MaintenanceRecord $record)
    {
        $record->delete();
        return redirect()->back()->with('success', 'Maintenance record deleted.');
    }
}
