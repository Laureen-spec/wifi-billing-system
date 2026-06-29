<?php

namespace StudyRoomTechLab\RentalManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RentalManagement\Models\RentalProperty;
use StudyRoomTechLab\RentalManagement\Models\RentalUnit;

class RentalUnitController extends Controller
{
    public function index(Request $request)
    {
        $units = RentalUnit::with('property')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('property_id'), fn ($query) => $query->where('property_id', $request->property_id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('rental-management::units.index', [
            'units' => $units,
            'properties' => RentalProperty::orderBy('name')->get(),
            'filters' => $request->only(['status', 'property_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:rental_properties,id'],
            'unit_number' => ['required', 'string', 'max:100'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['unit_type'] = $data['unit_type'] ?? 'bedsitter';
        $data['deposit_amount'] = $data['deposit_amount'] ?? 0;
        $data['capacity'] = $data['capacity'] ?? 1;
        $data['status'] = $data['status'] ?? 'vacant';

        RentalUnit::create($data);

        return back()->with('success', 'Unit created successfully.');
    }
}
