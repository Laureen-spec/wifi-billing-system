@php
    $template = $template ?? [];
    $templateKey = $template['template_key'] ?? 'simple';
    $logoUrl = $template['logo_url'] ?? null;
    $backgroundUrl = $template['background_url'] ?? null;
    $primaryColor = $template['primary_color'] ?? '#0f172a';
    $buttonColor = $template['button_color'] ?? '#0f172a';
    $secondaryButtonColor = $template['secondary_button_color'] ?? '#334155';
    $successColor = $template['success_color'] ?? '#16a34a';
    $textColor = $template['text_color'] ?? '#0f172a';
    $pageBackground = $template['page_background'] ?? '#f8fafc';
    $welcomeTitle = $template['welcome_title'] ?? 'StudyRoom WiFi';
    $welcomeSubtitle = $template['welcome_subtitle'] ?? 'Choose a WiFi package and pay using M-Pesa. Your internet will activate automatically after payment confirmation.';
    $footerText = $template['footer_text'] ?? 'Powered by StudyRoom WiFi Billing';
    $customerCarePhone = $template['customer_care_phone'] ?? null;
    $redirectUrl = $template['redirect_url'] ?? 'http://10.10.50.1/login';
    $instructions = $template['purchase_instructions'] ?? [];
    $freeAccessEnabled = (bool) ($template['enable_datalan_free_access'] ?? false);
    $freeAccessDuration = (int) ($template['free_access_duration_minutes'] ?? 60);
    $freeAccessRequiresPhone = (bool) ($template['free_access_requires_phone'] ?? false);
    $freeAccessRequiresName = (bool) ($template['free_access_requires_name'] ?? false);
    $freeAccessButtonText = $template['free_access_button_text'] ?? 'Get 1 hour free access';
    $freeAccessSpeedLimit = $template['free_access_speed_limit'] ?? null;
    $freeAccessIdentityMode = $template['free_access_identity_mode'] ?? 'mac';
