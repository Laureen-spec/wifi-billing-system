@php
    $cleanNotes = old('notes', $router?->notes);
    $selectedIsp = old('isp_id', $router?->isp_id ?? ($isps->first()?->id ?? null));
@endphp

<style>
.router-modern-form{
    display:grid;
    grid-template-columns:1fr;
    gap:18px;
}
.router-modern-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:18px;
}
.router-modern-field{
    display:flex;
    flex-direction:column;
    gap:8px;
}
.router-modern-field label{
    font-size:14px;
    font-weight:800;
    color:#0f172a;
}
.router-modern-field input,
.router-modern-field select,
.router-modern-field textarea{
    width:100%;
    border:1px solid #cbd5e1;
    border-radius:12px;
    padding:12px 14px;
    font-size:15px;
    color:#0f172a;
    background:#fff;
    outline:none;
}
.router-modern-field input:focus,
.router-modern-field select:focus,
.router-modern-field textarea:focus{
    border-color:#10b981;
    box-shadow:0 0 0 4px rgba(16,185,129,.08);
}
.router-modern-field textarea{
    min-height:120px;
    resize:vertical;
}
.router-help{
    color:#64748b;
    font-size:12px;
    line-height:1.5;
}
.router-info-box{
    border:1px solid #bfdbfe;
    background:#eff6ff;
    color:#1e40af;
    border-radius:14px;
    padding:14px 16px;
    font-size:14px;
    line-height:1.5;
}
.error{
    color:#dc2626;
    font-size:12px;
}
@media(max-width:900px){
    .router-modern-grid{
        grid-template-columns:1fr;
    }
}
</style>

<div class="router-modern-form">
    @if($isps->count() > 1)
        <div class="router-modern-field">
            <label>Company / ISP</label>
            <select name="isp_id">
                @foreach($isps as $isp)
                    <option value="{{ $isp->id }}" @selected((string) $selectedIsp === (string) $isp->id)>
                        {{ $isp->name }}
                    </option>
                @endforeach
            </select>
            @error('isp_id')<div class="error">{{ $message }}</div>@enderror
        </div>
    @else
        <input type="hidden" name="isp_id" value="{{ $selectedIsp }}">
    @endif

    <div class="router-modern-grid">
        <div class="router-modern-field">
            <label>MikroTik Name</label>
            <input
                name="name"
                value="{{ old('name', $router?->name) }}"
                required
                placeholder="Example: Main Office Router"
            >
            <div class="router-help">Use a name your team can recognize. The link command will set this as the router identity.</div>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="router-modern-field">
            <label>Link Status</label>
            <select name="status">
                <option value="inactive" @selected(old('status', $router?->status ?? 'inactive') === 'inactive')>
                    Waiting for link
                </option>
                <option value="active" @selected(old('status', $router?->status ?? 'inactive') === 'active')>
                    Active
                </option>
                <option value="failed" @selected(old('status', $router?->status ?? 'inactive') === 'failed')>
                    Failed
                </option>
            </select>
            <div class="router-help">New MikroTik devices should remain “Waiting for link” until the command is imported.</div>
            @error('status')<div class="error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="router-modern-field">
        <label>Notes</label>
        <textarea name="notes" placeholder="Optional internal notes, location, pole, estate, or technician name">{{ $cleanNotes }}</textarea>
        @error('notes')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div class="router-info-box">
        No host, API port, username, password, PPPoE, or Hotspot selection is needed here.
        After saving, StudyRoom Connect generates one MikroTik command. Paste it in MikroTik Terminal to link the router.
    </div>
</div>