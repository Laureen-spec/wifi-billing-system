@extends('layouts.app')
@section('title', 'Edit Customer')
@section('page_heading', 'Edit Customer')
@section('content')
<form class="form" method="POST" action="{{ route('isp.customers.update', $customer) }}">@csrf @method('PATCH')
@include('isp-customers.partials.form', ['customer' => $customer])
<button class="btn">Update Customer</button> <a class="btn secondary" href="{{ route('isp.customers.index') }}">Cancel</a>
</form>
@endsection
