@extends('layouts.app')
@section('title', 'ISP Dashboard')
@section('page_heading', 'ISP Dashboard')
@section('content')
<div class="page-title">
    <div>
        <h1>{{ $isp?->name ?? 'No ISP Assigned' }}</h1>
        <p>Manage packages, customers, and MikroTik routers.</p>
    </div>
    <a class="btn" href="{{ route('isp.customers.create') }}">Add Customer</a>
</div>
@if(!$isp)
    <div class="alert error">No ISP is assigned to this account yet. Ask a platform admin to assign one.</div>
@endif
<div class="grid grid-3">
    <div class="card"><div class="muted">Packages</div><div class="stat">{{ $packageCount }}</div></div>
    <div class="card"><div class="muted">Customers</div><div class="stat">{{ $customerCount }}</div></div>
    <div class="card"><div class="muted">MikroTik Routers</div><div class="stat">{{ $routerCount }}</div></div>
</div>
@endsection
