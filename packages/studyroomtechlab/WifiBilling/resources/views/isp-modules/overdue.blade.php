@extends('layouts.app')

@section('page-title', 'Overdue Accounts')
@section('title', 'Overdue Accounts')

@section('content')
<style>
    .sr-page{max-width:1180px;margin:0 auto}.sr-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.sr-hero h1{font-size:30px;margin:0 0 6px}.sr-hero p{margin:0;color:#64748b}.sr-card{background:#fff;border:1px solid #e8edf4;border-radius:22px;box-shadow:0 16px 40px rgba(15,23,42,.07);overflow:hidden;margin-bottom:18px}.sr-card h3{margin:0;padding:18px 20px;border-bottom:1px solid #eef2f7}.sr-table{width:100%;border-collapse:separate;border-spacing:0}.sr-table th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;text-align:left;background:#f8fafc;padding:14px 18px}.sr-table td{padding:16px 18px;border-top:1px solid #edf1f7}.sr-muted{color:#64748b;font-size:13px}.sr-badge{display:inline-flex;border-radius:999px;padding:5px 10px;background:#fee2e2;color:#991b1b;font-weight:800;font-size:12px}.sr-empty{padding:28px;color:#64748b;text-align:center}.sr-pagination{padding:14px 18px;border-top:1px solid #edf1f7}
</style>
<div class="sr-page">
    <div class="sr-hero">
        <div>
            <h1>Overdue Accounts</h1>
            <p>Customers and invoices whose due date has passed.</p>
        </div>
        <a class="btn secondary" href="{{ route('isp.customers.index') }}">Back to Customers</a>
    </div>

    <div class="sr-card">
        <h3>Overdue Customers</h3>
        <table class="sr-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><strong>{{ $customer->name }}</strong><div class="sr-muted">{{ $customer->username ?? $customer->phone }}</div></td>
                        <td>{{ $customer->internetPackage?->name ?? '-' }}</td>
                        <td>KES {{ number_format((float) $customer->monthly_amount, 2) }}</td>
                        <td>{{ $customer->next_due_date?->format('Y-m-d') ?? '-' }}</td>
                        <td><span class="sr-badge">{{ ucfirst($customer->billing_status ?? 'overdue') }}</span></td>
                        <td><a class="btn secondary" href="{{ route('isp.customers.show', $customer) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="sr-empty">No overdue customers found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($customers, 'links'))<div class="sr-pagination">{{ $customers->links() }}</div>@endif
    </div>

    <div class="sr-card">
        <h3>Overdue Invoices</h3>
        <table class="sr-table">
            <thead>
                <tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Balance</th><th>Due Date</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number ?? ('#' . ($invoice->id ?? '-')) }}</td>
                        <td>{{ $invoice->customer_id ?? '-' }}</td>
                        <td>KES {{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                        <td>KES {{ number_format((float) ($invoice->balance_amount ?? 0), 2) }}</td>
                        <td>{{ $invoice->due_date ?? '-' }}</td>
                        <td><span class="sr-badge">{{ ucfirst($invoice->status ?? 'overdue') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="sr-empty">No overdue invoices found or invoice table not enabled.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($invoices, 'links'))<div class="sr-pagination">{{ $invoices->links() }}</div>@endif
    </div>
</div>
@endsection
