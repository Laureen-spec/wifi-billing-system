@extends('layouts.app')
@section('title', 'Create Customer')
@section('page_heading', 'Create Customer')
@section('content')
<form class="form" method="POST" action="{{ route('isp.customers.store') }}">@csrf
@include('isp-customers.partials.form', ['customer' => null])
<button class="btn">Save Customer</button> <a class="btn secondary" href="{{ route('isp.customers.index') }}">Cancel</a>
</form>
@endsection
