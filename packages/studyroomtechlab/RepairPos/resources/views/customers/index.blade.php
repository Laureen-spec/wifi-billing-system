@extends('layouts.app')
@section('title', 'Repair Customers')
@section('page_heading', 'Repair Customers')
@section('content')
@include('repair-pos::partials-style')
<div class="rp-page">
@if(session('success'))<div class="rp-alert">{{ session('success') }}</div>@endif
<div class="rp-hero"><div><h1>Customers</h1><p>Customer list for repair jobs and POS sales.</p></div><div class="rp-actions"><a class="rp-btn secondary" href="{{ route('repair-pos.dashboard') }}">Dashboard</a><a class="rp-btn" href="{{ route('repair-pos.jobs.index') }}">Repair Jobs</a></div></div>
<div class="rp-card"><form class="rp-form" method="POST" action="{{ route('repair-pos.customers.store') }}">@csrf<input name="name" placeholder="Customer name" required><input name="phone" placeholder="Phone"><input type="email" name="email" placeholder="Email"><input name="location" placeholder="Location"><textarea name="notes" placeholder="Notes"></textarea><button class="rp-btn" type="submit">Add Customer</button></form>
<table class="rp-table"><thead><tr><th>Customer</th><th>Location</th><th>Jobs</th><th>Notes</th></tr></thead><tbody>@forelse($customers as $customer)<tr><td><strong>{{ $customer->name }}</strong><br><span class="rp-muted">{{ $customer->phone }} {{ $customer->email ? '• '.$customer->email : '' }}</span></td><td>{{ $customer->location ?? '-' }}</td><td>{{ $customer->jobs_count }}</td><td>{{ \Illuminate\Support\Str::limit($customer->notes, 80) }}</td></tr>@empty<tr><td colspan="4" class="rp-empty">No customers yet.</td></tr>@endforelse</tbody></table>@if(method_exists($customers,'links'))<div class="rp-pagination">{{ $customers->links() }}</div>@endif</div>
</div>
@endsection
