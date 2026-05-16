<?php
namespace App\Http\Controllers;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class TruckController extends Controller
{
    public function index()
    {
        try {
            $trucks = Truck::orderBy('truck_code')->get();
            return view('fleet', compact('trucks'));
        } catch (\Exception $e) {
            Log::error('Failed to load trucks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Unable to load trucks. Please try again.',
            ], 500);
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

            return response()->json($truck, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create truck', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while adding the truck. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $truck = Truck::findOrFail($id);

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

            return response()->json($truck);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Truck not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Please check your inputs and try again.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update truck', [
                'truck_id' => $id,
                'input'    => $request->all(),
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while updating the truck. Please try again.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $truck = Truck::findOrFail($id);
            $truck->delete();

            return response()->json(['message' => 'Truck deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Truck not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete truck', [
                'truck_id' => $id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Something went wrong while deleting the truck. Please try again.',
            ], 500);
        }
    }
}