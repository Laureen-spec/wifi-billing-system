@extends('layouts.app')
@section('title', 'Customers')
@section('page_heading', 'Customers')
@section('content')
<div class="page-title"><div><h1>Customers</h1><p>Manage customer billing and connection status.</p></div><a class="btn" href="{{ route('isp.customers.create') }}">Add Customer</a></div>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>ISP</th><th>Package</th><th>Status</th><th>Billing</th><th>Due</th><th>Actions</th></tr></thead><tbody>
@forelse($customers as $customer)
<tr>
<td><strong>{{ $customer->name }}</strong><div class="muted">{{ $customer->phone }} {{ $customer->email }}</div></td>
<td>{{ $customer->isp?->name }}</td><td>{{ $customer->internetPackage?->name ?: '-' }}</td>
<td><span class="badge">{{ ucfirst($customer->connection_status) }}</span></td>
<td><span class="badge {{ $customer->billing_status === 'paid' ? 'ok' : ($customer->billing_status === 'overdue' ? 'bad' : 'warn') }}">{{ ucfirst($customer->billing_status) }}</span><div class="muted">{{ number_format($customer->monthly_amount, 2) }}</div></td>
<td>{{ $customer->next_due_date?->format('Y-m-d') ?: '-' }}</td><td><a class="btn secondary" href="{{ route('isp.customers.edit', $customer) }}">Edit</a></td>
</tr>
@empty
<tr><td colspan="7" class="muted">No customers found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $customers->links() }}</div>
@endsection
