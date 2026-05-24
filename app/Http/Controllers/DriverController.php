<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\Driver;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    use LogsActivity;

    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    public function index()
    {
        $drivers = Driver::where('is_archived', false)->get();
        return view('admin', compact('drivers'));
    }

    public function archived()
    {
        try {
            $drivers = Driver::where('is_archived', true)->get();
            return response()->json($drivers);
        } catch (\Exception $e) {
            Log::error('Failed to load archived drivers', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to load archived drivers. Please try again.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name'           => 'required|string|max:255',
                'phone_number'        => 'required|digits_between:7,11',
                'license_number'      => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,11',
                'file'                => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('driver_files', 'public');
                if (!$filePath) {
                    return response()->json(['message' => 'File upload failed. Please try again.'], 500);
                }
            }

            $validated['file_path']      = $filePath;
            $validated['status']         = 'Available';
            $validated['assigned_truck'] = null;
            unset($validated['file']);

            $driver = Driver::create($validated);

            $this->writeLog('created', 'driver', $driver->id, $driver->full_name, null, $this->modelSnapshot($driver), null, $request);

            $driver->file_url = $filePath ? Storage::disk('public')->url($filePath) : null;

            return response()->json($driver, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create driver', ['input' => $request->except('file'), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while adding the driver. Please try again.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $driver->file_url = $driver->file_path ? Storage::disk('public')->url($driver->file_path) : null;
            return response()->json($driver);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Driver not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to load driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to load driver information. Please try again.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $oldSnapshot = $this->modelSnapshot($driver);

            $validated = $request->validate([
                'full_name'           => 'required|string|max:255',
                'phone_number'        => 'required|digits_between:7,11',
                'license_number'      => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,11',
                'file'                => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            if ($request->hasFile('file')) {
                $newPath = $request->file('file')->store('driver_files', 'public');
                if (!$newPath) {
                    return response()->json(['message' => 'File upload failed. Please try again.'], 500);
                }
                if ($driver->file_path) {
                    Storage::disk('public')->delete($driver->file_path);
                }
                $validated['file_path'] = $newPath;
            }

            $updateData = array_intersect_key($validated, array_flip([
                'full_name', 'phone_number', 'license_number',
                'license_expiry_date', 'address', 'emergency_contact', 'file_path',
            ]));

            $driver->update($updateData);

            $this->writeLog('updated', 'driver', $driver->id, $driver->full_name, $oldSnapshot, $this->modelSnapshot($driver->fresh()), null, $request);

            $driver->file_url = $driver->file_path ? Storage::disk('public')->url($driver->file_path) : null;

            return response()->json($driver);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Driver not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please check your inputs and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while updating the driver. Please try again.'], 500);
        }
    }

    public function archive(Request $request, $id)
    {
        try {
            $validated = $request->validate(['password' => 'required|string']);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed archive attempt — incorrect admin password', ['driver_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password.'], 403);
            }

            $driver = Driver::findOrFail($id);

            // Block archiving if driver is tied to any active trip ticket
            $activeTrip = \App\Models\TripTicket::where('driver_id', $driver->id)
                ->whereIn('status', ['Draft', 'In-Transit'])
                ->where('is_archived', false)
                ->first();

            if ($activeTrip) {
                return response()->json([
                    'message' => "This driver cannot be archived because they are currently assigned to trip ticket \"{$activeTrip->trip_no}\" with status \"{$activeTrip->status}\". Please cancel that trip ticket first.",
                ], 422);
            }

            $driver->update(['is_archived' => true]);
            $this->writeLog('archived', 'driver', $driver->id, $driver->full_name, null, null, null, $request);

            return response()->json(['message' => 'Driver archived successfully.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Driver not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while archiving the driver. Please try again.'], 500);
        }
    }

    public function unarchive(Request $request, $id)
    {
        try {
            $validated = $request->validate(['password' => 'required|string']);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed unarchive attempt — incorrect admin password', ['driver_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password.'], 403);
            }

            $driver = Driver::findOrFail($id);
            $driver->update(['is_archived' => false]);

            $this->writeLog('restored', 'driver', $driver->id, $driver->full_name, null, null, null, $request);

            return response()->json(['message' => 'Driver unarchived successfully.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Driver not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong while unarchiving the driver. Please try again.'], 500);
        }
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = trim($request->get('q', ''));
            if ($query === '') {
                return response()->json(['message' => 'Search query cannot be empty.'], 422);
            }
            $drivers = Driver::where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->where('full_name',      'like', "%{$query}%")
                    ->orWhere('phone_number', 'like', "%{$query}%")
                    ->orWhere('license_number','like', "%{$query}%");
                })->get();
            if ($drivers->isEmpty()) {
                return response()->json(['message' => "No drivers found matching \"{$query}\".", 'data' => []], 404);
            }
            return response()->json(['data' => $drivers], 200);
        } catch (\Exception $e) {
            Log::error('Driver search failed', ['query' => $request->get('q'), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Search failed. Please try again.'], 500);
        }
    }

    public function filterByStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $statuses      = $request->query('statuses', []);
            $validStatuses = ['Available', 'Covering'];
            if (!empty($statuses)) {
                $invalid = array_diff($statuses, $validStatuses);
                if (!empty($invalid)) {
                    return response()->json([
                        'message' => 'Invalid status value(s) provided.',
                        'errors'  => ['statuses' => ['Allowed values: Available, Covering.']],
                    ], 422);
                }
            }
            $drivers = Driver::where('is_archived', false)
                ->when(!empty($statuses), fn($q) => $q->whereIn('status', $statuses))
                ->get();
            if ($drivers->isEmpty()) {
                $label = !empty($statuses) ? implode(', ', $statuses) : 'any status';
                return response()->json(['message' => "No drivers found for status: {$label}.", 'data' => []], 404);
            }
            return response()->json(['data' => $drivers], 200);
        } catch (\Exception $e) {
            Log::error('Driver filter by status failed', ['statuses' => $request->get('statuses'), 'error' => $e->getMessage()]);
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