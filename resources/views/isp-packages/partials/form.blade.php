<div class="grid grid-3">
<div class="field"><label>ISP</label><select name="isp_id">@foreach($isps as $isp)<option value="{{ $isp->id }}" @selected(old('isp_id', $package?->isp_id) == $isp->id)>{{ $isp->name }}</option>@endforeach</select></div>
<div class="field"><label>Name</label><input name="name" value="{{ old('name', $package?->name) }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label>Price</label><input type="number" step="0.01" name="price" value="{{ old('price', $package?->price ?? 0) }}" required></div>
<div class="field"><label>Download Mbps</label><input type="number" name="download_speed_mbps" value="{{ old('download_speed_mbps', $package?->download_speed_mbps) }}"></div>
<div class="field"><label>Upload Mbps</label><input type="number" name="upload_speed_mbps" value="{{ old('upload_speed_mbps', $package?->upload_speed_mbps) }}"></div>
<div class="field"><label>Billing Cycle</label><select name="billing_cycle">@foreach(['daily','weekly','monthly','custom'] as $cycle)<option value="{{ $cycle }}" @selected(old('billing_cycle', $package?->billing_cycle ?? 'monthly') === $cycle)>{{ ucfirst($cycle) }}</option>@endforeach</select></div>
<div class="field"><label>Validity Days</label><input type="number" name="validity_days" value="{{ old('validity_days', $package?->validity_days ?? 30) }}" required></div>
<div class="field"><label>Status</label><select name="status">@foreach(['active','inactive'] as $status)<option value="{{ $status }}" @selected(old('status', $package?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div><div class="field"><label>Notes</label><textarea name="notes">{{ old('notes', $package?->notes) }}</textarea></div>
