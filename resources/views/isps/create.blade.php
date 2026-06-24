@extends('layouts.app')
@section('title', 'Create ISP')
@section('page_heading', 'Create ISP')
@section('content')
<div class="page-title"><div><h1>Create ISP</h1><p>Add an ISP and optionally create its admin user.</p></div></div>
<form class="form" method="POST" action="{{ route('isps.store') }}">@csrf
    @include('isps.partials.form', ['isp' => null])
    <button class="btn" type="submit">Save ISP</button>
    <a class="btn secondary" href="{{ route('isps.index') }}">Cancel</a>
</form>
@endsection
