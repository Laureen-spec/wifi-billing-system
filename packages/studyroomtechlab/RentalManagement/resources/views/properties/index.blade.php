@extends('layouts.app')
@section('title', 'Rental Properties')
@section('page_heading', 'Rental Properties')
@section('content')
@include('rental-management::partials-style')
<div class="rm-page">
@if(session('success'))<div class="rm-alert">{{ session('success') }}</div>@endif
<div class="rm-hero"><div><h1>Properties</h1><p>Add apartments, hostels, shops, or rental buildings.</p></div><div class="rm-actions"><a class="rm-btn secondary" href="{{ route('rental-management.dashboard') }}">Dashboard</a><a class="rm-btn" href="{{ route('rental-management.units.index') }}">Units</a></div></div>
<div class="rm-card"><form class="rm-form" method="POST" action="{{ route('rental-management.properties.store') }}">@csrf<input name="name" placeholder="Property name" required><input name="type" placeholder="Type e.g. apartment" value="apartment"><input name="location" placeholder="Location"><input name="manager_name" placeholder="Manager name"><input name="manager_phone" placeholder="Manager phone"><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select><textarea name="description" placeholder="Description"></textarea><button class="rm-btn" type="submit">Add Property</button></form>
<table class="rm-table"><thead><tr><th>Property</th><th>Location</th><th>Manager</th><th>Units</th><th>Tenants</th><th>Status</th></tr></thead><tbody>@forelse($properties as $property)<tr><td><strong>{{ $property->name }}</strong><br><span class="rm-muted">{{ ucfirst($property->type) }}</span></td><td>{{ $property->location ?? '-' }}</td><td>{{ $property->manager_name ?? '-' }}<br><span class="rm-muted">{{ $property->manager_phone }}</span></td><td>{{ $property->units_count }}</td><td>{{ $property->tenants_count }}</td><td><span class="rm-badge {{ $property->status }}">{{ ucfirst($property->status) }}</span></td></tr>@empty<tr><td colspan="6" class="rm-empty">No properties yet.</td></tr>@endforelse</tbody></table>@if(method_exists($properties,'links'))<div class="rm-pagination">{{ $properties->links() }}</div>@endif</div>
</div>
@endsection
