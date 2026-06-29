<?php

namespace StudyRoomTechLab\RentalManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RentalManagement\Models\RentalProperty;
use StudyRoomTechLab\RentalManagement\Models\RentalTenant;
use StudyRoomTechLab\RentalManagement\Models\RentalUnit;

class RentalTenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = RentalTenant::with(['property', 'unit'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($inner) use ($request) {
                $inner->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('phone', 'like', '%' . $request->q . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('rental-management::tenants.index', [
            'tenants' => $tenants,
            'properties' => RentalProperty::orderBy('name')->get(),
            'units' => RentalUnit::whereIn('status', ['vacant', 'reserved'])->orderBy('unit_number')->get(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['nullable', 'integer', 'exists:rental_properties,id'],
            'unit_id' => ['nullable', 'integer', 'exists:rental_units,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'move_in_date' => ['nullable', 'date'],
            'rent_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['unit_id'])) {
            $unit = RentalUnit::find($data['unit_id']);
            $data['property_id'] = $data['property_id'] ?? $unit?->property_id;
        }

        $data['status'] = 'active';
        $data['created_by'] = auth()->id();

        $tenant = RentalTenant::create($data);

        if ($tenant->unit_id) {
            RentalUnit::where('id', $tenant->unit_id)->update(['status' => 'occupied']);
        }

        return back()->with('success', 'Tenant added successfully.');
    }

    public function moveOut(RentalTenant $tenant)
    {
        $tenant->forceFill([
            'status' => 'moved_out',
            'move_out_date' => now()->toDateString(),
        ])->save();

        if ($tenant->unit_id) {
            RentalUnit::where('id', $tenant->unit_id)->update(['status' => 'vacant']);
        }

        return back()->with('success', 'Tenant moved out and unit marked vacant.');
    }
}