@endphp
<!DOCTYPE html>
<html lang="{{ $template['captive_portal_language'] ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $welcomeTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --sr-primary: {{ $primaryColor }};
            --sr-button: {{ $buttonColor }};
            --sr-secondary: {{ $secondaryButtonColor }};
            --sr-success: {{ $successColor }};
            --sr-text: {{ $textColor }};
            --sr-bg: {{ $pageBackground }};
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--sr-bg);
            color: var(--sr-text);
            min-height: 100vh;
            @if($backgroundUrl)
                background-image: linear-gradient(rgba(248,250,252,.88), rgba(248,250,252,.94)), url('{{ $backgroundUrl }}');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
            @endif
        }

        .wrap {
            max-width: 560px;
            margin: 0 auto;
            padding: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #fff;
            display: block;
        }

        .brand-mark {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: var(--sr-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            flex: 0 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, .96);
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            padding: 22px;
            margin-bottom: 14px;
            box-shadow: 0 14px 40px rgba(15, 23, 42, .08);
            backdrop-filter: blur(8px);
        }

        h1 {
            margin: 0 0 6px;
            font-size: 28px;
            color: var(--sr-text);
            line-height: 1.1;
        }

        h3 {
            color: var(--sr-text);
        }

        p {
            color: #64748b;
            line-height: 1.5;
            margin: 0;
        }

        .instructions {
            margin: 16px 0 0;
            padding: 12px;
            border: 1px dashed rgba(15, 23, 42, .18);
            border-radius: 16px;
            background: rgba(248, 250, 252, .8);
        }

        .instructions-title {
            margin: 0 0 8px;
            font-weight: 800;
            font-size: 14px;
            color: var(--sr-primary);
        }

        .instructions ol {
            margin: 0;
            padding-left: 18px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
        }

        .package-grid {
            display: grid;
            gap: 12px;
            margin-top: 14px;
        }

        .package {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 16px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .package:hover {
            border-color: rgba(15, 23, 42, .24);
            box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .package-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .package-meta {
            margin-top: 6px;
        }

        .package-cta {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgba(15, 23, 42, .06);
            color: var(--sr-text);
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 800;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(15, 23, 42, .48);
            padding: 14px;
        }

        .modal-backdrop.is-open {
            display: flex;
        }

        .modal-panel {
            width: 100%;
            max-width: 480px;
            max-height: calc(100vh - 28px);
            overflow-y: auto;
            border-radius: 24px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 20px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, .28);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .modal-close {
            width: 38px;
            height: 38px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            color: #64748b;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }

        .modal-summary {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 14px;
            margin-bottom: 14px;
            background: #f8fafc;
        }

        .free-access-card {
            border-color: rgba(22, 163, 74, .28);
            background: linear-gradient(180deg, rgba(240, 253, 244, .96), rgba(255, 255, 255, .96));
        }

        .free-access-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .free-access-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .free-access-fields {
            margin-top: 14px;
        }

        .package strong {
            color: var(--sr-text);
            font-size: 17px;
        }

        .price {
            font-size: 24px;
            font-weight: 900;
            margin-top: 8px;
            color: var(--sr-primary);
        }

        .btn {
            width: 100%;
            display: block;
            text-align: center;
            background: var(--sr-button);
            color: white;
            border: none;
            text-decoration: none;
            padding: 13px;
            border-radius: 14px;
            margin-top: 10px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .15s ease, opacity .15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--sr-secondary);
        }

        .btn-success {
            background: var(--sr-success);
        }

        .muted {
            color: #94a3b8;
            font-size: 13px;
        }

        .input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            margin-bottom: 8px;
            font-size: 15px;
            background: #fff;
            color: var(--sr-text);
        }

        .input:focus {
            outline: none;
            border-color: var(--sr-primary);
            box-shadow: 0 0 0 3px rgba(15, 23, 42, .10);
        }

        .status {
            margin-top: 8px;
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
        }

        .status-ok {
            color: #16a34a;
        }

        .status-error {
            color: #dc2626;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            margin-top: 18px;
        }

        .care {
            margin-top: 10px;
            color: #64748b;
            font-size: 13px;
        }

        .care strong {
            color: var(--sr-primary);
        }

        @media (max-width: 600px) {
            .wrap {
                padding: 14px;
            }

            .card {
                padding: 18px;
                border-radius: 18px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="hotspot-template-{{ $templateKey }}">
<div class="wrap">
    <div class="card">
        <div class="brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="brand-mark" style="display:none;">{{ strtoupper(substr($welcomeTitle, 0, 1)) }}</div>
            @else
                <div class="brand-mark">{{ strtoupper(substr($welcomeTitle, 0, 1)) }}</div>
            @endif

            <div>
                <h1>{{ $welcomeTitle }}</h1>
                <p>{{ $welcomeSubtitle }}</p>
            </div>
        </div>

        @if(!empty($instructions))
            <div class="instructions">
                <div class="instructions-title">How to connect</div>
                <ol>
                    @foreach($instructions as $instruction)
                        <li>{{ is_array($instruction) ? ($instruction['text'] ?? '') : $instruction }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if($customerCarePhone)
            <div class="care">Need help? Contact customer care: <strong>{{ $customerCarePhone }}</strong></div>
        @endif
    </div>

    @if($freeAccessEnabled)
        <div class="card free-access-card">
            <div class="free-access-heading">
                <div>
                    <h3 style="margin:0 0 6px;">Free Access</h3>
                    <p>{{ $freeAccessSpeedLimit ? 'Speed: ' . $freeAccessSpeedLimit : 'Start a limited free session before buying a package.' }}</p>
                </div>
                <span class="free-access-badge">{{ $freeAccessDuration }} min</span>
            </div>

            <div class="free-access-fields">
                @if($freeAccessRequiresName)
                    <input type="text"
                           id="free-access-name"
                           class="input"
                           placeholder="Your name">
                @endif

                @if($freeAccessRequiresPhone || $freeAccessIdentityMode !== 'mac')
                    <input type="tel"
                           id="free-access-phone"
                           class="input"
                           placeholder="Phone e.g. 0712345678">
                @endif

                <button type="button" id="free-access-start-btn" class="btn btn-success">
                    {{ $freeAccessButtonText }}
                </button>

                <div id="free-access-status" class="status"></div>
            </div>
        </div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Available Packages</h3>
        <p class="muted">Tap a plan to enter your M-Pesa number and complete payment.</p>

        <div class="package-grid">
            @forelse($packages as $package)
                @php
                    $name = $package->name ?? ('Package #' . $package->id);
                    $price = $package->price ?? 0;
                    $download = $package->download_speed_mbps ?? null;
                    $upload = $package->upload_speed_mbps ?? null;
                    $validity = $package->validity_days ?? null;
                    $speedText = trim(($download ? $download . ' Mbps down' : '') . ($upload ? ' / ' . $upload . ' Mbps up' : ''));
                    $validityText = $validity ? $validity . ' day(s)' : '';
                @endphp

                <button type="button"
                        class="package select-package-btn"
                        data-package-id="{{ $package->id }}"
                        data-package-name="{{ $name }}"
                        data-package-price="KES {{ number_format((float) $price, 2) }}"
                        data-package-speed="{{ $speedText }}"
                        data-package-validity="{{ $validityText }}">
                    <div class="package-top">
                        <div>
                            <strong>{{ $name }}</strong>

                            <div class="package-meta muted">
                                @if($speedText)
                                    <div>Speed: {{ $speedText }}</div>
                                @endif

                                @if($validityText)
                                    <div>Validity: {{ $validityText }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="price" style="margin-top:0; white-space:nowrap;">KES {{ number_format((float) $price, 2) }}</div>
                    </div>

                    <span class="package-cta">Select plan</span>
                </button>
            @empty
                <p>No active WiFi packages found.</p>
            @endforelse
        </div>
    </div>

    <div id="package-payment-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="modal-package-name">
            <div class="modal-head">
                <div>
                    <h3 id="modal-package-name" style="margin:0 0 4px;">Package</h3>
                    <p id="modal-package-meta" class="muted"></p>
                </div>
                <button type="button" class="modal-close" id="package-modal-close" aria-label="Close payment form">&times;</button>
            </div>

            <div class="modal-summary">
                <div class="muted">Amount to pay</div>
                <div id="modal-package-price" class="price">KES 0.00</div>
            </div>

            <input type="text"
                   id="modal-customer-name"
                   class="input"
                   placeholder="Your name optional">

            <input type="tel"
                   id="modal-customer-phone"
                   class="input"
                   placeholder="M-Pesa phone e.g. 0712345678">

            <input type="hidden" id="modal-validated-phone">
            <input type="hidden" id="modal-package-id">

            <button type="button"
                    class="btn btn-secondary"
                    id="modal-validate-phone-btn">
                Validate Number
            </button>

            <div id="modal-phone-validation-status" class="status"></div>

            <button type="button"
                    class="btn"
                    id="modal-pay-btn">
                Pay with M-Pesa
            </button>

            @if(config('app.debug'))
                <button type="button"
                        class="btn btn-success"
                        id="modal-simulate-pay-btn">
                    Simulate Payment Success
                </button>
            @endif

            <div id="modal-pay-status" class="status"></div>
        </div>
    </div>

    <p class="footer">{{ $footerText }}</p>
</div>

<script>
const hotspotSuccessRedirectUrl = @json($redirectUrl);
const freeAccessEnabled = @json($freeAccessEnabled);
const freeAccessStatusUrl = @json($freeAccessEnabled ? route('hotspot.free-access.status') : null);
const freeAccessStartUrl = @json($freeAccessEnabled ? route('hotspot.free-access.start') : null);
const freeAccessIdentityMode = @json($freeAccessIdentityMode);
const freeAccessRequiresPhone = @json($freeAccessRequiresPhone);
const hotspotPortalParams = new URLSearchParams(window.location.search);
const hotspotClientIdentity = {
    mac_address: hotspotPortalParams.get('mac') || hotspotPortalParams.get('mac_address') || hotspotPortalParams.get('client_mac') || hotspotPortalParams.get('calling_station_id') || '',
    ip_address: hotspotPortalParams.get('ip') || hotspotPortalParams.get('ip_address') || hotspotPortalParams.get('client_ip') || '',
    username: hotspotPortalParams.get('username') || hotspotPortalParams.get('user') || '',
};

if (freeAccessEnabled) {
    const freeAccessButton = document.getElementById('free-access-start-btn');

    if (freeAccessButton) {
        freeAccessButton.addEventListener('click', startFreeAccess);
    }

    if (freeAccessIdentityMode !== 'phone' && !freeAccessRequiresPhone) {
        checkFreeAccessStatus();
    }
}

function freeAccessPayload() {
    const nameInput = document.getElementById('free-access-name');
    const phoneInput = document.getElementById('free-access-phone');

    return {
        ...hotspotClientIdentity,
        name: nameInput ? nameInput.value.trim() : '',
        phone: phoneInput ? phoneInput.value.trim() : '',
    };
}

function freeAccessQuery(payload) {
    const params = new URLSearchParams();

    Object.keys(payload).forEach(function (key) {
        if (payload[key]) {
            params.set(key, payload[key]);
        }
    });

    return params.toString();
}

function checkFreeAccessStatus() {
    const status = document.getElementById('free-access-status');

    if (!status || !freeAccessStatusUrl) {
        return;
    }

    const query = freeAccessQuery(freeAccessPayload());

    fetch(freeAccessStatusUrl + (query ? '?' + query : ''), {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'blocked') {
            setStatus(status, data.message || 'You already used free access.', false);
        }
    })
    .catch(() => {});
}

function startFreeAccess() {
    const button = document.getElementById('free-access-start-btn');
    const status = document.getElementById('free-access-status');

    if (!button || !status || !freeAccessStartUrl) {
        return;
    }

    button.disabled = true;
    setStatus(status, 'Starting free access...', true);

    fetch(freeAccessStartUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify(freeAccessPayload())
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;

        if (data.status === 'active' && data.allowed) {
            setFreeAccessSuccess(status, data);
            return;
        }

        setStatus(status, escapeHtml(data.message || 'Free access is not available.'), false);
    })
    .catch(() => {
        button.disabled = false;
        setStatus(status, 'Free access request failed. Try again.', false);
    });
}

const packageModal = document.getElementById('package-payment-modal');
const packageModalClose = document.getElementById('package-modal-close');
const modalPackageName = document.getElementById('modal-package-name');
const modalPackageMeta = document.getElementById('modal-package-meta');
const modalPackagePrice = document.getElementById('modal-package-price');
const modalPackageId = document.getElementById('modal-package-id');
const modalCustomerName = document.getElementById('modal-customer-name');
const modalCustomerPhone = document.getElementById('modal-customer-phone');
const modalValidatedPhone = document.getElementById('modal-validated-phone');
const modalPhoneStatus = document.getElementById('modal-phone-validation-status');
const modalPayStatus = document.getElementById('modal-pay-status');
const modalValidateButton = document.getElementById('modal-validate-phone-btn');
const modalPayButton = document.getElementById('modal-pay-btn');
const modalSimulateButton = document.getElementById('modal-simulate-pay-btn');

function openPackageModal(button) {
    const speed = button.dataset.packageSpeed || '';
    const validity = button.dataset.packageValidity || '';
    const meta = [speed ? 'Speed: ' + speed : '', validity ? 'Validity: ' + validity : ''].filter(Boolean).join(' · ');

    modalPackageId.value = button.dataset.packageId || '';
    modalPackageName.textContent = button.dataset.packageName || 'Selected package';
    modalPackagePrice.textContent = button.dataset.packagePrice || 'KES 0.00';
    modalPackageMeta.textContent = meta || 'Enter your phone number to pay with M-Pesa.';
    modalCustomerName.value = '';
    modalCustomerPhone.value = '';
    modalValidatedPhone.value = '';
    modalPhoneStatus.innerHTML = '';
    modalPayStatus.innerHTML = '';
    modalValidateButton.disabled = false;
    modalPayButton.disabled = false;
    if (modalSimulateButton) {
        modalSimulateButton.disabled = false;
    }

    packageModal.classList.add('is-open');
    packageModal.setAttribute('aria-hidden', 'false');
    setTimeout(function () {
        modalCustomerPhone.focus();
    }, 80);
}

function closePackageModal() {
    packageModal.classList.remove('is-open');
    packageModal.setAttribute('aria-hidden', 'true');
}

document.querySelectorAll('.select-package-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        openPackageModal(button);
    });
});

