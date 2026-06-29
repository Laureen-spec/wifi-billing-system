<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use StudyRoomTechLab\MpesaPayment\Models\IspSettlement;
use StudyRoomTechLab\MpesaPayment\Models\IspWallet;
use StudyRoomTechLab\MpesaPayment\Models\IspWalletLedger;

class MpesaSettlementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $settlements = IspSettlement::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('settlement_number', 'like', "%{$search}%")
                        ->orWhere('payout_phone', 'like', "%{$search}%")
                        ->orWhere('payout_name', 'like', "%{$search}%")
                        ->orWhere('mpesa_receipt_number', 'like', "%{$search}%")
                        ->orWhere('transaction_reference', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => IspSettlement::where('status', 'pending')->sum('net_amount'),
            'approved' => IspSettlement::where('status', 'approved')->sum('net_amount'),
            'processing' => IspSettlement::where('status', 'processing')->sum('net_amount'),
            'paid' => IspSettlement::where('status', 'paid')->sum('net_amount'),
            'failed_count' => IspSettlement::where('status', 'failed')->count(),
        ];

        return view('mpesa-payment::settlements.index', [
            'settlements' => $settlements,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, IspSettlement $settlement)
    {
        $this->authorizePlatform($request);

        return view('mpesa-payment::settlements.show', [
            'settlement' => $settlement->load('wallet'),
        ]);
    }

    public function store(Request $request, IspWallet $wallet)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $fee = round((float) ($data['fee'] ?? 0), 2);
        $netAmount = max(0, $amount - $fee);

        if ($amount > (float) $wallet->available_balance) {
            return back()->withErrors([
                'amount' => 'Settlement amount cannot be more than the available wallet balance.',
            ])->withInput();
        }

        $settlement = DB::transaction(function () use ($request, $wallet, $amount, $fee, $netAmount, $data) {
            $wallet->refresh();

            $before = (float) $wallet->available_balance;
            $after = max(0, $before - $amount);

            $wallet->forceFill([
                'available_balance' => $after,
                'pending_balance' => (float) $wallet->pending_balance + $amount,
            ])->save();

            $settlement = IspSettlement::create([
                'isp_id' => $wallet->isp_id,
                'isp_wallet_id' => $wallet->id,
                'settlement_number' => $this->makeSettlementNumber(),
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'currency' => $wallet->currency ?: 'KES',
                'payout_method' => $wallet->payout_method ?: 'mpesa',
                'payout_name' => $wallet->payout_name,
                'payout_phone' => $wallet->payout_phone,
                'payout_shortcode' => $wallet->payout_shortcode,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'requested_by' => $request->user()?->id,
                'requested_at' => now(),
                'metadata' => [
                    'created_from' => 'wallet_show_page',
                    'reserved_balance_before' => $before,
                    'reserved_balance_after' => $after,
                ],
            ]);

            IspWalletLedger::create([
                'isp_id' => $wallet->isp_id,
                'isp_wallet_id' => $wallet->id,
                'isp_settlement_id' => $settlement->id,
                'type' => 'settlement',
                'source' => 'settlement_request',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $wallet->currency ?: 'KES',
                'reference' => $settlement->settlement_number,
                'description' => 'Settlement request reserved from ISP wallet.',
                'created_by' => $request->user()?->id,
            ]);

            return $settlement;
        });

        return redirect()
            ->route('mpesa-payment.settlements.show', $settlement)
            ->with('success', 'Settlement request created.');
    }

    public function approve(Request $request, IspSettlement $settlement)
    {
        $this->authorizePlatform($request);

        if (! in_array($settlement->status, ['pending'], true)) {
            return back()->withErrors(['status' => 'Only pending settlements can be approved.']);
        }

        $settlement->forceFill([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->save();

        return back()->with('success', 'Settlement approved.');
    }

    public function markPaid(Request $request, IspSettlement $settlement)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'mpesa_receipt_number' => ['nullable', 'string', 'max:191'],
            'transaction_reference' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($settlement->status, ['pending', 'approved', 'processing'], true)) {
            return back()->withErrors(['status' => 'This settlement cannot be marked as paid.']);
        }

        DB::transaction(function () use ($request, $settlement, $data) {
            $settlement->refresh();
            $wallet = $settlement->wallet;

            if ($wallet) {
                $wallet->forceFill([
                    'pending_balance' => max(0, (float) $wallet->pending_balance - (float) $settlement->amount),
                    'total_paid_out' => (float) $wallet->total_paid_out + (float) $settlement->amount,
                ])->save();
            }

            $settlement->forceFill([
                'status' => 'paid',
                'mpesa_receipt_number' => $data['mpesa_receipt_number'] ?? $settlement->mpesa_receipt_number,
                'transaction_reference' => $data['transaction_reference'] ?? $settlement->transaction_reference,
                'notes' => $data['notes'] ?? $settlement->notes,
                'processed_by' => $request->user()?->id,
                'processed_at' => now(),
                'paid_at' => now(),
            ])->save();
        });

        return back()->with('success', 'Settlement marked as paid.');
    }

    public function markFailed(Request $request, IspSettlement $settlement)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'failure_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! in_array($settlement->status, ['pending', 'approved', 'processing'], true)) {
            return back()->withErrors(['status' => 'This settlement cannot be marked as failed.']);
        }

        DB::transaction(function () use ($request, $settlement, $data) {
            $settlement->refresh();
            $wallet = $settlement->wallet;

            if ($wallet) {
                $before = (float) $wallet->available_balance;
                $after = $before + (float) $settlement->amount;

                $wallet->forceFill([
                    'available_balance' => $after,
                    'pending_balance' => max(0, (float) $wallet->pending_balance - (float) $settlement->amount),
                ])->save();

                IspWalletLedger::create([
                    'isp_id' => $settlement->isp_id,
                    'isp_wallet_id' => $wallet->id,
                    'isp_settlement_id' => $settlement->id,
                    'type' => 'reversal',
                    'source' => 'settlement_failed',
                    'amount' => (float) $settlement->amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'currency' => $wallet->currency ?: 'KES',
                    'reference' => $settlement->settlement_number,
                    'description' => 'Failed settlement returned to ISP wallet.',
                    'created_by' => $request->user()?->id,
                ]);
            }

            $settlement->forceFill([
                'status' => 'failed',
                'failure_reason' => $data['failure_reason'],
                'processed_by' => $request->user()?->id,
                'processed_at' => now(),
                'failed_at' => now(),
            ])->save();
        });

        return back()->with('success', 'Settlement marked as failed and funds returned to available balance.');
    }

    private function makeSettlementNumber(): string
    {
        do {
            $number = 'STL-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (IspSettlement::where('settlement_number', $number)->exists());

        return $number;
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
