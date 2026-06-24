@extends('layouts.app')
@section('title', 'ISPs')
@section('page_heading', 'ISP Management')
@section('content')
<div class="page-title"><div><h1>ISPs</h1><p>Create and manage ISP tenants.</p></div><a class="btn" href="{{ route('isps.create') }}">Add ISP</a></div>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Contact</th><th>Admin</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($isps as $isp)
<tr>
    <td><strong>{{ $isp->name }}</strong><div class="muted">{{ $isp->address }}</div></td>
    <td>{{ $isp->email ?: '-' }}<div class="muted">{{ $isp->phone }}</div></td>
    <td>{{ $isp->admin?->name ?: '-' }}<div class="muted">{{ $isp->admin?->email }}</div></td>
    <td><span class="badge {{ $isp->status === 'active' ? 'ok' : ($isp->status === 'pending' ? 'warn' : 'bad') }}">{{ ucfirst($isp->status) }}</span></td>
    <td><a class="btn secondary" href="{{ route('isps.edit', $isp) }}">Edit</a></td>
</tr>
@empty
<tr><td colspan="5" class="muted">No ISPs found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $isps->links() }}</div>
@endsection
