@extends('layouts.app')
@section('title', 'Router Setup Script')
@section('page_heading', 'Router Setup Script')
@section('content')
<div class="page-title"><div><h1>{{ $router->name }}</h1><p>Copy this command into MikroTik Terminal.</p></div><a class="btn secondary" href="{{ route('isp.routers.index') }}">Back</a></div>
<div class="card">
    <h3>Fetch and Import Command</h3>
    <div class="code">{{ $command }}</div>
    <p class="muted">Public script URL: {{ $provisionUrl }}</p>
</div>
@endsection
