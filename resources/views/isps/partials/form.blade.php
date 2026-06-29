<div class="grid grid-2">
    <div class="field"><label>ISP Name</label><input name="name" value="{{ old('name', $isp?->name) }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Status</label><select name="status" required>@foreach(['active','pending','suspended'] as $status)<option value="{{ $status }}" @selected(old('status', $isp?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $isp?->email) }}"></div>
    <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $isp?->phone) }}"></div>
</div>
<div class="field"><label>Address</label><input name="address" value="{{ old('address', $isp?->address) }}"></div>
<hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
<h3>ISP Admin User</h3>
<p class="muted">Provide admin details to create or update the ISP admin. Leave password blank on edit to keep the existing password.</p>
<div class="grid grid-3">
    <div class="field"><label>Admin Name</label><input name="admin_name" value="{{ old('admin_name', $isp?->admin?->name) }}">@error('admin_name')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Admin Email</label><input type="email" name="admin_email" value="{{ old('admin_email', $isp?->admin?->email) }}">@error('admin_email')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label>Password</label><input type="password" name="admin_password">@error('admin_password')<div class="error">{{ $message }}</div>@enderror</div>
</div>
