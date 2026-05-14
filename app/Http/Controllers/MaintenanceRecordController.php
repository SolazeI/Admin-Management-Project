<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\MaintenanceRecord;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MaintenanceRecordController extends Controller
{
    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function index()
    {
        $trucks = Truck::orderBy('truck_code')->get();

        $records = MaintenanceRecord::with('truck')
            ->where('is_archived', false)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $archivedRecords = MaintenanceRecord::with('truck')
            ->where('is_archived', true)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('maintenance', compact('trucks', 'records', 'archivedRecords'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_id'          => 'required|exists:trucks,id',
            'issue_description' => 'required|string|max:255',
            'start_date'        => 'nullable|date',
            'notes'             => 'nullable|string|max:255',
            'cost'              => 'nullable|numeric|min:0',
        ]);

        // New records always start as Pending
        $validated['status'] = 'Pending';

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

    public function transition(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'status' => 'required|in:In-Progress,Completed,Cancelled',
        ]);

        // Enforce valid transitions
        $allowed = [
            'Pending'     => ['In-Progress', 'Cancelled'],
            'In-Progress' => ['Completed', 'Cancelled'],
        ];

        $current = $record->status;
        if (!isset($allowed[$current]) || !in_array($validated['status'], $allowed[$current])) {
            return redirect()->back()->with('error', "Cannot transition from {$current} to {$validated['status']}.");
        }

        $record->update(['status' => $validated['status']]);
        $this->syncTruckStatus($record->truck_id);

        return redirect()->back()->with('success', 'Maintenance status updated.');
    }

    public function archive(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$this->checkAdminPassword($validated['password'])) {
            return redirect()->back()->with('error', 'Incorrect password.');
        }

        $truckId = $record->truck_id;
        $record->update(['is_archived' => true]);

        // Re-sync — archived records don't count toward truck status
        $this->syncTruckStatus($truckId);

        return redirect()->back()->with('success', 'Maintenance record archived.');
    }

    public function unarchive(MaintenanceRecord $record)
    {
        $record->update(['is_archived' => false]);
        $this->syncTruckStatus($record->truck_id);

        return redirect()->back()->with('success', 'Maintenance record restored.');
    }

    public function destroy(Request $request, MaintenanceRecord $record)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$this->checkAdminPassword($validated['password'])) {
            return redirect()->back()->with('error', 'Incorrect password.');
        }

        $truckId = $record->truck_id;
        $record->delete();
        $this->syncTruckStatus($truckId);

        return redirect()->back()->with('success', 'Maintenance record deleted.');
    }

    private function checkAdminPassword(string $password): bool
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();
        if (!$setting || !is_string($setting->value) || $setting->value === '') {
            return false;
        }

        return Hash::check($password, $setting->value);
    }

    /**
     * Derive and apply the correct truck status based on active, non-archived maintenance records.
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
            ->where('is_archived', false)
            ->exists();

        $truck->update([
            'status' => $hasActive ? 'Maintenance' : 'Available',
        ]);
    }
}