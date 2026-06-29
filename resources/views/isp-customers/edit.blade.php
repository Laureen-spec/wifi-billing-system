@extends('layouts.app')
@section('title', 'Edit Customer')
@section('page_heading', 'Edit Customer')
@section('content')
<div class="sr-customer-shell">
    <div class="sr-page-head"><div><span class="sr-kicker">WiFi Billing</span><h1>Edit Customer</h1><p>Update service details, package, expiry, MikroTik router, and login credentials.</p></div><div class="actions"><a class="btn secondary" href="{{ route('isp.customers.show', $customer) }}">View Customer</a><a class="btn secondary" href="{{ route('isp.customers.index') }}">Back</a></div></div>
    <form class="sr-form-card" method="POST" action="{{ route('isp.customers.update', $customer) }}">@csrf @method('PATCH') @include('isp-customers.partials.form', ['customer' => $customer])<div class="sr-form-actions"><button class="btn" type="submit">Update Customer</button><a class="btn secondary" href="{{ route('isp.customers.show', $customer) }}">Cancel</a></div></form>
</div>
@endsection
