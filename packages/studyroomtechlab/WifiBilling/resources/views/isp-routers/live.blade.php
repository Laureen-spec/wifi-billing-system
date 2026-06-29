@extends('layouts.app')
@section('page-title','Router Live')
@section('content')
<div style="max-width:1100px;margin:auto"><h1>Live Users & Ports - {{ $router->name }}</h1><div class="row"><div class="col-md-3"><div class="card p-3"><small>Online</small><h3>{{ $snapshot['online'] ? 'Yes' : 'No' }}</h3></div></div><div class="col-md-3"><div class="card p-3"><small>Hotspot active</small><h3>{{ $snapshot['hotspot_active'] ?? '-' }}</h3></div></div><div class="col-md-3"><div class="card p-3"><small>PPPoE active</small><h3>{{ $snapshot['pppoe_active'] ?? '-' }}</h3></div></div><div class="col-md-3"><div class="card p-3"><small>CPU</small><h3>{{ $snapshot['cpu'] ?? '-' }}%</h3></div></div></div><div class="card p-4 mt-3"><h4>Manual MikroTik commands</h4><pre>/ip hotspot active print
/ppp active print
/interface ethernet monitor ether1 once
/interface monitor-traffic ether4 once
/interface bridge host print where on-interface=ether4
/tool ip-scan address-range=10.10.50.1-10.10.50.254 interface=ether4</pre></div></div>
@endsection
