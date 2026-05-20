<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Traits\LogsActivity;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TruckController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function index()
    {
        try {
            $trucks = Truck::orderBy('truck_code')->get();
            return view('fleet', compact('trucks'));
        } catch (\Exception $e) {
            Log::error('Failed to load trucks', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to load trucks. Please try again.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'truck_code'   => 'required|string|max:50|unique:trucks,truck_code',
                'plate_number' => 'required|string|max:50|unique:trucks,plate_number',
                'model'        => 'nullable|string|max:100',
                'notes'        => 'nullable|string',
                'status'       => 'nullable|in:Available,Inactive',
            ]);

            $validated['status'] = 'Available';

            $truck = Truck::create($validated);

            $this->writeLog('created', 'truck', $truck->id, $truck->truck_code, null, $this->modelSnapshot($truck), null, $request);

            return response()->json($truck, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create truck', ['input' => $request->all(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while adding the truck. Please try again.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $truck = Truck::findOrFail($id);
            $oldSnapshot = $this->modelSnapshot($truck);

            $validated = $request->validate([
                'truck_code'   => 'required|string|max:50|unique:trucks,truck_code,' . $truck->id,
                'plate_number' => 'nullable|string|max:50|unique:trucks,plate_number,' . $truck->id,
                'model'        => 'nullable|string|max:100',
                'notes'        => 'nullable|string',
                'status'       => 'nullable|in:Available,Inactive',
            ]);

            if (!in_array($truck->status, ['Available', 'Inactive'])) {
                unset($validated['status']);
            }

            $truck->update($validated);

            $this->writeLog('updated', 'truck', $truck->id, $truck->truck_code, $oldSnapshot, $this->modelSnapshot($truck->fresh()), null, $request);

            return response()->json($truck);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Truck not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update truck', ['truck_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while updating the truck. Please try again.'], 500);
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
                    'truck_id' => $id,
                    'ip'       => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect admin password.',
                ], 403);
            }

            $truck = Truck::findOrFail($id);

            // Block if truck has active maintenance records
            $activeMaintenance = \App\Models\MaintenanceRecord::where('truck_id', $id)
                ->whereIn('status', ['Pending', 'In-Progress'])
                ->where('is_archived', false)
                ->first();

            if ($activeMaintenance) {
                return response()->json([
                    'message' => "Truck {$truck->truck_code} cannot be deleted because it has an active maintenance record with status \"{$activeMaintenance->status}\". Complete or cancel it first.",
                    'errors'  => ['truck_id' => ["Truck has an active maintenance record ({$activeMaintenance->status})."]],
                ], 422);
            }

            // Block if truck has active trip tickets
            $activeTrip = \App\Models\TripTicket::where('truck_id', $id)
                ->whereIn('status', ['Draft', 'In-Transit'])
                ->where('is_archived', false)
                ->first();

            if ($activeTrip) {
                $tripNo = $activeTrip->trip_no ?? "ID {$activeTrip->id}";
                return response()->json([
                    'message' => "Truck {$truck->truck_code} cannot be deleted because it has an active trip ticket ({$tripNo}) with status \"{$activeTrip->status}\". Complete or cancel it first.",
                    'errors'  => ['truck_id' => ["Truck has an active trip ticket ({$activeTrip->status})."]],
                ], 422);
            }

            $snapshot = $this->modelSnapshot($truck);
            $truck->delete();

            $this->writeLog('deleted', 'truck', $id, $snapshot['truck_code'] ?? (string) $id, $snapshot, null, null, $request);

            return response()->json(['message' => 'Truck deleted successfully.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Truck not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete truck', ['truck_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while deleting the truck. Please try again.'], 500);
        }
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = trim($request->get('q', ''));
            if ($query === '') {
                return response()->json(['message' => 'Search query cannot be empty.'], 422);
            }
            $trucks = Truck::where(function ($q) use ($query) {
                    $q->where('truck_code',   'like', "%{$query}%")
                    ->orWhere('plate_number', 'like', "%{$query}%")
                    ->orWhere('model',        'like', "%{$query}%")
                    ->orWhere('notes',        'like', "%{$query}%");
                })
                ->orderBy('truck_code')
                ->get();
            if ($trucks->isEmpty()) {
                return response()->json([
                    'message' => "No trucks found matching \"{$query}\".",
                    'data'    => [],
                ], 404);
            }
            return response()->json(['data' => $trucks], 200);
        } catch (\Exception $e) {
            Log::error('Truck search failed', ['query' => $request->get('q'), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Search failed. Please try again.'], 500);
        }
    }

    public function filterByStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $statuses      = $request->query('statuses', []);
            $validStatuses = ['Available', 'In-Transit', 'Maintenance', 'Inactive'];
            if (!empty($statuses)) {
                $invalid = array_diff($statuses, $validStatuses);
                if (!empty($invalid)) {
                    return response()->json([
                        'message' => 'Invalid status value(s) provided.',
                        'errors'  => ['statuses' => ['Allowed values: Available, In-Transit, Maintenance, Inactive.']],
                    ], 422);
                }
            }
            $trucks = Truck::when(!empty($statuses), fn($q) => $q->whereIn('status', $statuses))
                ->orderBy('truck_code')
                ->get();
            if ($trucks->isEmpty()) {
                $label = !empty($statuses) ? implode(', ', $statuses) : 'any status';
                return response()->json([
                    'message' => "No trucks found for status: {$label}.",
                    'data'    => [],
                ], 404);
            }
            return response()->json(['data' => $trucks], 200);
        } catch (\Exception $e) {
            Log::error('Truck filter by status failed', ['statuses' => $request->get('statuses'), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Filter failed. Please try again.'], 500);
        }
    }

    private function checkAdminPassword(string $password): bool
    {
        $setting = AdminSetting::where('key', self::ADMIN_PASSWORD_KEY)->first();

        if (!$setting || !is_string($setting->value) || $setting->value === '') {
            return false;
        }

        return Hash::check($password, $setting->value);
    }
}