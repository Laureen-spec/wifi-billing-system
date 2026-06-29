@extends('layouts.app')
@section('title', 'StudyRoom TechLab Billing Dashboard')
@section('page_heading', 'Platform Dashboard')
@section('content')
<div class="page-title">
    <div>
        <h1>StudyRoom TechLab Billing</h1>
        <p>Platform overview for ISP billing operations.</p>
    </div>
    <a class="btn" href="{{ route('isps.create') }}">Add ISP</a>
</div>
<div class="grid grid-4">
    <div class="card"><div class="muted">Total ISPs</div><div class="stat">{{ $totalIsps }}</div></div>
    <div class="card"><div class="muted">Active ISPs</div><div class="stat">{{ $activeIsps }}</div></div>
    <div class="card"><div class="muted">Packages</div><div class="stat">{{ $packageCount }}</div></div>
    <div class="card"><div class="muted">Customers</div><div class="stat">{{ $customerCount }}</div></div>
</div>
<div class="grid grid-2" style="margin-top:16px">
    <div class="card"><h3>MikroTik Routers</h3><div class="stat">{{ $routerCount }}</div><p class="muted">Routers registered across all ISPs.</p></div>
    <div class="card"><h3>Next Steps</h3><p class="muted">Create an ISP, assign an ISP admin, then add packages, customers, and MikroTik routers.</p></div>
</div>
@endsection
