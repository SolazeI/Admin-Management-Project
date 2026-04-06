<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function index()
    {
        $trucks = Truck::orderBy('truck_code')->get();
        return view('fleet', compact('trucks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_code' => 'required|string|max:50|unique:trucks,truck_code',
            'plate_number' => 'nullable|string|max:50|unique:trucks,plate_number',
            'model' => 'nullable|string|max:100',
            'status' => 'required|in:Available,In-Transit,Maintenance,Inactive',
            'notes' => 'nullable|string',
        ]);

        Truck::create($validated);
        return redirect()->back()->with('success', 'Truck added.');
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'truck_code' => 'required|string|max:50|unique:trucks,truck_code,' . $truck->id,
            'plate_number' => 'nullable|string|max:50|unique:trucks,plate_number,' . $truck->id,
            'model' => 'nullable|string|max:100',
            'status' => 'required|in:Available,In-Transit,Maintenance,Inactive',
            'notes' => 'nullable|string',
        ]);

        $truck->update($validated);
        return redirect()->back()->with('success', 'Truck updated.');
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();
        return redirect()->back()->with('success', 'Truck deleted.');
    }
}
