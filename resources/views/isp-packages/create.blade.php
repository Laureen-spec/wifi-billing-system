@extends('layouts.app')
@section('title', 'Create Package')
@section('page_heading', 'Create Package')
@section('content')
<form class="form" method="POST" action="{{ route('isp.packages.store') }}">@csrf
@include('isp-packages.partials.form', ['package' => null])
<button class="btn">Save Package</button> <a class="btn secondary" href="{{ route('isp.packages.index') }}">Cancel</a>
</form>
@endsection
