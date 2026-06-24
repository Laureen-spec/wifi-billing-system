<div class="grid grid-3">
<div class="field"><label>ISP</label><select name="isp_id">@foreach($isps as $isp)<option value="{{ $isp->id }}" @selected(old('isp_id', $customer?->isp_id) == $isp->id)>{{ $isp->name }}</option>@endforeach</select></div>
<div class="field"><label>Package</label><select name="internet_package_id"><option value="">No package</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected(old('internet_package_id', $customer?->internet_package_id) == $package->id)>{{ $package->name }}</option>@endforeach</select></div>
<div class="field"><label>Name</label><input name="name" value="{{ old('name', $customer?->name) }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $customer?->phone) }}"></div>
<div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $customer?->email) }}"></div>
<div class="field"><label>Location</label><input name="location" value="{{ old('location', $customer?->location) }}"></div>
<div class="field"><label>Connection Status</label><select name="connection_status">@foreach(['pending','active','suspended','disconnected'] as $status)<option value="{{ $status }}" @selected(old('connection_status', $customer?->connection_status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="field"><label>Billing Status</label><select name="billing_status">@foreach(['paid','unpaid','overdue'] as $status)<option value="{{ $status }}" @selected(old('billing_status', $customer?->billing_status ?? 'unpaid') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="field"><label>Monthly Amount</label><input type="number" step="0.01" name="monthly_amount" value="{{ old('monthly_amount', $customer?->monthly_amount ?? 0) }}" required></div>
<div class="field"><label>Installation Date</label><input type="date" name="installation_date" value="{{ old('installation_date', $customer?->installation_date?->format('Y-m-d')) }}"></div>
<div class="field"><label>Next Due Date</label><input type="date" name="next_due_date" value="{{ old('next_due_date', $customer?->next_due_date?->format('Y-m-d')) }}"></div>
</div><div class="field"><label>Address</label><input name="address" value="{{ old('address', $customer?->address) }}"></div><div class="field"><label>Notes</label><textarea name="notes">{{ old('notes', $customer?->notes) }}</textarea></div>
