<?php
namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\MaintenanceRecord;
use App\Models\TripTicket;
use App\Models\Truck;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MaintenanceRecordController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    /** Trip ticket statuses that make a truck ineligible for maintenance. */
    private const BLOCKING_TRIP_STATUSES = ['Draft', 'In-Transit'];

    // -------------------------------------------------------------------------
    // Public endpoints
    // -------------------------------------------------------------------------

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
            // index() renders a Blade view — return a view-level error, not JSON
            return back()->withErrors(['error' => 'Unable to load maintenance records. Please try again.']);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->normalizeMaintenanceInput($request);

            $validated = $request->validate([
                'truck_id'          => 'required|exists:trucks,id',
                'issue_description' => 'required|string|max:255',
                'start_date'        => 'nullable|date',
                'notes'             => 'nullable|string|max:255',
                'cost'              => 'nullable|numeric|min:0',
            ]);

            if ($error = $this->assertTruckNotOnActiveTrip($validated['truck_id'])) {
                return $error; // 422
            }

            $validated['status'] = 'Pending';
            $record = MaintenanceRecord::create($validated);
            $this->syncTruckStatus($validated['truck_id']);

            $label = optional($record->truck)->truck_code ?? '—';
            $this->writeLog('created', 'maintenance_record', $record->id, $label, null, $this->modelSnapshot($record), null, $request);

            return response()->json($record->load('truck'), 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create maintenance record', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while adding the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $record      = MaintenanceRecord::findOrFail($id); // throws ModelNotFoundException → 404
            $oldSnapshot = $this->modelSnapshot($record);

            $this->normalizeMaintenanceInput($request);

            $validated = $request->validate([
                'truck_id'          => 'required|exists:trucks,id',
                'issue_description' => 'required|string|max:255',
                'start_date'        => 'nullable|date',
                'notes'             => 'nullable|string|max:255',
                'cost'              => 'nullable|numeric|min:0',
            ]);

            // Only re-check cross-module constraint if the truck is being swapped
            if ((int) $validated['truck_id'] !== (int) $record->truck_id) {
                if ($error = $this->assertTruckNotOnActiveTrip($validated['truck_id'])) {
                    return $error; // 422
                }
            }

            $oldTruckId = $record->truck_id;
            $record->update($validated);
            $this->syncTruckStatus($validated['truck_id']);

            if ((int) $oldTruckId !== (int) $validated['truck_id']) {
                $this->syncTruckStatus($oldTruckId);
            }

            $label = optional($record->truck)->truck_code ?? '—';
            $this->writeLog('updated', 'maintenance_record', $record->id, $label, $oldSnapshot, $this->modelSnapshot($record->fresh()), null, $request);

            return response()->json($record->load('truck'), 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update maintenance record', [
                'record_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while updating the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function transition(Request $request, int $id): JsonResponse
    {
        try {
            $record = MaintenanceRecord::findOrFail($id); // throws ModelNotFoundException → 404

            $validated = $request->validate([
                'status' => 'required|in:In-Progress,Completed,Cancelled',
            ]);

            $allowed = [
                'Pending'     => ['In-Progress', 'Cancelled'],
                'In-Progress' => ['Completed',   'Cancelled'],
            ];

            $current = $record->status;

            if (!isset($allowed[$current]) || !in_array($validated['status'], $allowed[$current], true)) {
                return response()->json([
                    'message' => "Invalid status transition: cannot move from \"{$current}\" to \"{$validated['status']}\".",
                    'errors'  => ['status' => ["Cannot transition from \"{$current}\" to \"{$validated['status']}\"."]],
                ], 422);
            }

            // Re-verify cross-module constraint at activation time
            if ($validated['status'] === 'In-Progress') {
                if ($error = $this->assertTruckNotOnActiveTrip($record->truck_id)) {
                    return $error; // 422
                }
            }

            $record->update(['status' => $validated['status']]);
            $this->syncTruckStatus($record->truck_id);

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

            return response()->json($record->load('truck'), 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Maintenance record not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to transition maintenance record status', [
                'record_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while updating the maintenance status. Please try again.',
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
                    'record_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect admin password.',
                ], 403);
            }

            $record  = MaintenanceRecord::findOrFail($id); // throws ModelNotFoundException → 404
            $truckId = $record->truck_id;
            $label   = optional($record->truck)->truck_code ?? '—';

            $record->update(['is_archived' => true]);
            $this->syncTruckStatus($truckId);

            $this->writeLog('archived', 'maintenance_record', $record->id, $label, null, null, null, $request);

            return response()->json([
                'message' => 'Maintenance record archived successfully.',
            ], 200);

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
                'message' => 'An unexpected error occurred while archiving the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function unarchive(Request $request, int $id): JsonResponse
    {
        try {
            $record = MaintenanceRecord::findOrFail($id); // throws ModelNotFoundException → 404
            $label  = optional($record->truck)->truck_code ?? '—';

            $record->update(['is_archived' => false]);
            $this->syncTruckStatus($record->truck_id);

            $this->writeLog('restored', 'maintenance_record', $record->id, $label, null, null, null, $request);

            return response()->json([
                'message' => 'Maintenance record restored successfully.',
            ], 200);

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
                'message' => 'An unexpected error occurred while restoring the maintenance record. Please try again.',
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
                    'record_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect admin password.',
                ], 403);
            }

            $record   = MaintenanceRecord::findOrFail($id); // throws ModelNotFoundException → 404
            $snapshot = $this->modelSnapshot($record);
            $truckId  = $record->truck_id;
            $label    = optional($record->truck)->truck_code ?? '—';

            $record->delete();
            $this->syncTruckStatus($truckId);

            $this->writeLog('deleted', 'maintenance_record', $id, $label, $snapshot, null, null, $request);

            return response()->json([
                'message' => 'Maintenance record deleted successfully.',
            ], 200);

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
                'message' => 'An unexpected error occurred while deleting the maintenance record. Please try again.',
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = trim($request->get('q', ''));
            if ($query === '') {
                return response()->json(['message' => 'Search query cannot be empty.'], 422);
            }
            $records = MaintenanceRecord::with('truck')
                ->where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->whereHas('truck', fn($t) => $t->where('truck_code', 'like', "%{$query}%"))
                    ->orWhere('issue_description', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%");
                })
                ->orderByDesc('created_at')
                ->get();
            if ($records->isEmpty()) {
                return response()->json([
                    'message' => "No maintenance records found matching \"{$query}\".",
                    'data'    => [],
                ], 404);
            }
            return response()->json(['data' => $records], 200);
        } catch (\Exception $e) {
            Log::error('Maintenance search failed', [
                'query' => $request->get('q'),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Search failed. Please try again.'], 500);
        }
    }

    public function filterByStatus(Request $request): JsonResponse
    {
        try {
            $statuses      = $request->query('statuses', []);
            $validStatuses = ['Pending', 'In-Progress', 'Completed', 'Cancelled'];
            if (!empty($statuses)) {
                $invalid = array_diff($statuses, $validStatuses);
                if (!empty($invalid)) {
                    return response()->json([
                        'message' => 'Invalid status value(s) provided.',
                        'errors'  => ['statuses' => ['Allowed values: Pending, In-Progress, Completed, Cancelled.']],
                    ], 422);
                }
            }
            $records = MaintenanceRecord::with('truck')
                ->where('is_archived', false)
                ->when(!empty($statuses), fn($q) => $q->whereIn('status', $statuses))
                ->orderByDesc('created_at')
                ->get();
            if ($records->isEmpty()) {
                $label = !empty($statuses) ? implode(', ', $statuses) : 'any status';
                return response()->json([
                    'message' => "No maintenance records found for status: {$label}.",
                    'data'    => [],
                ], 404);
            }
            return response()->json(['data' => $records], 200);
        } catch (\Exception $e) {
            Log::error('Maintenance filter by status failed', [
                'statuses' => $request->get('statuses'),
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Filter failed. Please try again.'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns 422 if the truck has a non-archived trip ticket
     * in Draft or In-Transit status; null if the truck is clear.
     */
    /** Empty strings from JSON requests are not treated as null by Laravel validators. */
    private function normalizeMaintenanceInput(Request $request): void
    {
        $cost = $request->input('cost');
        if ($cost !== null && $cost !== '') {
            $cleaned = preg_replace('/[^\d.]/', '', (string) $cost);
            $cost = $cleaned !== '' ? $cleaned : null;
        } else {
            $cost = null;
        }

        $request->merge([
            'start_date' => $request->filled('start_date') ? $request->input('start_date') : null,
            'notes'      => $request->filled('notes') ? $request->input('notes') : null,
            'cost'       => $cost,
        ]);
    }

    private function assertTruckNotOnActiveTrip(int $truckId): ?JsonResponse
    {
        $activeTrip = TripTicket::where('truck_id', $truckId)
            ->whereIn('status', self::BLOCKING_TRIP_STATUSES)
            ->where('is_archived', false)
            ->first();

        if (!$activeTrip) {
            return null;
        }

        $code    = optional(Truck::find($truckId))->truck_code ?? "ID {$truckId}";
        $tripNo  = $activeTrip->trip_no ?? "ID {$activeTrip->id}";
        $status  = $activeTrip->status;

        $message = "Truck {$code} cannot be put under maintenance because it has an active trip ticket "
                 . "({$tripNo}) with status \"{$status}\". "
                 . "Complete or cancel the trip ticket first.";

        return response()->json([
            'message' => $message,
            'errors'  => ['truck_id' => [$message]],
        ], 422);
    }

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