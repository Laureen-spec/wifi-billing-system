@extends('layouts.app')
@section('title', 'Edit ISP')
@section('page_heading', 'Edit ISP')
@section('content')
<div class="page-title"><div><h1>Edit ISP</h1><p>Update ISP details and admin assignment.</p></div></div>
<form class="form" method="POST" action="{{ route('isps.update', $isp) }}">@csrf @method('PATCH')
    @include('isps.partials.form', ['isp' => $isp])
    <button class="btn" type="submit">Update ISP</button>
    <a class="btn secondary" href="{{ route('isps.index') }}">Cancel</a>
</form>
@endsection
