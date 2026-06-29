<?php

namespace StudyRoomTechLab\RentalManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use StudyRoomTechLab\RentalManagement\Models\RentalInvoice;
use StudyRoomTechLab\RentalManagement\Models\RentalPayment;
use StudyRoomTechLab\RentalManagement\Models\RentalTenant;

class RentalInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = RentalInvoice::with('tenant')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('rental-management::invoices.index', [
            'invoices' => $invoices,
            'tenants' => RentalTenant::where('status', 'active')->orderBy('name')->get(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:rental_tenants,id'],
            'invoice_type' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'billing_month' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $tenant = RentalTenant::with(['property', 'unit'])->findOrFail($data['tenant_id']);
        $data['property_id'] = $tenant->property_id;
        $data['unit_id'] = $tenant->unit_id;
        $data['invoice_number'] = 'RENT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $data['paid_amount'] = 0;
        $data['status'] = 'unpaid';

        RentalInvoice::create($data);

        return back()->with('success', 'Invoice created successfully.');
    }

    public function recordPayment(Request $request, RentalInvoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['invoice_id'] = $invoice->id;
        $data['tenant_id'] = $invoice->tenant_id;
        $data['payment_method'] = $data['payment_method'] ?? 'cash';
        $data['status'] = 'paid';
        $data['paid_at'] = now();
        $data['created_by'] = auth()->id();

        RentalPayment::create($data);

        $paid = $invoice->payments()->sum('amount');
        $invoice->forceFill([
            'paid_amount' => $paid,
            'status' => $paid >= (float) $invoice->amount ? 'paid' : 'partial',
        ])->save();

        return back()->with('success', 'Payment recorded successfully.');
    }
}
