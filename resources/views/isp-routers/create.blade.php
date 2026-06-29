@extends('layouts.app')

@section('title', 'Link a MikroTik')
@section('page_heading', 'Link a MikroTik')

@section('content')
<form class="form" method="POST" action="{{ route('isp.routers.store') }}">
    @csrf

    <div class="router-link-card">
        <div class="router-link-header">
            <div>
                <h1>Link a MikroTik</h1>
                <p>Create a secure link command. Paste it once in MikroTik Terminal to connect the device.</p>
            </div>
        </div>

        <div class="router-link-steps">
            <div class="router-step active">
                <span>1</span>
                <div>
                    <strong>Name the device</strong>
                    <small>Add a friendly name for your team.</small>
                </div>
            </div>

            <div class="router-step">
                <span>2</span>
                <div>
                    <strong>Copy link command</strong>
                    <small>The command is generated after saving.</small>
                </div>
            </div>

            <div class="router-step">
                <span>3</span>
                <div>
                    <strong>Router connects back</strong>
                    <small>Provisioning handles the setup automatically.</small>
                </div>
            </div>
        </div>

        @include('isp-routers.partials.form', ['router' => null])

        <div class="router-actions">
            <button class="btn" type="submit">Generate Link Command</button>
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
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
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
.router-link-steps{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:14px;
    margin-bottom:22px;
}
.router-step{
    display:flex;
    gap:12px;
    align-items:center;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:14px;
    background:#f8fafc;
}
.router-step.active{
    background:#ecfdf5;
    border-color:#a7f3d0;
}
.router-step span{
    display:inline-flex;
    width:34px;
    height:34px;
    align-items:center;
    justify-content:center;
    border-radius:999px;
    background:#fee2e2;
    color:#dc2626;
    font-weight:800;
}
.router-step.active span{
    background:#10b981;
    color:#fff;
}
.router-step strong{
    display:block;
    font-size:14px;
    color:#0f172a;
}
.router-step small{
    display:block;
    margin-top:2px;
    color:#64748b;
}
.router-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:20px;
}
@media(max-width:1000px){
    .router-link-steps{
        grid-template-columns:1fr;
    }
}
</style>
@endsection