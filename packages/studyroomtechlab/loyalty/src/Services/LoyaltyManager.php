<?php

namespace StudyRoomTechLab\Loyalty\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use StudyRoomTechLab\Loyalty\Models\LoyaltyCustomerPoint;
use StudyRoomTechLab\Loyalty\Models\LoyaltyPointTransaction;
use StudyRoomTechLab\Loyalty\Models\LoyaltyRewardRule;
use StudyRoomTechLab\Loyalty\Models\LoyaltySetting;
use StudyRoomTechLab\Loyalty\Models\LoyaltyVoucher;

class LoyaltyManager
{
    public function awardForPayment(mixed $customer, float|int|string $amount, mixed $source = null): array
    {
        $customerId = $this->customerId($customer);
        $ispId = $this->customerIspId($customer);

        if (! $customerId || ! $ispId) {
            return ['awarded' => false, 'points' => 0, 'reason' => 'missing_customer_or_isp'];
        }

        $settings = $this->settingsForIsp($ispId);
        if (! $settings->enabled) {
            return ['awarded' => false, 'points' => 0, 'reason' => 'loyalty_disabled'];
        }

        $amountValue = max(0, (float) $amount);
        $points = (int) $settings->default_points_per_payment;
        $points += $this->amountBasedPoints(
            $amountValue,
            (float) $settings->amount_step,
            (int) $settings->points_per_amount
        );
        $sourcePayload = $this->sourcePayload($source);
        $rules = LoyaltyRewardRule::query()
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $points += $this->pointsForRule($rule, $amountValue, $settings, $sourcePayload);
        }

        if ($points <= 0) {
            return ['awarded' => false, 'points' => 0, 'reason' => 'no_points_configured'];
        }

        $ledger = $this->addPoints(
            $ispId,
            $customerId,
            $points,
            LoyaltyPointTransaction::TYPE_EARNED,
            $sourcePayload['source_type'],
            $sourcePayload['source_id'],
            $sourcePayload['description'] ?: 'Points awarded for successful payment.',
            $settings
        );

        $voucher = null;
        if ($settings->auto_generate_voucher) {
            $rule = $this->eligibleVoucherRule($ispId, (int) $ledger->current_points);
            $threshold = $rule?->voucher_threshold ?: (int) $settings->voucher_threshold;

            if ($threshold > 0 && (int) $ledger->current_points >= $threshold) {
                $voucher = $this->generateVoucher($customer, $rule);
            }
        }

