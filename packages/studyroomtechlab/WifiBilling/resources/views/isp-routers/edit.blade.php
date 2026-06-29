@extends('layouts.app')
@section('page-title','Edit MikroTik')
@section('content')<div style="max-width:900px;margin:auto"><h1>Edit MikroTik</h1><div class="card p-4"><form method="POST" action="{{ route('isp.routers.update',$router) }}">@csrf @method('PATCH') @include('wifi-billing::isp-routers.partials.form')<button class="btn btn-primary">Update</button></form></div></div>@endsection