if (packageModalClose) {
    packageModalClose.addEventListener('click', closePackageModal);
}

if (packageModal) {
    packageModal.addEventListener('click', function (event) {
        if (event.target === packageModal) {
            closePackageModal();
        }
    });
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && packageModal.classList.contains('is-open')) {
        closePackageModal();
    }
});

if (modalValidateButton) {
    modalValidateButton.addEventListener('click', function () {
        const phone = modalCustomerPhone.value.trim();

        if (!phone) {
            setStatus(modalPhoneStatus, 'Enter phone number first.', false);
            return;
        }

        modalValidateButton.disabled = true;
        modalValidatedPhone.value = '';
        setStatus(modalPhoneStatus, 'Validating phone number...', true);

        fetch("{{ route('mpesa-payment.hotspot.validate-phone') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                phone: phone
            })
        })
        .then(response => response.json())
        .then(data => {
            modalValidateButton.disabled = false;

            if (!data.valid) {
                modalValidatedPhone.value = '';
                setStatus(modalPhoneStatus, data.message || 'Invalid phone number.', false);
                return;
            }

            modalValidatedPhone.value = data.phone;
            modalCustomerPhone.value = data.phone;
            setStatus(modalPhoneStatus, 'Valid number: ' + data.display_phone, true);
        })
        .catch(() => {
            modalValidateButton.disabled = false;
            modalValidatedPhone.value = '';
            setStatus(modalPhoneStatus, 'Could not validate number.', false);
        });
    });
}

