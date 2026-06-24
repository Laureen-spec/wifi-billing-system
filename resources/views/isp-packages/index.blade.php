@extends('layouts.app')
@section('title', 'Internet Packages')
@section('page_heading', 'Internet Packages')
@section('content')
<div class="page-title"><div><h1>Internet Packages</h1><p>Manage ISP plans and billing cycles.</p></div><a class="btn" href="{{ route('isp.packages.create') }}">Add Package</a></div>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>ISP</th><th>Speed</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($packages as $package)
<tr><td><strong>{{ $package->name }}</strong><div class="muted">{{ $package->billing_cycle }} / {{ $package->validity_days }} days</div></td><td>{{ $package->isp?->name }}</td><td>{{ $package->download_speed_mbps ?: '-' }} / {{ $package->upload_speed_mbps ?: '-' }} Mbps</td><td>{{ number_format($package->price, 2) }}</td><td><span class="badge {{ $package->status === 'active' ? 'ok' : 'bad' }}">{{ ucfirst($package->status) }}</span></td><td><a class="btn secondary" href="{{ route('isp.packages.edit', $package) }}">Edit</a></td></tr>
@empty
<tr><td colspan="6" class="muted">No packages found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $packages->links() }}</div>
@endsection
