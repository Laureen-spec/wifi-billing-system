@extends('layouts.app')
@section('title', 'Create Customer')
@section('page_heading', 'Create Customer')
@section('content')
<div class="sr-customer-shell">
    <div class="sr-page-head"><div><span class="sr-kicker">WiFi Billing</span><h1>Create Customer</h1><p>Create a Hotspot, PPPoE, or IP binding customer with package, expiry, device limit, and MikroTik target.</p></div><a class="btn secondary" href="{{ route('isp.customers.index') }}">Back to Customers</a></div>
    <form class="sr-form-card" method="POST" action="{{ route('isp.customers.store') }}">@csrf @include('isp-customers.partials.form', ['customer' => null])<div class="sr-form-actions"><button class="btn" type="submit">Create Customer</button><a class="btn secondary" href="{{ route('isp.customers.index') }}">Cancel</a></div></form>
</div>
@endsection
