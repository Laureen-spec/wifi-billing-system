@extends('layouts.app')

@section('title', 'Edit MikroTik Link')
@section('page_heading', 'Edit MikroTik Link')

@section('content')
<form class="form" method="POST" action="{{ route('isp.routers.update', $router) }}">
    @csrf
    @method('PATCH')

    <div class="router-link-card">
        <div class="router-link-header">
            <div>
                <h1>Edit MikroTik Link</h1>
                <p>Update the device label or internal notes. Connection setup is handled by the link command.</p>
            </div>
        </div>

        @include('isp-routers.partials.form', ['router' => $router])

        <div class="router-actions">
            <button class="btn" type="submit">Update Link</button>
            <a class="btn secondary" href="{{ route('isp.routers.index') }}">Cancel</a>
        </div>
    </div>
</form>

<style>
.router-link-card{
    border:1px solid #e5e7eb;
    border-radius:18px;
    background:#fff;
    padding:24px;
    box-shadow:0 12px 30px rgba(15,23,42,.05);
}
.router-link-header{
    margin-bottom:20px;
}
.router-link-header h1{
    margin:0;
    font-size:28px;
    font-weight:800;
    color:#0f172a;
}
.router-link-header p{
    margin:6px 0 0;
    color:#64748b;
    font-size:15px;
}
.router-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:20px;
}
</style>
@endsection