if (modalPayButton) {
    modalPayButton.addEventListener('click', function () {
        const phone = modalValidatedPhone.value || modalCustomerPhone.value.trim();
        const name = modalCustomerName.value.trim();
        const packageId = modalPackageId.value;

        if (!packageId) {
            setStatus(modalPayStatus, 'Select a package first.', false);
            return;
        }

        if (!phone) {
            setStatus(modalPayStatus, 'Validate your M-Pesa number first.', false);
            return;
        }

        modalPayButton.disabled = true;
        setStatus(modalPayStatus, 'Sending STK Push...', true);

        fetch("{{ route('mpesa-payment.hotspot.stk-push') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                internet_package_id: packageId,
                phone: phone,
                name: name
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                modalPayButton.disabled = false;
                setStatus(modalPayStatus, data.message || 'Failed to send STK Push.', false);
                return;
            }

            setStatus(modalPayStatus, 'STK Push sent. Enter your M-Pesa PIN.', true);

            if (data.transaction_id) {
                pollStatus(data.transaction_id, modalPayStatus, modalPayButton);
            }
        })
        .catch(() => {
            modalPayButton.disabled = false;
            setStatus(modalPayStatus, 'Payment request failed. Try again.', false);
        });
    });
}

if (modalSimulateButton) {
    modalSimulateButton.addEventListener('click', function () {
        const phone = modalValidatedPhone.value || modalCustomerPhone.value.trim();
        const name = modalCustomerName.value.trim();
        const packageId = modalPackageId.value;

        if (!packageId) {
            setStatus(modalPayStatus, 'Select a package first.', false);
            return;
        }

        if (!phone) {
            setStatus(modalPayStatus, 'Validate or enter your M-Pesa number first.', false);
            return;
        }

        modalSimulateButton.disabled = true;
        setStatus(modalPayStatus, 'Simulating payment success...', true);

        fetch("{{ route('mpesa-payment.hotspot.simulate-payment') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                internet_package_id: packageId,
                phone: phone,
                name: name
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                modalSimulateButton.disabled = false;
                setStatus(modalPayStatus, data.message || 'Simulation failed.', false);
                return;
            }

            setStatus(modalPayStatus, data.message + ' Receipt: ' + data.receipt, true);

            setTimeout(function () {
                window.location.href = hotspotSuccessRedirectUrl;
            }, 2000);
        })
        .catch(() => {
            modalSimulateButton.disabled = false;
            setStatus(modalPayStatus, 'Simulation request failed.', false);
        });
    });
}

