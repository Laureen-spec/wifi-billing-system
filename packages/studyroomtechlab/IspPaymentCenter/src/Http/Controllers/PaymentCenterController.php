<?php

namespace StudyRoomTechLab\IspPaymentCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use StudyRoomTechLab\IspPaymentCenter\Services\PaymentCenterService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentCenterController extends Controller
{
    public function __construct(
        private readonly PaymentCenterService $payments
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorizeAccess($request);

        return Inertia::render('isp-payment-center/index', [
            'pageTitle' => 'Payment Center',
            'subtitle' => 'All customer payments, confirmations, and settlement records in one clean workspace.',
            ...$this->payments->dashboard($request),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAccess($request);

        $rows = $this->payments->exportRows($request);
        $filename = 'payment-center-' . now()->format('Ymd-His') . '.csv';

        return Response::streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Customer',
                'Phone / Account',
                'Receipt',
                'Method',
                'Source',
                'Package',
                'Amount',
                'Currency',
                'Status',
                'Wallet',
                'Provisioning',
                'Date',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['customer'],
                    trim(($row['phone'] ?? '') . ' ' . ($row['account'] ?? '')),
                    $row['receipt'],
                    $row['method'],
                    $row['source'],
                    $row['package'],
                    $row['amount'],
                    $row['currency'],
                    $row['status'],
                    $row['wallet'],
                    $row['provisioning'],
                    $row['date'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function storeManualPayment(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'account' => ['nullable', 'string', 'max:100'],
            'receipt' => ['nullable', 'string', 'max:120'],
            'method' => ['required', 'string', 'in:Cash,Bank,Voucher'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string', 'in:confirmed,pending,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->payments->storeManualPayment($request, $validated);

        return redirect()
            ->route('isp-payment-center.index')
            ->with('success', 'Manual payment recorded.');
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        $type = (string) ($user->type ?? '');
        if (in_array($type, ['superadmin', 'super_admin', 'admin', 'company', 'isp_admin'], true)) {
            return;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['superadmin', 'super_admin', 'admin', 'company', 'isp_admin'])) {
            return;
        }

        abort_unless(
            $user->can('view-wifi-dashboard')
            || $user->can('manage-wifi-dashboard')
            || $user->can('view-isp-customers')
            || $user->can('manage-isp-customers'),
            403
        );
    }
}
