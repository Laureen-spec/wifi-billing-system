@extends('layouts.app')
@section('title', 'Edit Package')
@section('page_heading', 'Edit Package')
@section('content')
<form class="form" method="POST" action="{{ route('isp.packages.update', $package) }}">@csrf @method('PATCH')
@include('isp-packages.partials.form', ['package' => $package])
<button class="btn">Update Package</button> <a class="btn secondary" href="{{ route('isp.packages.index') }}">Cancel</a>
</form>
@endsection
