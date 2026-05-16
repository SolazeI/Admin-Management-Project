<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\MaintenanceRecord;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MaintenanceRecordController extends Controller
{
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
            Log::error('Failed to load maintenance records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Unable to load maintenance records. Please try again.',
            ], 500);
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

            return response()->json($record->load('truck'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create maintenance record', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while adding the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $record = MaintenanceRecord::findOrFail($id);

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

            return response()->json($record->load('truck'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update maintenance record', [
                'record_id' => $id,
                'input'     => $request->all(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while updating the maintenance record. Please try again.',
            ], 500);
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
                return response()->json([
                    'message' => "Cannot transition from {$current} to {$validated['status']}.",
                ], 422);
            }

            $record->update(['status' => $validated['status']]);
            $this->syncTruckStatus($record->truck_id);

            return response()->json($record->load('truck'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to transition maintenance record status', [
                'record_id' => $id,
                'input'     => $request->all(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while updating the maintenance status. Please try again.',
            ], 500);
        }
    }

    public function archive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed archive attempt — incorrect admin password', [
                    'record_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect password.',
                ], 403);
            }

            $record  = MaintenanceRecord::findOrFail($id);
            $truckId = $record->truck_id;

            $record->update(['is_archived' => true]);
            $this->syncTruckStatus($truckId);

            return response()->json(['message' => 'Maintenance record archived successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive maintenance record', [
                'record_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while archiving the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function unarchive($id)
    {
        try {
            $record = MaintenanceRecord::findOrFail($id);
            $record->update(['is_archived' => false]);
            $this->syncTruckStatus($record->truck_id);

            return response()->json(['message' => 'Maintenance record restored successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive maintenance record', [
                'record_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while restoring the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed delete attempt — incorrect admin password', [
                    'record_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect password.',
                ], 403);
            }

            $record  = MaintenanceRecord::findOrFail($id);
            $truckId = $record->truck_id;

            $record->delete();
            $this->syncTruckStatus($truckId);

            return response()->json(['message' => 'Maintenance record deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete maintenance record', [
                'record_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while deleting the maintenance record. Please try again.',
            ], 500);
        }
    }

    private function checkAdminPassword(string $password): bool
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();
        if (!$setting || !is_string($setting->value) || $setting->value === '') {
            Log::error('Admin password setting missing or invalid', [
                'key' => self::ADMIN_PASSWORD_KEY,
            ]);
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