@php
    $days = [
        'mon' => 'Mon',
        'tue' => 'Tue',
        'wed' => 'Wed',
        'thu' => 'Thu',
        'fri' => 'Fri',
        'sat' => 'Sat',
        'sun' => 'Sun',
    ];

    $selectedDays = old('schedule_days', $package?->schedule_days ?? []);
    $selectedRouters = old('router_ids', $package?->mikrotikRouters?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? []);
    $enableBurst = old('enable_burst', $package?->enable_burst ? '1' : '0');
    $enableSchedule = old('enable_schedule', $package?->enable_schedule ? '1' : '0');
    $availableOnAll = old('available_on_all_mikrotik', $package?->available_on_all_mikrotik === false ? '0' : '1');
    $packageType = old('package_type', $package?->package_type ?? $package?->access_type ?? 'hotspot');
@endphp

<style>
.package-form-shell{
    display:flex;
    flex-direction:column;
    gap:18px;
}
.package-form-section{
    border:1px solid #e5e7eb;
    border-radius:18px;
    background:#ffffff;
    padding:22px;
    box-shadow:0 8px 24px rgba(15, 23, 42, 0.04);
}
.package-form-section h3{
    margin:0 0 4px;
    font-size:18px;
    font-weight:800;
    color:#0f172a;
}
.package-form-section p{
    margin:0 0 16px;
    color:#64748b;
    font-size:13px;
    line-height:1.5;
}
.package-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:18px;
}
.package-grid-2{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:18px;
}
.package-grid-1{
    display:grid;
    grid-template-columns:1fr;
    gap:18px;
}
.package-field{
    display:flex;
    flex-direction:column;
    gap:8px;
}
.package-field label{
    font-size:14px;
    font-weight:700;
    color:#0f172a;
}
.package-field input,
.package-field select,
.package-field textarea{
    width:100%;
    border:1px solid #cbd5e1;
    border-radius:12px;
    padding:12px 14px;
    font-size:15px;
    color:#0f172a;
    background:#fff;
    outline:none;
    transition:all .2s ease;
}
.package-field input:focus,
.package-field select:focus,
.package-field textarea:focus{
    border-color:#10b981;
    box-shadow:0 0 0 4px rgba(16, 185, 129, 0.08);
}
.package-field textarea{
    min-height:120px;
    resize:vertical;
}
.package-help{
    color:#64748b;
    font-size:12px;
    line-height:1.45;
}
.package-type-wrap{
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:18px;
}
.package-type-note{
    border:1px dashed #cbd5e1;
    border-radius:16px;
    background:#f8fafc;
    padding:16px;
}
.package-type-note h4{
    margin:0 0 8px;
    font-size:15px;
    font-weight:800;
    color:#0f172a;
}
.package-type-note p{
    margin:0;
    font-size:13px;
    color:#64748b;
}
.toggle-group{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.toggle-option{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border:1px solid #dbe2ea;
    border-radius:999px;
    background:#fff;
    font-weight:700;
    color:#334155;
}
.check-list{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:12px;
    margin-top:10px;
}
.check-list label{
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 14px;
    border:1px solid #e2e8f0;
    border-radius:12px;
    background:#fff;
    font-weight:600;
    color:#334155;
}
.package-summary{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:12px;
    margin-top:8px;
}
.summary-card{
    border:1px solid #e5e7eb;
    border-radius:14px;
    background:#f8fafc;
    padding:14px;
}
.summary-card strong{
    display:block;
    font-size:13px;
    color:#64748b;
    margin-bottom:6px;
}
.summary-card span{
    display:block;
    font-size:15px;
    font-weight:800;
    color:#0f172a;
}
.error{
    font-size:12px;
    color:#dc2626;
    margin-top:4px;
}
.package-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:8px;
}
@media (max-width: 1100px){
    .package-grid,
    .package-grid-2,
    .package-type-wrap,
    .package-summary{
        grid-template-columns:1fr;
    }
}
</style>

