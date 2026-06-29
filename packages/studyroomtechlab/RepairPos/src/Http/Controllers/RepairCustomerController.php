<?php

namespace StudyRoomTechLab\RepairPos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RepairPos\Models\RepairCustomer;

class RepairCustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = RepairCustomer::withCount('jobs')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($inner) use ($request) {
                $inner->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('phone', 'like', '%' . $request->q . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('repair-pos::customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();
        RepairCustomer::create($data);

        return back()->with('success', 'Customer saved successfully.');
    }
}