function pollStatus(transactionId, status, button) {
    let attempts = 0;
    const statusUrl = "{{ url('/mpesa-payment/hotspot/transactions') }}/" + transactionId + "/status";

    const timer = setInterval(function () {
        attempts++;

        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            setStatus(status, data.message || 'Waiting for M-Pesa confirmation...', true);

            if (data.status === 'paid') {
                clearInterval(timer);
                setStatus(status, 'Payment received. Internet is being activated.', true);

                setTimeout(function () {
                    window.location.href = hotspotSuccessRedirectUrl;
                }, 2000);
            }

            if (['failed', 'cancelled', 'expired'].includes(data.status)) {
                clearInterval(timer);
                button.disabled = false;
                setStatus(status, data.message || 'Payment was not completed.', false);
            }

            if (attempts >= 20) {
                clearInterval(timer);
                button.disabled = false;
                setStatus(status, 'Still waiting for confirmation. Check M-Pesa or try again.', false);
            }
        })
        .catch(() => {});
    }, 3000);
}

function setFreeAccessSuccess(status, data) {
    let html = '<div>' + escapeHtml(data.message || 'Free access is active. Activation has been queued.') + '</div>';

    if (data.username || data.password) {
        html += '<div style="margin-top:8px;padding:10px;border-radius:12px;background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.18);">';
        html += '<strong>Login details</strong>';
        if (data.username) {
            html += '<div>Username: <strong>' + escapeHtml(data.username) + '</strong></div>';
        }
        if (data.password) {
            html += '<div>Password: <strong>' + escapeHtml(data.password) + '</strong></div>';
        }
        html += '<div class="muted" style="margin-top:6px;">Wait a few seconds for MikroTik to pull the command, then log in with these details.</div>';
        if (data.login_url) {
            html += '<a class="btn btn-success" style="margin-top:10px;" href="' + escapeAttribute(data.login_url) + '">Connect now</a>';
        }
        html += '</div>';
    }

    setStatus(status, html, true);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
}

function setStatus(element, message, ok) {
    element.classList.remove('status-ok', 'status-error');
    element.classList.add(ok ? 'status-ok' : 'status-error');
    element.innerHTML = message;
}
</script>
</body>
</html>
