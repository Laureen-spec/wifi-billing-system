<?php

namespace StudyRoomTechLab\RentalManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RentalManagement\Models\RentalProperty;

class RentalPropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = RentalProperty::withCount(['units', 'tenants'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%' . $request->q . '%')->orWhere('location', 'like', '%' . $request->q . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('rental-management::properties.index', compact('properties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $data['type'] = $data['type'] ?? 'apartment';
        $data['status'] = $data['status'] ?? 'active';
        $data['created_by'] = auth()->id();

        RentalProperty::create($data);

        return back()->with('success', 'Property created successfully.');
    }
}
