<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'StudyRoom Connect')</title>
    <style>
        :root{--bg:#fff;--panel:#fff;--sidebar:#fafafa;--active:#f1f1f3;--line:#e5e7eb;--text:#111827;--muted:#6b7280;--brand:#10b981;--brand-dark:#059669;--danger:#b91c1c;--ok:#15803d}
        *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;font-size:14px}
        a{color:inherit;text-decoration:none}.app{display:flex;min-height:100vh}.sidebar{width:255px;background:var(--sidebar);color:#4b5563;position:fixed;inset:0 auto 0 0;display:flex;flex-direction:column;border-right:1px solid var(--line);padding:16px 12px}
        .brand{height:72px;border-radius:8px;background:#f4f4f5;display:flex;align-items:center;justify-content:center;margin-bottom:12px}.brand img{max-width:132px;max-height:42px}.brand strong{display:block;font-size:18px;color:#111827}.brand span{display:block;color:#6b7280;margin-top:4px;font-size:12px}
        .menu-search{padding:0 0 12px}.menu-search input{height:42px;border-radius:8px;background:#fff;color:#6b7280}
        .nav{overflow:auto;padding-bottom:18px}.nav a,.nav .nav-parent{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:#4b5563;margin-bottom:4px;font-size:15px}.nav a:hover,.nav a.active,.nav .nav-parent.active{background:var(--active);color:#18181b;font-weight:700}.nav .nav-group{margin-bottom:4px}.nav .nav-children{margin-left:12px;border-left:2px solid #e5e7eb;padding-left:14px}.nav .nav-children a{font-size:14px;padding:8px 10px}.nav svg{width:18px;height:18px;flex:0 0 auto;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
        .main{margin-left:255px;min-height:100vh;flex:1}.topbar{height:64px;background:var(--panel);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:5;box-shadow:0 1px 2px rgba(15,23,42,.04)}
        .content{padding:24px;background:#f8fafc;min-height:calc(100vh - 64px)}.page-title{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.page-title h1{font-size:24px;margin:0}.page-title p{margin:5px 0 0;color:var(--muted)}
        .grid{display:grid;gap:16px}.grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .card{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,.04)}.stat{font-size:28px;font-weight:700;margin-top:8px}.muted{color:var(--muted)}
        .table-wrap{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px 14px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}th{background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em}tr:last-child td{border-bottom:0}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 12px;border-radius:7px;border:1px solid transparent;background:var(--brand);color:#fff;font-weight:600;cursor:pointer}.btn:hover{background:var(--brand-dark)}.btn.secondary{background:#fff;color:#0f172a;border-color:var(--line)}.btn.danger{background:var(--danger)}
        .actions{display:flex;gap:8px;flex-wrap:wrap}.badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px}.badge.ok{background:#dcfce7;color:#166534}.badge.warn{background:#fef3c7;color:#92400e}.badge.bad{background:#fee2e2;color:#991b1b}
        .form{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:18px}.field{margin-bottom:14px}label{display:block;font-weight:700;margin-bottom:6px}input,select,textarea{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:var(--text)}textarea{min-height:92px}.error{color:var(--danger);font-size:12px;margin-top:4px}.alert{padding:12px 14px;border-radius:8px;margin-bottom:16px}.alert.success{background:#dcfce7;color:#166534}.alert.error{background:#fee2e2;color:#991b1b}
        .top-user{display:flex;align-items:center;gap:10px}.avatar{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:999px;background:#ecfdf5;color:#059669;font-weight:700;border:2px solid #e5e7eb}.user-meta strong{display:block;font-size:15px}.user-meta span{display:block;color:var(--muted);font-size:12px}.logout{background:#fff;border:1px solid var(--line);border-radius:8px;padding:8px 12px;font-weight:700}
        .code{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:14px;font-family:Consolas,monospace;overflow:auto}.pagination{margin-top:14px}.pagination nav{display:flex;gap:6px}
        .router-onboarding{padding-bottom:40px}.setup-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.setup-step{border:1px solid var(--line);border-radius:10px;padding:14px;background:#fff;display:grid;grid-template-columns:auto 1fr;gap:4px 10px;align-items:center}.setup-step span{grid-row:1/3;width:34px;height:34px;border-radius:999px;background:#fee2e2;color:#be123c;display:flex;align-items:center;justify-content:center;font-weight:800}.setup-step strong{display:block}.setup-step small{color:var(--muted)}.setup-step.active span,.setup-step.done span{background:#10b981;color:#fff}.service-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:4px 0 14px}.service-choice{display:flex;gap:10px;align-items:flex-start;border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff}.service-choice input{width:auto;margin-top:4px}.service-choice small{display:block;color:var(--muted);margin-top:3px}.command-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}.command-header h3{margin:0}.command-box{max-height:260px;overflow:auto;overflow-wrap:normal;word-break:normal;white-space:pre}.device-mode-help code{background:rgba(15,23,42,.12);padding:2px 4px;border-radius:4px}.terminal-wait{margin-top:14px;background:#18181b;color:#f8fafc;border-radius:8px;padding:16px;font-family:Consolas,monospace}.terminal-wait::first-letter{color:#10b981}.service-title{margin-top:22px}.service-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.wizard-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
        .form-section{margin:16px 0}.form-section h3{margin-top:0}.toggle-row{display:flex;gap:10px;flex-wrap:wrap;margin:8px 0 14px}.toggle-row label,.check-list label{display:inline-flex;align-items:center;gap:8px;font-weight:600;border:1px solid var(--line);border-radius:8px;background:#fff;padding:8px 10px}.toggle-row input,.check-list input{width:auto}.check-list{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}.conditional-fields{margin-top:12px}
        @media(max-width:900px){.sidebar{position:static;width:100%}.app{display:block}.main{margin-left:0}.grid-4,.grid-3,.grid-2{grid-template-columns:1fr}.topbar{position:static}}
        @media(max-width:640px){.router-onboarding{padding-bottom:96px}.router-onboarding .setup-steps{grid-template-columns:1fr}.router-onboarding .service-choice-grid,.router-onboarding .service-card-grid{grid-template-columns:1fr}.router-onboarding .command-header{align-items:stretch;flex-direction:column}.router-onboarding .command-box{max-height:220px;font-size:12px;line-height:1.45;white-space:pre;overflow:auto}.router-onboarding .wizard-actions{display:grid;grid-template-columns:1fr}.router-onboarding .wizard-actions .btn,.router-onboarding .copy-command{width:100%;justify-content:center}.router-onboarding kbd,.router-onboarding .search-shortcut{display:none!important}}
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
