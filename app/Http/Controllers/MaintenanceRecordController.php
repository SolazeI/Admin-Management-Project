<?php
namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\MaintenanceRecord;
use App\Models\Truck;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MaintenanceRecordController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function index()
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to load maintenance records', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to load maintenance records. Please try again.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'truck_id'          => 'required|exists:trucks,id',
                'issue_description' => 'required|string|max:255',
                'start_date'        => 'nullable|date',
                'notes'             => 'nullable|string|max:255',
                'cost'              => 'nullable|numeric|min:0',
            ]);

            $validated['status'] = 'Pending';
            $record = MaintenanceRecord::create($validated);
            $this->syncTruckStatus($validated['truck_id']);

            // Subject label: just the truck code
            $label = optional($record->truck)->truck_code ?? '—';

            $this->writeLog('created', 'maintenance_record', $record->id, $label, null, $this->modelSnapshot($record), null, $request);

            return response()->json($record->load('truck'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create maintenance record', ['input' => $request->all(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while adding the maintenance record. Please try again.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $record      = MaintenanceRecord::findOrFail($id);
            $oldSnapshot = $this->modelSnapshot($record);

            $validated = $request->validate([
                'truck_id'          => 'required|exists:trucks,id',
                'issue_description' => 'required|string|max:255',
                'start_date'        => 'nullable|date',
                'notes'             => 'nullable|string|max:255',
                'cost'              => 'nullable|numeric|min:0',
            ]);

            $oldTruckId = $record->truck_id;
            $record->update($validated);
            $this->syncTruckStatus($validated['truck_id']);

            if ($oldTruckId !== $validated['truck_id']) {
                $this->syncTruckStatus($oldTruckId);
            }

            // Subject label: just the truck code
            $label = optional($record->truck)->truck_code ?? '—';

            $this->writeLog('updated', 'maintenance_record', $record->id, $label, $oldSnapshot, $this->modelSnapshot($record->fresh()), null, $request);

            return response()->json($record->load('truck'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Maintenance record not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update maintenance record', ['record_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while updating the maintenance record. Please try again.'], 500);
        }
    }

    public function transition(Request $request, $id)
    {
        try {
            $record = MaintenanceRecord::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|in:In-Progress,Completed,Cancelled',
            ]);

            $allowed = [
                'Pending'     => ['In-Progress', 'Cancelled'],
                'In-Progress' => ['Completed', 'Cancelled'],
            ];

            $current = $record->status;

            if (!isset($allowed[$current]) || !in_array($validated['status'], $allowed[$current])) {
                return response()->json(['message' => "Cannot transition from {$current} to {$validated['status']}."], 422);
            }

            $record->update(['status' => $validated['status']]);
            $this->syncTruckStatus($record->truck_id);

            // Subject label: just the truck code
            $label = optional($record->truck)->truck_code ?? '—';

            $this->writeLog(
                'status_changed',
                'maintenance_record',
                $record->id,
                $label,
                ['status' => $current],
                ['status' => $validated['status']],
                "{$current} → {$validated['status']}",
                $request
            );

            return response()->json($record->load('truck'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Maintenance record not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to transition maintenance record status', ['record_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while updating the maintenance status. Please try again.'], 500);
        }
    }

    public function archive(Request $request, $id)
    {
        try {
            $validated = $request->validate(['password' => 'required|string']);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed archive attempt — incorrect admin password', ['record_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password.'], 403);
            }

            $record  = MaintenanceRecord::findOrFail($id);
            $truckId = $record->truck_id;

            // Subject label: just the truck code
            $label = optional($record->truck)->truck_code ?? '—';

            $record->update(['is_archived' => true]);
            $this->syncTruckStatus($truckId);

            $this->writeLog('archived', 'maintenance_record', $record->id, $label, null, null, null, $request);

            return response()->json(['message' => 'Maintenance record archived successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Maintenance record not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive maintenance record', ['record_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while archiving the maintenance record. Please try again.'], 500);
        }
    }

    public function unarchive(Request $request, $id)
    {
        try {
            $record = MaintenanceRecord::findOrFail($id);

            // Subject label: just the truck code
            $label = optional($record->truck)->truck_code ?? '—';

            $record->update(['is_archived' => false]);
            $this->syncTruckStatus($record->truck_id);

            $this->writeLog('restored', 'maintenance_record', $record->id, $label, null, null, null, $request);

            return response()->json(['message' => 'Maintenance record restored successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Maintenance record not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive maintenance record', ['record_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while restoring the maintenance record. Please try again.'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $validated = $request->validate(['password' => 'required|string']);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed delete attempt — incorrect admin password', ['record_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password.'], 403);
            }

            $record   = MaintenanceRecord::findOrFail($id);
            $snapshot = $this->modelSnapshot($record);
            $truckId  = $record->truck_id;

            // Subject label: just the truck code (captured before delete)
            $label = optional($record->truck)->truck_code ?? '—';

            $record->delete();
            $this->syncTruckStatus($truckId);

            $this->writeLog('deleted', 'maintenance_record', $id, $label, $snapshot, null, null, $request);

            return response()->json(['message' => 'Maintenance record deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Maintenance record not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete maintenance record', ['record_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while deleting the maintenance record. Please try again.'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function checkAdminPassword(string $password): bool
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();
        if (!$setting || !is_string($setting->value) || $setting->value === '') {
            return false;
        }
        return Hash::check($password, $setting->value);
    }

    private function syncTruckStatus(int $truckId): void
    {
        $truck = Truck::find($truckId);
        if (!$truck || $truck->status === 'In-Transit') return;

        $hasActive = MaintenanceRecord::where('truck_id', $truckId)
            ->whereIn('status', ['Pending', 'In-Progress'])
            ->where('is_archived', false)
            ->exists();

        $truck->update(['status' => $hasActive ? 'Maintenance' : 'Available']);
    }
}