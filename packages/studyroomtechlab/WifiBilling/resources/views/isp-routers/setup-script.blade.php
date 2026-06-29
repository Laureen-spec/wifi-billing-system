@extends('layouts.app')
@section('page-title','MikroTik Provisioning Command')
@section('content')
<div style="max-width:1000px;margin:auto"><h1>MikroTik Provisioning Command</h1><p>This command installs Agent Mode and syncs all hotspot files: login, status, logout, error, redirect and md5.js.</p>@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="GET" class="card p-3 mb-3"><label>RouterOS Version</label><select name="ros" class="form-control" onchange="this.form.submit()"><option value="6" @selected(($ros ?? '6')=='6')>RouterOS 6.x / older</option><option value="7" @selected(($ros ?? '6')=='7')>RouterOS 7.x</option></select></form>
<div class="card p-4"><label>Paste this in MikroTik Terminal</label><textarea class="form-control" rows="5" onclick="this.select()">{{ $command }}</textarea><p><b>Provision URL:</b> {{ $provisionUrl }}</p></div></div>
@endsection
