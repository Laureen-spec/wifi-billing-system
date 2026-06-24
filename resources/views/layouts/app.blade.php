<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'StudyRoom TechLab Billing')</title>
    <style>
        :root{--bg:#f4f7fb;--panel:#fff;--line:#dfe7f1;--text:#172033;--muted:#64748b;--brand:#0f766e;--brand-dark:#115e59;--danger:#b91c1c;--ok:#15803d}
        *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;font-size:14px}
        a{color:inherit;text-decoration:none}.app{display:flex;min-height:100vh}.sidebar{width:260px;background:#0f172a;color:#e5edf7;position:fixed;inset:0 auto 0 0;display:flex;flex-direction:column}
        .brand{padding:22px 20px;border-bottom:1px solid rgba(255,255,255,.1)}.brand strong{display:block;font-size:18px}.brand span{display:block;color:#93a4b8;margin-top:4px;font-size:12px}
        .nav{padding:14px}.nav a{display:block;padding:11px 12px;border-radius:8px;color:#cbd5e1;margin-bottom:4px}.nav a:hover,.nav a.active{background:rgba(20,184,166,.18);color:#fff}
        .main{margin-left:260px;min-height:100vh;flex:1}.topbar{height:64px;background:var(--panel);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:5}
        .content{padding:24px}.page-title{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.page-title h1{font-size:24px;margin:0}.page-title p{margin:5px 0 0;color:var(--muted)}
        .grid{display:grid;gap:16px}.grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .card{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,.04)}.stat{font-size:28px;font-weight:700;margin-top:8px}.muted{color:var(--muted)}
        .table-wrap{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px 14px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}th{background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em}tr:last-child td{border-bottom:0}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 12px;border-radius:7px;border:1px solid transparent;background:var(--brand);color:#fff;font-weight:600;cursor:pointer}.btn:hover{background:var(--brand-dark)}.btn.secondary{background:#fff;color:#0f172a;border-color:var(--line)}.btn.danger{background:var(--danger)}
        .actions{display:flex;gap:8px;flex-wrap:wrap}.badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}.badge.ok{background:#dcfce7;color:#166534}.badge.warn{background:#fef3c7;color:#92400e}.badge.bad{background:#fee2e2;color:#991b1b}
        .form{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:18px}.field{margin-bottom:14px}label{display:block;font-weight:700;margin-bottom:6px}input,select,textarea{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:var(--text)}textarea{min-height:92px}.error{color:var(--danger);font-size:12px;margin-top:4px}.alert{padding:12px 14px;border-radius:8px;margin-bottom:16px}.alert.success{background:#dcfce7;color:#166534}.alert.error{background:#fee2e2;color:#991b1b}
        .code{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:14px;font-family:Consolas,monospace;overflow:auto}.pagination{margin-top:14px}.pagination nav{display:flex;gap:6px}
        @media(max-width:900px){.sidebar{position:static;width:100%}.app{display:block}.main{margin-left:0}.grid-4,.grid-3,.grid-2{grid-template-columns:1fr}.topbar{position:static}}
    </style>
</head>
<body>
<div class="app">
    @include('layouts.sidebar')
    <main class="main">
        @include('layouts.topbar')
        <section class="content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
