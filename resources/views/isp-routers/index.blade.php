@extends('layouts.app')
@section('title', 'MikroTik Routers')
@section('page_heading', 'MikroTik Routers')
@section('content')
<div class="page-title"><div><h1>MikroTik Routers</h1><p>Manage router credentials and setup scripts.</p></div><a class="btn" href="{{ route('isp.routers.create') }}">Add Router</a></div>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>ISP</th><th>Host</th><th>Status</th><th>Provisioning</th><th>Actions</th></tr></thead><tbody>
@forelse($routers as $router)
<tr>
<td><strong>{{ $router->name }}</strong><div class="muted">{{ $router->connection_type }}</div></td><td>{{ $router->isp?->name }}</td><td>{{ $router->host }}:{{ $router->api_port }}</td>
<td><span class="badge {{ $router->status === 'active' ? 'ok' : ($router->status === 'failed' ? 'bad' : '') }}">{{ ucfirst($router->status) }}</span></td>
<td>{{ ucfirst($router->provision_status) }}<div class="muted">{{ $router->provisioned_at?->format('Y-m-d H:i') }}</div></td>
<td><div class="actions"><a class="btn secondary" href="{{ route('isp.routers.setup-script', $router) }}">Setup Script</a><a class="btn secondary" href="{{ route('isp.routers.edit', $router) }}">Edit</a><form method="POST" action="{{ route('isp.routers.test', $router) }}">@csrf<button class="btn secondary">Test Connection</button></form></div></td>
</tr>
@empty
<tr><td colspan="6" class="muted">No routers found.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $routers->links() }}</div>
@endsection
