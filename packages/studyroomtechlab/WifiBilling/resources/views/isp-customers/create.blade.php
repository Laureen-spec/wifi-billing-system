@extends('layouts.app')
@section('page-title','Create Customer')
@section('content')
<div style="max-width:900px;margin:0 auto"><h1>Create Customer</h1><div class="card p-4"><form method="POST" action="{{ route('isp.customers.store') }}">@csrf @include('wifi-billing::isp-customers.partials.form')<button class="btn btn-primary">Save Customer</button></form></div></div>
@endsection
