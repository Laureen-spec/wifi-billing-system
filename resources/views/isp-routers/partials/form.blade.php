<div class="grid grid-3">
<div class="field"><label>ISP</label><select name="isp_id">@foreach($isps as $isp)<option value="{{ $isp->id }}" @selected(old('isp_id', $router?->isp_id) == $isp->id)>{{ $isp->name }}</option>@endforeach</select></div>
<div class="field"><label>Name</label><input name="name" value="{{ old('name', $router?->name) }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label>Host</label><input name="host" value="{{ old('host', $router?->host) }}" required></div>
<div class="field"><label>API Port</label><input type="number" name="api_port" value="{{ old('api_port', $router?->api_port ?? 8728) }}" required></div>
<div class="field"><label>Username</label><input name="username" value="{{ old('username', $router?->username ?? 'billing-api') }}" required></div>
<div class="field"><label>Password {{ $router ? '(leave blank to keep)' : '' }}</label><input type="password" name="password" {{ $router ? '' : 'required' }}>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label>Connection Type</label><select name="connection_type">@foreach(['api','api_ssl'] as $type)<option value="{{ $type }}" @selected(old('connection_type', $router?->connection_type ?? 'api') === $type)>{{ strtoupper($type) }}</option>@endforeach</select></div>
<div class="field"><label>Status</label><select name="status">@foreach(['active','inactive','failed'] as $status)<option value="{{ $status }}" @selected(old('status', $router?->status ?? 'inactive') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div><div class="field"><label>Notes</label><textarea name="notes">{{ old('notes', $router?->notes) }}</textarea></div>
