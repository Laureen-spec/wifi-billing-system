<?php

namespace StudyRoomTechLab\RepairPos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RepairPos\Models\RepairCustomer;
use StudyRoomTechLab\RepairPos\Models\RepairJob;

class RepairJobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = RepairJob::with('customer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($inner) use ($request) {
                $inner->where('customer_name', 'like', '%' . $request->q . '%')
                    ->orWhere('phone', 'like', '%' . $request->q . '%')
                    ->orWhere('device_model', 'like', '%' . $request->q . '%')
                    ->orWhere('serial_number', 'like', '%' . $request->q . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('repair-pos::jobs.index', [
            'jobs' => $jobs,
            'customers' => RepairCustomer::orderBy('name')->get(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:repair_pos_customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'device_type' => ['required', 'string', 'max:100'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'issue' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'string', 'max:50'],
            'expected_pickup_date' => ['nullable', 'date'],
        ]);

        if (! empty($data['customer_id'])) {
            $customer = RepairCustomer::find($data['customer_id']);
            $data['customer_name'] = $data['customer_name'] ?: $customer?->name;
            $data['phone'] = $data['phone'] ?: $customer?->phone;
        }

        $data['estimated_cost'] = $data['estimated_cost'] ?? 0;
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['status'] = 'received';
        $data['created_by'] = auth()->id();

        RepairJob::create($data);

        return back()->with('success', 'Repair job created successfully.');
    }

    public function updateStatus(Request $request, RepairJob $job)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'final_cost' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'diagnosis' => ['nullable', 'string'],
            'warranty_expiry' => ['nullable', 'date'],
        ]);

        $job->forceFill(array_filter($data, fn ($value) => $value !== null))->save();

        return back()->with('success', 'Repair job updated successfully.');
    }
}
