@extends('layouts.app')
@section('page-title','Add MikroTik')
@section('content')<div style="max-width:900px;margin:auto"><h1>Add MikroTik</h1><div class="card p-4"><form method="POST" action="{{ route('isp.routers.store') }}">@csrf @include('wifi-billing::isp-routers.partials.form')<button class="btn btn-primary">Save & Generate Provisioning</button></form></div></div>@endsection
