<?php

namespace StudyRoomTechLab\MpesaPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MpesaSetting extends Model
{
    protected $table = 'mpesa_settings';

    protected $fillable = [
        'isp_id',
        'owner_type',
        'collection_mode',
        'active_gateway',
        'environment',
        'business_name',
        'shortcode',
        'account_reference',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'callback_url',
        'commission_type',
        'commission_value',
        'is_default',
        'is_active',
        'allow_isp_direct',
        'system_payment_channel',
        'system_till_number',
        'system_paybill_number',
        'system_account_number',
        'system_phone_number',
        'documentation_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'allow_isp_direct' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class, 'mpesa_setting_id');
    }


    public function toPlatformPayload(): array
    {
        return [
            'id' => $this->id,
            'active_gateway' => $this->active_gateway ?: 'mpesa',
            'environment' => $this->environment ?: 'sandbox',
            'business_name' => $this->business_name,
            'shortcode' => $this->shortcode,
            'account_reference' => $this->account_reference,
            'callback_url' => $this->callback_url,
            'commission_type' => $this->commission_type ?: 'percentage',
            'commission_value' => $this->commission_value,
            'allow_isp_direct' => (bool) $this->allow_isp_direct,
            'is_active' => (bool) $this->is_active,
            'documentation_url' => $this->documentation_url,
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'has_consumer_key' => ! empty($this->consumer_key),
            'has_consumer_secret' => ! empty($this->consumer_secret),
            'has_passkey' => ! empty($this->passkey),
        ];
    }

    public function toAdminPayload(): array
    {
        return [
            'id' => $this->id,
            'payment_mode' => $this->collection_mode === 'isp_direct' ? 'own' : 'system',
            'gateway' => $this->active_gateway ?: 'mpesa',
            'environment' => $this->environment ?: 'sandbox',
            'business_name' => $this->business_name,
            'shortcode' => $this->shortcode,
            'account_reference' => $this->account_reference,
            'callback_url' => $this->callback_url,
            'system_payment_channel' => $this->system_payment_channel ?: 'till',
            'system_till_number' => $this->system_till_number,
            'system_paybill_number' => $this->system_paybill_number,
            'system_account_number' => $this->system_account_number,
            'system_phone_number' => $this->system_phone_number,
            'documentation_url' => $this->documentation_url,
            'is_active' => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'has_consumer_key' => ! empty($this->consumer_key),
            'has_consumer_secret' => ! empty($this->consumer_secret),
            'has_passkey' => ! empty($this->passkey),
        ];
    }

    public function isPlatform(): bool
    {
        return $this->owner_type === 'platform' || $this->isp_id === null;
    }

    public function isIspDirect(): bool
    {
        return $this->collection_mode === 'isp_direct';
    }

    public function decryptedConsumerKey(): ?string
    {
        return $this->decryptValue($this->consumer_key);
    }

    public function decryptedConsumerSecret(): ?string
    {
        return $this->decryptValue($this->consumer_secret);
    }

    public function decryptedPasskey(): ?string
    {
        return $this->decryptValue($this->passkey);
    }

    private function decryptValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
