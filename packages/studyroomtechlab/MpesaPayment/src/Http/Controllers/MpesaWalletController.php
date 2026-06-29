<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use StudyRoomTechLab\MpesaPayment\Models\IspWallet;

class MpesaWalletController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $wallets = IspWallet::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('isp_id', 'like', "%{$search}%")
                        ->orWhere('payout_phone', 'like', "%{$search}%")
                        ->orWhere('payout_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'available_balance' => IspWallet::sum('available_balance'),
            'pending_balance' => IspWallet::sum('pending_balance'),
            'total_earned' => IspWallet::sum('total_earned'),
            'total_paid_out' => IspWallet::sum('total_paid_out'),
            'wallet_count' => IspWallet::count(),
        ];

        return view('mpesa-payment::wallets.index', [
            'wallets' => $wallets,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, IspWallet $wallet)
    {
        $this->authorizePlatform($request);

        $ledger = $wallet->ledger()
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $settlements = $wallet->settlements()
            ->latest()
            ->limit(10)
            ->get();

        return view('mpesa-payment::wallets.show', [
            'wallet' => $wallet,
            'ledger' => $ledger,
            'settlements' => $settlements,
        ]);
    }

    public function updatePayout(Request $request, IspWallet $wallet)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'payout_name' => ['nullable', 'string', 'max:191'],
            'payout_phone' => ['nullable', 'string', 'max:30'],
            'payout_shortcode' => ['nullable', 'string', 'max:30'],
            'payout_method' => ['required', 'in:mpesa,bank,manual'],
            'minimum_settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'settlement_schedule' => ['required', 'in:manual,daily,weekly,monthly'],
            'auto_settlement_enabled' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $wallet->forceFill([
            'payout_name' => $data['payout_name'] ?? null,
            'payout_phone' => $data['payout_phone'] ?? null,
            'payout_shortcode' => $data['payout_shortcode'] ?? null,
            'payout_method' => $data['payout_method'],
            'minimum_settlement_amount' => $data['minimum_settlement_amount'] ?? $wallet->minimum_settlement_amount,
            'settlement_schedule' => $data['settlement_schedule'],
            'auto_settlement_enabled' => $request->boolean('auto_settlement_enabled'),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('success', 'Wallet payout settings updated.');
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