        return [
            'awarded' => true,
            'points' => $points,
            'current_points' => (int) $ledger->fresh()->current_points,
            'voucher' => $voucher,
        ];
    }

    public function awardManualPoints(mixed $customer, int $points, ?string $description = null): LoyaltyCustomerPoint
    {
        $customerId = $this->customerId($customer);
        $ispId = $this->customerIspId($customer);

        if (! $customerId || ! $ispId) {
            throw ValidationException::withMessages([
                'customer_id' => 'A valid ISP customer is required before loyalty points can be adjusted.',
            ]);
        }

        if ($points <= 0) {
            throw ValidationException::withMessages([
                'points' => 'Manual points must be greater than zero.',
            ]);
        }

        return $this->addPoints(
            $ispId,
            $customerId,
            $points,
            LoyaltyPointTransaction::TYPE_ADJUSTED,
            LoyaltyRewardRule::TRIGGER_MANUAL_BONUS,
            null,
            $description ?: 'Manual loyalty point adjustment.',
            $this->settingsForIsp($ispId)
        );
    }

    public function generateVoucher(mixed $customer, ?LoyaltyRewardRule $rule = null): LoyaltyVoucher
    {
        $customerId = $this->customerId($customer);
        $ispId = $this->customerIspId($customer);

        if (! $customerId || ! $ispId) {
            throw ValidationException::withMessages([
                'customer_id' => 'A valid ISP customer is required before a voucher can be generated.',
            ]);
        }

        $settings = $this->settingsForIsp($ispId);
        $threshold = (int) ($rule?->voucher_threshold ?: $settings->voucher_threshold);

        if ($threshold <= 0) {
            throw ValidationException::withMessages([
                'voucher_threshold' => 'Set a valid voucher threshold before generating vouchers.',
            ]);
        }

        return DB::transaction(function () use ($customerId, $ispId, $settings, $rule, $threshold): LoyaltyVoucher {
            $ledger = $this->ledgerForUpdate($ispId, $customerId);

            if ((int) $ledger->current_points < $threshold) {
                throw ValidationException::withMessages([
                    'points' => 'This customer does not have enough points for a voucher yet.',
                ]);
            }

            $voucher = LoyaltyVoucher::query()->create([
                'isp_id' => $ispId,
                'customer_id' => $customerId,
                'voucher_code' => $this->uniqueVoucherCode(),
                'points_used' => $threshold,
                'package_name' => $rule?->voucher_package_name ?: $settings->voucher_package_name,
                'duration_minutes' => $rule?->voucher_duration_minutes ?: $settings->voucher_duration_minutes,
                'status' => LoyaltyVoucher::STATUS_UNUSED,
                'expires_at' => $this->voucherExpiresAt($settings),
                'created_by' => auth()->id(),
            ]);

            $ledger->forceFill([
                'current_points' => max(0, (int) $ledger->current_points - $threshold),
                'redeemed_points' => (int) $ledger->redeemed_points + $threshold,
            ])->save();

            LoyaltyPointTransaction::query()->create([
                'isp_id' => $ispId,
                'customer_id' => $customerId,
                'loyalty_customer_point_id' => $ledger->id,
                'type' => LoyaltyPointTransaction::TYPE_REDEEMED,
                'points' => $threshold,
                'source_type' => LoyaltyVoucher::class,
                'source_id' => (string) $voucher->id,
                'description' => 'Redeemed points for loyalty voucher ' . $voucher->voucher_code . '.',
                'created_by' => auth()->id(),
            ]);

            return $voucher->fresh();
        });
    }

    public function redeemVoucher(mixed $voucher): LoyaltyVoucher
    {
        $record = $voucher instanceof LoyaltyVoucher
            ? $voucher
            : LoyaltyVoucher::query()->where('voucher_code', (string) $voucher)->firstOrFail();

        if ($record->status !== LoyaltyVoucher::STATUS_UNUSED) {
            throw ValidationException::withMessages([
                'voucher' => 'Only unused loyalty vouchers can be redeemed.',
            ]);
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            $record->forceFill(['status' => LoyaltyVoucher::STATUS_EXPIRED])->save();

            throw ValidationException::withMessages([
                'voucher' => 'This loyalty voucher has expired.',
            ]);
        }

        $record->forceFill([
            'status' => LoyaltyVoucher::STATUS_REDEEMED,
            'redeemed_at' => now(),
        ])->save();

        return $record->fresh();
    }

    public function expireOldPoints(): int
    {
        $expired = 0;

        LoyaltyPointTransaction::query()
            ->where('type', LoyaltyPointTransaction::TYPE_EARNED)
            ->whereNull('expired_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$expired): void {
                foreach ($transactions as $transaction) {
                    DB::transaction(function () use ($transaction, &$expired): void {
                        $ledger = $this->ledgerForUpdate((int) $transaction->isp_id, (int) $transaction->customer_id);
                        $pointsToExpire = min((int) $transaction->points, (int) $ledger->current_points);

                        $transaction->forceFill(['expired_at' => now()])->save();

                        if ($pointsToExpire <= 0) {
                            return;
                        }

                        $ledger->forceFill([
                            'current_points' => max(0, (int) $ledger->current_points - $pointsToExpire),
                        ])->save();

                        LoyaltyPointTransaction::query()->create([
                            'isp_id' => $transaction->isp_id,
                            'customer_id' => $transaction->customer_id,
                            'loyalty_customer_point_id' => $ledger->id,
                            'type' => LoyaltyPointTransaction::TYPE_EXPIRED,
                            'points' => $pointsToExpire,
                            'source_type' => LoyaltyPointTransaction::class,
                            'source_id' => (string) $transaction->id,
                            'description' => 'Expired loyalty points.',
                        ]);

                        $expired += $pointsToExpire;
                    });
                }
            });

        return $expired;
    }

    public function expireOldVouchers(): int
    {
        return LoyaltyVoucher::query()
            ->where('status', LoyaltyVoucher::STATUS_UNUSED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => LoyaltyVoucher::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    private function addPoints(
        int $ispId,
        int $customerId,
        int $points,
        string $type,
        ?string $sourceType,
        ?string $sourceId,
        string $description,
        LoyaltySetting $settings
    ): LoyaltyCustomerPoint {
        return DB::transaction(function () use ($ispId, $customerId, $points, $type, $sourceType, $sourceId, $description, $settings): LoyaltyCustomerPoint {
            $ledger = $this->ledgerForUpdate($ispId, $customerId);
            $ledger->forceFill([
                'current_points' => (int) $ledger->current_points + $points,
                'lifetime_points' => (int) $ledger->lifetime_points + $points,
                'last_awarded_at' => now(),
            ])->save();

            LoyaltyPointTransaction::query()->create([
                'isp_id' => $ispId,
                'customer_id' => $customerId,
                'loyalty_customer_point_id' => $ledger->id,
                'type' => $type,
                'points' => $points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => auth()->id(),
                'expires_at' => $this->pointsExpireAt($settings),
            ]);

            return $ledger->fresh();
        });
    }

    private function ledgerForUpdate(int $ispId, int $customerId): LoyaltyCustomerPoint
    {
        $ledger = LoyaltyCustomerPoint::query()
            ->where('isp_id', $ispId)
            ->where('customer_id', $customerId)
            ->lockForUpdate()
            ->first();

        if ($ledger) {
            return $ledger;
        }

        return LoyaltyCustomerPoint::query()->create([
            'isp_id' => $ispId,
            'customer_id' => $customerId,
            'current_points' => 0,
            'lifetime_points' => 0,
            'redeemed_points' => 0,
        ]);
    }

    private function pointsForRule(LoyaltyRewardRule $rule, float $amount, LoyaltySetting $settings, array $sourcePayload): int
    {
        return match ($rule->trigger_type) {
            LoyaltyRewardRule::TRIGGER_SUCCESSFUL_PAYMENT => (int) $rule->points_value,
            LoyaltyRewardRule::TRIGGER_AMOUNT_SPENT => $this->amountBasedPoints($amount, (float) ($rule->amount_step ?: $settings->amount_step), (int) $rule->points_value),
            LoyaltyRewardRule::TRIGGER_RENEWAL_COUNT => $this->renewalPoints($rule, $sourcePayload),
            LoyaltyRewardRule::TRIGGER_ON_TIME_PAYMENT => ! empty($sourcePayload['on_time_payment']) ? (int) $rule->points_value : 0,
            default => 0,
        };
    }

    private function amountBasedPoints(float $amount, float $step, int $pointsPerStep): int
    {
        if ($amount <= 0 || $step <= 0 || $pointsPerStep <= 0) {
            return 0;
        }

        return (int) floor($amount / $step) * $pointsPerStep;
    }

    private function renewalPoints(LoyaltyRewardRule $rule, array $sourcePayload): int
    {
        $required = (int) ($rule->renewal_count ?: 0);
        $actual = (int) ($sourcePayload['renewal_count'] ?? 0);

        if ($required <= 0 || $actual < $required) {
            return 0;
        }

        return (int) $rule->points_value;
    }

    private function settingsForIsp(int $ispId): LoyaltySetting
    {
        return LoyaltySetting::query()->firstOrCreate(
            ['isp_id' => $ispId],
            LoyaltySetting::defaults($ispId)
        );
    }

    private function eligibleVoucherRule(int $ispId, int $currentPoints): ?LoyaltyRewardRule
    {
        return LoyaltyRewardRule::query()
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->where('auto_voucher', true)
            ->whereNotNull('voucher_threshold')
            ->where('voucher_threshold', '<=', $currentPoints)
            ->orderByDesc('voucher_threshold')
            ->first();
    }

    private function pointsExpireAt(LoyaltySetting $settings): ?Carbon
    {
        $days = (int) ($settings->points_expiry_days ?: 0);

        return $days > 0 ? now()->addDays($days) : null;
    }

    private function voucherExpiresAt(LoyaltySetting $settings): ?Carbon
    {
        $days = (int) ($settings->points_expiry_days ?: 0);

        return $days > 0 ? now()->addDays($days) : null;
    }

    private function uniqueVoucherCode(): string
    {
        do {
            $code = 'LOY-' . now()->format('ymd') . '-' . Str::upper(Str::random(8));
        } while (LoyaltyVoucher::query()->where('voucher_code', $code)->exists());

        return $code;
    }

    private function customerId(mixed $customer): ?int
    {
        if ($customer instanceof Model) {
            return (int) $customer->getKey();
        }

        if (is_array($customer)) {
            return isset($customer['id']) ? (int) $customer['id'] : null;
        }

        return is_object($customer) && isset($customer->id) ? (int) $customer->id : null;
    }

    private function customerIspId(mixed $customer): ?int
    {
        if ($customer instanceof Model) {
            return isset($customer->isp_id) ? (int) $customer->isp_id : null;
        }

        if (is_array($customer)) {
            return isset($customer['isp_id']) ? (int) $customer['isp_id'] : null;
        }

        return is_object($customer) && isset($customer->isp_id) ? (int) $customer->isp_id : null;
    }

    private function sourcePayload(mixed $source): array
    {
        if ($source instanceof Model) {
            return [
                'source_type' => $source::class,
                'source_id' => (string) $source->getKey(),
                'description' => null,
                'on_time_payment' => false,
                'renewal_count' => null,
            ];
        }

        if (is_array($source)) {
            return [
                'source_type' => isset($source['source_type']) ? (string) $source['source_type'] : null,
                'source_id' => isset($source['source_id']) ? (string) $source['source_id'] : null,
                'description' => isset($source['description']) ? (string) $source['description'] : null,
                'on_time_payment' => (bool) ($source['on_time_payment'] ?? false),
                'renewal_count' => $source['renewal_count'] ?? null,
            ];
        }

        return [
            'source_type' => is_string($source) ? $source : null,
            'source_id' => null,
            'description' => null,
            'on_time_payment' => false,
            'renewal_count' => null,
        ];
    }
}
