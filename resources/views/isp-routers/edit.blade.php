@extends('layouts.app')
@section('title', 'Edit Router')
@section('page_heading', 'Edit Router')
@section('content')
<form class="form" method="POST" action="{{ route('isp.routers.update', $router) }}">@csrf @method('PATCH')
@include('isp-routers.partials.form', ['router' => $router])
<button class="btn">Update Router</button> <a class="btn secondary" href="{{ route('isp.routers.index') }}">Cancel</a>
</form>
@endsection
