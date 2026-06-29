@php
    $mpesaCustomerId = $customer->id ?? null;
    $mpesaPackageId = $package->id ?? $internetPackage->id ?? null;
    $mpesaAmount = $amount ?? $package->price ?? $internetPackage->price ?? null;
    $mpesaPhone = $customer->phone ?? $customer->phone_number ?? '';
@endphp

@if($mpesaCustomerId && $mpesaPackageId && $mpesaAmount)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-top:16px;">
        <h4 style="margin:0 0 8px;">Pay with M-Pesa</h4>

        <p style="margin:0 0 12px;color:#64748b;">
            Send STK Push to customer phone and activate internet after successful payment.
        </p>

        <input type="hidden" id="mpesa_customer_id" value="{{ $mpesaCustomerId }}">
        <input type="hidden" id="mpesa_package_id" value="{{ $mpesaPackageId }}">
        <input type="hidden" id="mpesa_amount" value="{{ $mpesaAmount }}">

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text"
                   id="mpesa_phone"
                   value="{{ $mpesaPhone }}"
                   placeholder="2547XXXXXXXX"
                   style="padding:10px;border:1px solid #d1d5db;border-radius:10px;min-width:220px;">

            <button type="button" id="btnMpesaPay" class="btn btn-primary">
                Pay KES {{ number_format((float) $mpesaAmount, 2) }}
            </button>
        </div>

        <div id="mpesaPaymentStatus" style="margin-top:12px;color:#64748b;"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('btnMpesaPay');
            const statusBox = document.getElementById('mpesaPaymentStatus');

            if (!button || !statusBox) return;

            button.addEventListener('click', function () {
                button.disabled = true;
                statusBox.innerHTML = 'Sending M-Pesa STK Push...';

                fetch("{{ route('mpesa-payment.stk-push') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        customer_id: document.getElementById('mpesa_customer_id').value,
                        internet_package_id: document.getElementById('mpesa_package_id').value,
                        amount: document.getElementById('mpesa_amount').value,
                        phone: document.getElementById('mpesa_phone').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        button.disabled = false;
                        statusBox.innerHTML = data.message || 'Failed to send STK Push.';
                        return;
                    }

                    statusBox.innerHTML = 'STK Push sent. Waiting for payment confirmation...';

                    if (data.transaction_id) {
                        pollMpesaStatus(data.transaction_id);
                    } else {
                        button.disabled = false;
                        statusBox.innerHTML = 'STK Push sent, but transaction ID was not returned.';
                    }
                })
                .catch(() => {
                    button.disabled = false;
                    statusBox.innerHTML = 'M-Pesa request failed. Check server logs.';
                });
            });

            function pollMpesaStatus(transactionId) {
                let attempts = 0;

                const timer = setInterval(function () {
                    attempts++;

                    fetch("{{ url('/mpesa-payment/transactions') }}/" + transactionId + "/status", {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        statusBox.innerHTML = data.message || ('Payment status: ' + data.status);

                        if (data.status === 'paid') {
                            clearInterval(timer);
                            statusBox.innerHTML = 'Payment received. Internet activation started.';
                            setTimeout(function () {
                                window.location.reload();
                            }, 1500);
                        }

                        if (['failed', 'cancelled', 'expired'].includes(data.status)) {
                            clearInterval(timer);
                            button.disabled = false;
                            statusBox.innerHTML = data.message || 'Payment was not completed.';
                        }

                        if (attempts >= 20) {
                            clearInterval(timer);
                            button.disabled = false;
                            statusBox.innerHTML = 'Still waiting for callback. Check transactions page.';
                        }
                    })
                    .catch(() => {
                        if (attempts >= 20) {
                            clearInterval(timer);
                            button.disabled = false;
                            statusBox.innerHTML = 'Could not check payment status.';
                        }
                    });
                }, 3000);
            }
        });
    </script>
@endif
