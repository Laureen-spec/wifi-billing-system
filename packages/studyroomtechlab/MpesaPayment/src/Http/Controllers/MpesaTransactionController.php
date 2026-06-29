<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use StudyRoomTechLab\MpesaPayment\Models\MpesaTransaction;

class MpesaTransactionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $transactions = MpesaTransaction::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('phone', 'like', "%{$search}%")
                        ->orWhere('mpesa_receipt_number', 'like', "%{$search}%")
                        ->orWhere('checkout_request_id', 'like', "%{$search}%")
                        ->orWhere('merchant_request_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('mpesa-payment::transactions.index', [
            'transactions' => $transactions,
        ]);
    }

    public function show(Request $request, MpesaTransaction $transaction)
    {
        $this->authorizePlatform($request);

        return view('mpesa-payment::transactions.show', [
            'transaction' => $transaction,
        ]);
    }

    private function authorizePlatform(Request $request): void
    {
        abort_unless($request->user(), 403);

        if (class_exists(IspTenantResolver::class)) {
            if (app(IspTenantResolver::class)->isPlatform($request)) {
                return;
            }
        }

        $type = strtolower((string) ($request->user()->type ?? ''));

        abort_unless(in_array($type, [
            'super admin',
            'super_admin',
            'superadmin',
            'company',
            'owner',
        ], true), 403);
    }
}
