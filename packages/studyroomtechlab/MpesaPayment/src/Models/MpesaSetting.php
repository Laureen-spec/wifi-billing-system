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