<div class="package-form-shell">

    <div class="package-form-section">
        <h3>Package Type</h3>
        <p>Choose the service style for this package. We use professional names on the screen while keeping backend compatibility.</p>

        <div class="package-type-wrap">
            <div class="package-field">
                <label for="package_type">Type of package</label>
                <select name="package_type" id="package_type" required>
                    <option value="">Select package type</option>
                    <option value="hotspot" @selected($packageType === 'hotspot')>Hotspot Access</option>
                    <option value="pppoe" @selected($packageType === 'pppoe')>PPPoE Service</option>
                    <option value="data_bundle" @selected($packageType === 'data_bundle')>Flex Bundle</option>
                    <option value="free_trial" @selected($packageType === 'free_trial')>Starter Access</option>
                </select>
                <div class="package-help">This helps organize package filtering, customer assignment, and provisioning behavior.</div>
                @error('package_type') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-type-note">
                <h4 id="type_title">Package guidance</h4>
                <p id="type_description">Choose a package type to show a quick explanation here.</p>
            </div>
        </div>
    </div>

    <div class="package-form-section">
        <h3>Basic Configuration</h3>
        <p>Set the package name, price, speed, validity, and billing cycle.</p>

        <div class="package-grid">
            <div class="package-field">
                <label for="name">Package Name</label>
                <input
                    id="name"
                    name="name"
                    value="{{ old('name', $package?->name) }}"
                    required
                    placeholder="Example: Home 10Mbps Unlimited"
                >
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="price">Price</label>
                <input
                    id="price"
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    value="{{ old('price', $package?->price ?? 0) }}"
                    required
                >
                <div class="package-help">Use 0 only for Starter Access packages.</div>
                @error('price') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="billing_cycle">Billing Cycle</label>
                <select id="billing_cycle" name="billing_cycle">
                    <option value="daily" @selected(old('billing_cycle', $package?->billing_cycle ?? 'monthly') === 'daily')>Daily</option>
                    <option value="weekly" @selected(old('billing_cycle', $package?->billing_cycle ?? 'monthly') === 'weekly')>Weekly</option>
                    <option value="monthly" @selected(old('billing_cycle', $package?->billing_cycle ?? 'monthly') === 'monthly')>Monthly</option>
                    <option value="custom" @selected(old('billing_cycle', $package?->billing_cycle ?? 'monthly') === 'custom')>Custom</option>
                </select>
                @error('billing_cycle') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="download_speed_mbps">Download Mbps</label>
                <input
                    id="download_speed_mbps"
                    type="number"
                    step="0.01"
                    min="0"
                    name="download_speed_mbps"
                    value="{{ old('download_speed_mbps', $package?->download_speed_mbps) }}"
                    placeholder="Example: 10"
                >
                @error('download_speed_mbps') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="upload_speed_mbps">Upload Mbps</label>
                <input
                    id="upload_speed_mbps"
                    type="number"
                    step="0.01"
                    min="0"
                    name="upload_speed_mbps"
                    value="{{ old('upload_speed_mbps', $package?->upload_speed_mbps) }}"
                    placeholder="Example: 5"
                >
                @error('upload_speed_mbps') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="validity_days">Validity Days</label>
                <input
                    id="validity_days"
                    type="number"
                    min="1"
                    name="validity_days"
                    value="{{ old('validity_days', $package?->validity_days ?? 30) }}"
                    required
                >
                @error('validity_days') <div class="error">{{ $message }}</div> @enderror
            </div>


            <div class="package-field">
                <label for="shared_users">Device Limit / Shared Users</label>
                <input
                    id="shared_users"
                    type="number"
                    min="1"
                    max="100"
                    name="shared_users"
                    value="{{ old('shared_users', $package?->shared_users ?? 1) }}"
                    required
                >
                <div class="package-help">Example: 1 device, 2 devices, 5 devices. This becomes MikroTik <code>shared-users</code>.</div>
                @error('shared_users') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="package-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" @selected(old('status', $package?->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $package?->status) === 'inactive')>Inactive</option>
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="package-summary">
            <div class="summary-card">
                <strong>Audience</strong>
                <span id="summary_audience">General internet users</span>
            </div>
            <div class="summary-card">
                <strong>Authentication</strong>
                <span id="summary_auth">Standard access</span>
            </div>
            <div class="summary-card">
                <strong>Best Use</strong>
                <span id="summary_use">Public access</span>
            </div>
            <div class="summary-card">
                <strong>Access Style</strong>
                <span id="summary_style">Hotspot session</span>
            </div>
        </div>
    </div>

    <div class="package-form-section">
        <h3>Burst Settings</h3>
        <p>Optional MikroTik burst settings for temporary higher speed.</p>

        <div class="toggle-group">
            <label class="toggle-option">
                <input type="radio" name="enable_burst" value="1" @checked($enableBurst == '1')>
                Yes
            </label>
            <label class="toggle-option">
                <input type="radio" name="enable_burst" value="0" @checked($enableBurst != '1')>
                No
            </label>
        </div>

        <div id="burst-fields" class="package-grid" style="margin-top:16px;">
            <div class="package-field">
                <label>Burst Download Mbps</label>
                <input type="number" step="0.01" min="0" name="burst_download" value="{{ old('burst_download', $package?->burst_download) }}">
            </div>

            <div class="package-field">
                <label>Burst Upload Mbps</label>
                <input type="number" step="0.01" min="0" name="burst_upload" value="{{ old('burst_upload', $package?->burst_upload) }}">
            </div>

            <div class="package-field">
                <label>Burst Threshold</label>
                <input name="burst_threshold" value="{{ old('burst_threshold', $package?->burst_threshold) }}" placeholder="Example: 20M/20M">
            </div>

            <div class="package-field">
                <label>Burst Time</label>
                <input name="burst_time" value="{{ old('burst_time', $package?->burst_time) }}" placeholder="Example: 30s/30s">
            </div>

            <div class="package-field">
                <label>Priority</label>
                <input type="number" min="1" max="8" name="priority" value="{{ old('priority', $package?->priority) }}">
            </div>

            <div class="package-field">
                <label>Limit-at</label>
                <input name="limit_at" value="{{ old('limit_at', $package?->limit_at) }}" placeholder="Example: 5M/5M">
            </div>
        </div>
    </div>

    <div class="package-form-section">
        <h3>Schedule Settings</h3>
        <p>Use this when the package should only work during selected hours or days.</p>

        <div class="toggle-group">
            <label class="toggle-option">
                <input type="radio" name="enable_schedule" value="1" @checked($enableSchedule == '1')>
                Yes
            </label>
            <label class="toggle-option">
                <input type="radio" name="enable_schedule" value="0" @checked($enableSchedule != '1')>
                No
            </label>
        </div>

        <div id="schedule-fields" style="margin-top:16px;">
            <div class="package-grid">
                <div class="package-field">
                    <label>Start Time</label>
                    <input type="time" name="schedule_start" value="{{ old('schedule_start', $package?->schedule_start) }}">
                </div>

                <div class="package-field">
                    <label>End Time</label>
                    <input type="time" name="schedule_end" value="{{ old('schedule_end', $package?->schedule_end) }}">
                </div>

                <div class="package-field">
                    <label>Recurring</label>
                    <select name="schedule_recurring">
                        <option value="1" @selected(old('schedule_recurring', $package?->schedule_recurring ?? true))>Yes</option>
                        <option value="0" @selected((string) old('schedule_recurring', $package?->schedule_recurring) === '0')>No</option>
                    </select>
                </div>
            </div>

            <div class="check-list">
                @foreach($days as $value => $label)
                    <label>
                        <input type="checkbox" name="schedule_days[]" value="{{ $value }}" @checked(in_array($value, $selectedDays, true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="package-form-section">
        <h3>MikroTik Availability</h3>
        <p>Select where this package should be available.</p>

        <div class="package-grid-2">
            <div>
                <label style="display:block; margin-bottom:10px; font-weight:700; color:#0f172a;">Available on all MikroTik routers?</label>
                <div class="toggle-group">
                    <label class="toggle-option">
                        <input type="radio" name="available_on_all_mikrotik" value="1" @checked($availableOnAll == '1')>
                        Yes
                    </label>
                    <label class="toggle-option">
                        <input type="radio" name="available_on_all_mikrotik" value="0" @checked($availableOnAll == '0')>
                        No
                    </label>
                </div>
            </div>

            <div>
                <label style="display:block; margin-bottom:10px; font-weight:700; color:#0f172a;">Hide this package from client self-service</label>
                <div class="toggle-group">
                    <label class="toggle-option">
                        <input type="radio" name="hidden_from_client" value="1" @checked(old('hidden_from_client', $package?->hidden_from_client ? '1' : '0') == '1')>
                        Yes
                    </label>
                    <label class="toggle-option">
                        <input type="radio" name="hidden_from_client" value="0" @checked(old('hidden_from_client', $package?->hidden_from_client ? '1' : '0') != '1')>
                        No
                    </label>
                </div>
            </div>
        </div>

        <div id="router-fields" style="margin-top:16px;">
            <label style="display:block; margin-bottom:10px; font-weight:700; color:#0f172a;">Specific MikroTik Routers</label>

            <div class="check-list">
                @forelse($routers as $router)
                    <label>
                        <input type="checkbox" name="router_ids[]" value="{{ $router->id }}" @checked(in_array((string) $router->id, $selectedRouters, true))>
                        {{ $router->name }}
                    </label>
                @empty
                    <span class="package-help">No MikroTik routers available yet.</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="package-form-section">
        <h3>Notes</h3>
        <p>Add optional internal notes for your team.</p>

        <div class="package-grid-1">
            <div class="package-field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Optional internal notes">{{ old('notes', $package?->notes) }}</textarea>
            </div>
        </div>

        
    </div>
</div>

<script>
function refreshPackageSections() {
    const burst = document.querySelector('input[name="enable_burst"]:checked')?.value === '1';
    const schedule = document.querySelector('input[name="enable_schedule"]:checked')?.value === '1';
    const allRouters = document.querySelector('input[name="available_on_all_mikrotik"]:checked')?.value === '1';

    const burstFields = document.getElementById('burst-fields');
    const scheduleFields = document.getElementById('schedule-fields');
    const routerFields = document.getElementById('router-fields');

    if (burstFields) burstFields.style.display = burst ? 'grid' : 'none';
    if (scheduleFields) scheduleFields.style.display = schedule ? 'block' : 'none';
    if (routerFields) routerFields.style.display = allRouters ? 'none' : 'block';
}

function refreshPackageTypeInfo() {
    const type = document.getElementById('package_type')?.value || '';

    const typeTitle = document.getElementById('type_title');
    const typeDescription = document.getElementById('type_description');
    const summaryAudience = document.getElementById('summary_audience');
    const summaryAuth = document.getElementById('summary_auth');
    const summaryUse = document.getElementById('summary_use');
    const summaryStyle = document.getElementById('summary_style');

    const map = {
        hotspot: {
            title: 'Hotspot Access',
            description: 'Best for hostels, cafés, event WiFi, or places where users connect through a hotspot login page.',
            audience: 'Walk-in and public users',
            auth: 'Captive portal login',
            use: 'Short sessions or timed access',
            style: 'Voucher or hotspot session'
        },
        pppoe: {
            title: 'PPPoE Service',
            description: 'Ideal for home and office customers who connect using a dedicated username and password.',
            audience: 'Home and business clients',
            auth: 'PPPoE username/password',
            use: 'Fixed monthly subscriptions',
            style: 'Always-on broadband profile'
        },
        data_bundle: {
            title: 'Flex Bundle',
            description: 'Good for measured access packages, promotional bundles, or controlled usage plans.',
            audience: 'Light or flexible users',
            auth: 'Metered or limited package',
            use: 'Volume-based offers',
            style: 'Usage-controlled access'
        },
        free_trial: {
            title: 'Starter Access',
            description: 'Useful for onboarding, demo access, or giving new customers a quick first experience.',
            audience: 'New customers',
            auth: 'Introductory access',
            use: 'Promotions and trials',
            style: 'Trial-based access'
        }
    };

    const current = map[type] || {
        title: 'Package guidance',
        description: 'Choose a package type to show a quick explanation here.',
        audience: 'General internet users',
        auth: 'Standard access',
        use: 'Flexible setup',
        style: 'Service package'
    };

    if (typeTitle) typeTitle.textContent = current.title;
    if (typeDescription) typeDescription.textContent = current.description;
    if (summaryAudience) summaryAudience.textContent = current.audience;
    if (summaryAuth) summaryAuth.textContent = current.auth;
    if (summaryUse) summaryUse.textContent = current.use;
    if (summaryStyle) summaryStyle.textContent = current.style;
}

document.addEventListener('DOMContentLoaded', function () {
    refreshPackageSections();
    refreshPackageTypeInfo();

    document.querySelectorAll('input[name="enable_burst"], input[name="enable_schedule"], input[name="available_on_all_mikrotik"]').forEach((input) => {
        input.addEventListener('change', refreshPackageSections);
    });

    const packageTypeSelect = document.getElementById('package_type');
    if (packageTypeSelect) {
        packageTypeSelect.addEventListener('change', refreshPackageTypeInfo);
    }
});
</script>