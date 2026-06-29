<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use StudyRoomTechLab\MpesaPayment\Models\IspSettlement;
use StudyRoomTechLab\MpesaPayment\Models\IspWallet;
use StudyRoomTechLab\MpesaPayment\Models\MpesaTransaction;

class MpesaDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $today = now()->startOfDay();

        $stats = [
            'today_collections' => MpesaTransaction::where('status', 'paid')
                ->where('paid_at', '>=', $today)
                ->sum('amount'),

            'monthly_collections' => MpesaTransaction::where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),

            'total_collections' => MpesaTransaction::where('status', 'paid')
                ->sum('amount'),

            'pending_transactions' => MpesaTransaction::whereIn('status', ['pending', 'stk_sent'])
                ->count(),

            'failed_transactions' => MpesaTransaction::whereIn('status', ['failed', 'cancelled', 'expired'])
                ->count(),

            'platform_fees' => MpesaTransaction::where('status', 'paid')
                ->sum('platform_fee'),

            'isp_wallet_balance' => IspWallet::sum('available_balance'),

            'pending_settlements' => IspSettlement::whereIn('status', ['pending', 'approved', 'processing'])
                ->sum('net_amount'),
        ];

        return view('mpesa-payment::dashboard.index', [
            'stats' => $stats,
            'recentTransactions' => MpesaTransaction::latest()->limit(10)->get(),
            'wallets' => IspWallet::latest()->limit(10)->get(),
            'settlements' => IspSettlement::latest()->limit(10)->get(),
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