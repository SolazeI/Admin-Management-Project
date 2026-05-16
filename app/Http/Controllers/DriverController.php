<?php

namespace App\Http\Controllers;

use App\Models\AdminSetting;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
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
            Log::error('Failed to load archived drivers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Unable to load archived drivers. Please try again.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name'           => 'required|string|max:255',
                'phone_number'        => 'required|digits_between:7,15',
                'license_number'      => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,15',
                'file'                => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('driver_files', 'public');

                if (!$filePath) {
                    Log::error('Driver file upload failed during store', [
                        'original_name' => $request->file('file')->getClientOriginalName(),
                        'size'          => $request->file('file')->getSize(),
                    ]);
                    return response()->json([
                        'message' => 'File upload failed. Please try again.',
                    ], 500);
                }
            }

            $validated['file_path']      = $filePath;
            $validated['status']         = 'Available';
            $validated['assigned_truck'] = null;
            unset($validated['file']);

            $driver = Driver::create($validated);

            $driver->file_url = $filePath
                ? Storage::disk('public')->url($filePath)
                : null;

            return response()->json($driver, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create driver', [
                'input' => $request->except('file'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while adding the driver. Please try again.',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $driver = Driver::findOrFail($id);

            $driver->file_url = $driver->file_path
                ? Storage::disk('public')->url($driver->file_path)
                : null;

            return response()->json($driver);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Driver not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to load driver', [
                'driver_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Unable to load driver information. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);

            $validated = $request->validate([
                'full_name'           => 'required|string|max:255',
                'phone_number'        => 'required|digits_between:7,15',
                'license_number'      => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address'             => 'required|string|max:500',
                'emergency_contact'   => 'required|digits_between:7,15',
                'file'                => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            if ($request->hasFile('file')) {
                $newPath = $request->file('file')->store('driver_files', 'public');

                if (!$newPath) {
                    Log::error('Driver file upload failed during update', [
                        'driver_id'     => $id,
                        'original_name' => $request->file('file')->getClientOriginalName(),
                        'size'          => $request->file('file')->getSize(),
                    ]);
                    return response()->json([
                        'message' => 'File upload failed. Please try again.',
                    ], 500);
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

            $driver->file_url = $driver->file_path
                ? Storage::disk('public')->url($driver->file_path)
                : null;

            return response()->json($driver);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Driver not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update driver', [
                'driver_id' => $id,
                'input'     => $request->except('file'),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while updating the driver. Please try again.',
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
                    'driver_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect password.',
                ], 403);
            }

            $driver = Driver::findOrFail($id);
            $driver->update(['is_archived' => true]);

            return response()->json(['message' => 'Driver archived successfully.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Driver not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to archive driver', [
                'driver_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while archiving the driver. Please try again.',
            ], 500);
        }
    }

    public function unarchive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            if (!$this->checkAdminPassword($validated['password'])) {
                Log::warning('Failed unarchive attempt — incorrect admin password', [
                    'driver_id' => $id,
                    'ip'        => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Incorrect password.',
                ], 403);
            }

            $driver = Driver::findOrFail($id);
            $driver->update(['is_archived' => false]);

            return response()->json(['message' => 'Driver unarchived successfully.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Driver not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please enter the admin password.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to unarchive driver', [
                'driver_id' => $id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while unarchiving the driver. Please try again.',
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('q');

            $drivers = Driver::where('is_archived', false)
                ->where(function ($q) use ($query) {
                    $q->where('full_name', 'like', "%{$query}%")
                      ->orWhere('phone_number', 'like', "%{$query}%")
                      ->orWhere('license_number', 'like', "%{$query}%");
                })
                ->get();

            return response()->json($drivers);

        } catch (\Exception $e) {
            Log::error('Driver search failed', [
                'query' => $request->get('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }

    public function filterByStatus(Request $request)
    {
        try {
            $statuses = $request->get('statuses', []);

            $query = Driver::where('is_archived', false)
                ->withCount('trips as total_trips_count');

            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }

            return response()->json($query->get());

        } catch (\Exception $e) {
            Log::error('Driver filter by status failed', [
                'statuses' => $request->get('statuses'),
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Filter failed. Please try again.',
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
}