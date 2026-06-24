@extends('layouts.app')
@section('title', 'Create Router')
@section('page_heading', 'Create Router')
@section('content')
<form class="form" method="POST" action="{{ route('isp.routers.store') }}">@csrf
@include('isp-routers.partials.form', ['router' => null])
<button class="btn">Save Router</button> <a class="btn secondary" href="{{ route('isp.routers.index') }}">Cancel</a>
</form>
@endsection
