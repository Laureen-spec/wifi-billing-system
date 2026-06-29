@extends('layouts.app')
@section('page-title','Edit Customer')
@section('content')
<div style="max-width:900px;margin:0 auto"><h1>Edit Customer</h1><div class="card p-4"><form method="POST" action="{{ route('isp.customers.update',$customer) }}">@csrf @method('PATCH') @include('wifi-billing::isp-customers.partials.form')<button class="btn btn-primary">Update Customer</button></form></div></div>
@endsection
