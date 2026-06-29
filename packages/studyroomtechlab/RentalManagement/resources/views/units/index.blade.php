@extends('layouts.app')
@section('title', 'Rental Units')
@section('page_heading', 'Rental Units')
@section('content')
@include('rental-management::partials-style')
<div class="rm-page">
@if(session('success'))<div class="rm-alert">{{ session('success') }}</div>@endif
<div class="rm-hero"><div><h1>Units / Rooms</h1><p>Create rooms and control vacancy status.</p></div><div class="rm-actions"><a class="rm-btn secondary" href="{{ route('rental-management.dashboard') }}">Dashboard</a><a class="rm-btn" href="{{ route('rental-management.tenants.index') }}">Tenants</a></div></div>
<div class="rm-card"><form class="rm-form" method="POST" action="{{ route('rental-management.units.store') }}">@csrf<select name="property_id" required><option value="">Select property</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select><input name="unit_number" placeholder="Unit/Room number" required><input name="unit_type" placeholder="Type" value="bedsitter"><input type="number" step="0.01" name="rent_amount" placeholder="Rent" required><input type="number" step="0.01" name="deposit_amount" placeholder="Deposit"><input type="number" name="capacity" placeholder="Capacity" value="1"><select name="status"><option value="vacant">Vacant</option><option value="reserved">Reserved</option><option value="occupied">Occupied</option><option value="maintenance">Maintenance</option></select><textarea name="notes" placeholder="Notes"></textarea><button class="rm-btn" type="submit">Add Unit</button></form>
<table class="rm-table"><thead><tr><th>Unit</th><th>Property</th><th>Rent</th><th>Deposit</th><th>Capacity</th><th>Status</th></tr></thead><tbody>@forelse($units as $unit)<tr><td><strong>{{ $unit->unit_number }}</strong><br><span class="rm-muted">{{ ucfirst($unit->unit_type) }}</span></td><td>{{ $unit->property?->name ?? '-' }}</td><td>KES {{ number_format($unit->rent_amount, 2) }}</td><td>KES {{ number_format($unit->deposit_amount, 2) }}</td><td>{{ $unit->capacity }}</td><td><span class="rm-badge {{ $unit->status }}">{{ ucfirst($unit->status) }}</span></td></tr>@empty<tr><td colspan="6" class="rm-empty">No units yet.</td></tr>@endforelse</tbody></table>@if(method_exists($units,'links'))<div class="rm-pagination">{{ $units->links() }}</div>@endif</div>
</div>
@endsection
