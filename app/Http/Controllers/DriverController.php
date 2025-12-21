<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::where('is_archived', false)->get();
        return view('admin', compact('drivers'));
    }

    public function archived()
    {
        $drivers = Driver::where('is_archived', true)->get();
        return response()->json($drivers);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'license_number' => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address' => 'required|string',
                'emergency_contact' => 'required|string|max:20',
                'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('driver_files', 'public');
            }

            $driver = Driver::create([
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'license_number' => $validated['license_number'],
                'license_expiry_date' => $validated['license_expiry_date'],
                'address' => $validated['address'],
                'emergency_contact' => $validated['emergency_contact'],
                'file_path' => $filePath,
                'status' => 'Available',
                'assigned_truck' => null,
            ]);

            return response()->json($driver);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding driver: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return response()->json($driver);
    }

    public function update(Request $request, $id)
    {
        try {
            $driver = Driver::findOrFail($id);

            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'license_number' => 'required|string|max:50',
                'license_expiry_date' => 'required|date',
                'address' => 'required|string',
                'emergency_contact' => 'required|string|max:20',
                'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            if ($request->hasFile('file')) {
                if ($driver->file_path) {
                    Storage::disk('public')->delete($driver->file_path);
                }
                $filePath = $request->file('file')->store('driver_files', 'public');
                $validated['file_path'] = $filePath;
            }

            // Only update fields that are in validated array
            $updateData = array_intersect_key($validated, array_flip([
                'full_name', 'phone_number', 'license_number', 
                'license_expiry_date', 'address', 'emergency_contact', 'file_path'
            ]));

            $driver->update($updateData);

            return response()->json($driver);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating driver: ' . $e->getMessage()
            ], 500);
        }
    }

    public function archive(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // In a real app, verify admin password here
        $driver = Driver::findOrFail($id);
        $driver->update(['is_archived' => true]);

        return response()->json(['message' => 'Driver archived successfully']);
    }

    public function unarchive(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // In a real app, verify admin password here
        $driver = Driver::findOrFail($id);
        $driver->update(['is_archived' => false]);

        return response()->json(['message' => 'Driver unarchived successfully']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $drivers = Driver::where('is_archived', false)
            ->where(function($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%")
                  ->orWhere('license_number', 'like', "%{$query}%");
            })
            ->get();

        return response()->json($drivers);
    }
}

