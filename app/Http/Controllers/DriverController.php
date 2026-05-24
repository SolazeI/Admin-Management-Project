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
            return response()->json(['message' => 'We couldn\'t load the archived drivers. Please refresh and try again.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name'           => 'required|string|min:2|max:255',
                'phone_number'        => 'required|digits_between:7,15|unique:drivers,phone_number',
                'license_number'      => 'required|string|max:50|unique:drivers,license_number',
                'license_expiry_date' => 'required|date|after:today',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,15',
                'file'                => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ], [
                'full_name.required'              => 'Please enter the driver\'s full name.',
                'full_name.min'                   => 'Full name must be at least 2 characters.',
                'full_name.max'                   => 'Full name is too long (maximum 255 characters).',
                'phone_number.required'           => 'Please enter a phone number.',
                'phone_number.digits_between'     => 'Phone number must be between 7 and 15 digits.',
                'phone_number.unique'             => 'This phone number is already registered to another driver.',
                'license_number.required'         => 'Please enter the driver\'s license number.',
                'license_number.unique'           => 'This license number is already registered to another driver.',
                'license_number.max'              => 'License number is too long (maximum 50 characters).',
                'license_expiry_date.required'    => 'Please enter the license expiry date.',
                'license_expiry_date.date'        => 'Please enter a valid date for the license expiry.',
                'license_expiry_date.after'       => 'License expiry date must be in the future. Expired licenses cannot be registered.',
                'address.required'                => 'Please enter the driver\'s address.',
                'address.max'                     => 'Address is too long (maximum 500 characters).',
                'emergency_contact.required'      => 'Please enter an emergency contact number.',
                'emergency_contact.digits_between'=> 'Emergency contact must be between 7 and 15 digits.',
                'file.required'                   => 'Please upload a document (license or ID).',
                'file.max'                        => 'File is too large. Maximum allowed size is 10MB.',
                'file.mimes'                      => 'Only PDF, Word documents, and image files (JPG, PNG) are allowed.',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('driver_files', 'public');
                if (!$filePath) {
                    return response()->json(['message' => 'The document couldn\'t be uploaded. Please try again.'], 500);
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
            return response()->json(['message' => 'Some fields need your attention. Please review and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create driver', ['input' => $request->except('file'), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We couldn\'t add this driver right now. Please try again in a moment.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $driver->file_url = $driver->file_path ? Storage::disk('public')->url($driver->file_path) : null;
            return response()->json($driver);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'This driver no longer exists or may have been removed.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to load driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We couldn\'t load this driver\'s information. Please try again.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);
            $oldSnapshot = $this->modelSnapshot($driver);

            $validated = $request->validate([
                'full_name'           => 'required|string|min:2|max:255',
                'phone_number'        => 'required|digits_between:7,15|unique:drivers,phone_number,' . $id,
                'license_number'      => 'required|string|max:50|unique:drivers,license_number,' . $id,
                'license_expiry_date' => 'required|date|after:today',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,15',
                'file'                => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ], [
                'full_name.required'              => 'Please enter the driver\'s full name.',
                'full_name.min'                   => 'Full name must be at least 2 characters.',
                'full_name.max'                   => 'Full name is too long (maximum 255 characters).',
                'phone_number.required'           => 'Please enter a phone number.',
                'phone_number.digits_between'     => 'Phone number must be between 7 and 15 digits.',
                'phone_number.unique'             => 'This phone number is already used by another driver.',
                'license_number.required'         => 'Please enter the driver\'s license number.',
                'license_number.unique'           => 'This license number is already registered to another driver.',
                'license_number.max'              => 'License number is too long (maximum 50 characters).',
                'license_expiry_date.required'    => 'Please enter the license expiry date.',
                'license_expiry_date.date'        => 'Please enter a valid date for the license expiry.',
                'license_expiry_date.after'       => 'License expiry date must be in the future.',
                'address.required'                => 'Please enter the driver\'s address.',
                'address.max'                     => 'Address is too long (maximum 500 characters).',
                'emergency_contact.required'      => 'Please enter an emergency contact number.',
                'emergency_contact.digits_between'=> 'Emergency contact must be between 7 and 15 digits.',
                'file.max'                        => 'File is too large. Maximum allowed size is 10MB.',
                'file.mimes'                      => 'Only PDF, Word documents, and image files (JPG, PNG) are allowed.',
            ]);

            if ($request->hasFile('file')) {
                $newPath = $request->file('file')->store('driver_files', 'public');
                if (!$newPath) {
                    return response()->json(['message' => 'The document couldn\'t be uploaded. Please try again.'], 500);
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
            return response()->json(['message' => 'This driver no longer exists or may have been removed.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Some fields need your attention. Please review and try again.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We couldn\'t save the changes. Please try again in a moment.'], 500);
        }
    }

    public function archive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|max:255',
            ], [
                'password.required' => 'Please enter the admin password to continue.',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed archive attempt — incorrect admin password', ['driver_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password. Please try again.'], 401);
            }

            $driver = Driver::findOrFail($id);

            $activeTrip = \App\Models\TripTicket::where('driver_id', $driver->id)
                ->whereIn('status', ['Draft', 'In-Transit'])
                ->where('is_archived', false)
                ->first();

            if ($activeTrip) {
                return response()->json([
                    'message' => "Unable to archive {$driver->full_name}. They are currently assigned to trip \"{$activeTrip->trip_no}\" ({$activeTrip->status}). Please complete or cancel that trip first.",
                ], 422);
            }

            $driver->update(['is_archived' => true]);
            $this->writeLog('archived', 'driver', $driver->id, $driver->full_name, null, null, null, $request);

            return response()->json(['message' => "{$driver->full_name} has been archived successfully."]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'This driver no longer exists or may have been removed.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password to continue.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We couldn\'t archive this driver right now. Please try again.'], 500);
        }
    }

    public function unarchive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|max:255',
            ], [
                'password.required' => 'Please enter the admin password to continue.',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed unarchive attempt — incorrect admin password', ['driver_id' => $id, 'ip' => $request->ip()]);
                return response()->json(['message' => 'Incorrect password. Please try again.'], 401);
            }

            $driver = Driver::findOrFail($id);
            $driver->update(['is_archived' => false]);

            $this->writeLog('restored', 'driver', $driver->id, $driver->full_name, null, null, null, $request);

            return response()->json(['message' => "{$driver->full_name} has been restored and is now active."]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'This driver no longer exists or may have been removed.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Please enter the admin password to continue.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive driver', ['driver_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We couldn\'t restore this driver right now. Please try again.'], 500);
        }
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = trim($request->get('q', ''));
            if ($query === '') {
                return response()->json(['message' => 'Please enter a name, phone number, or license number to search.'], 422);
            }
            if (strlen($query) > 100) {
                return response()->json(['message' => 'Search query is too long. Please shorten it and try again.'], 422);
            }
            $drivers = Driver::where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->where('full_name',       'like', "%{$query}%")
                      ->orWhere('phone_number',  'like', "%{$query}%")
                      ->orWhere('license_number','like', "%{$query}%");
                })->get();
            if ($drivers->isEmpty()) {
                return response()->json(['message' => "No drivers found matching \"{$query}\". Try a different name or number.", 'data' => []], 404);
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
                        'message' => 'One or more selected filters are invalid. Please refresh and try again.',
                        'errors'  => ['statuses' => ['Allowed values: Available, Covering.']],
                    ], 422);
                }
            }
            $drivers = Driver::where('is_archived', false)
                ->when(!empty($statuses), fn($q) => $q->whereIn('status', $statuses))
                ->get();
            if ($drivers->isEmpty()) {
                $label = !empty($statuses) ? implode(', ', $statuses) : 'any status';
                return response()->json(['message' => "No drivers found with status: {$label}.", 'data' => []], 404);
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