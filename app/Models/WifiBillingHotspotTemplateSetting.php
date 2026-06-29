<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WifiBillingHotspotTemplateSetting extends Model
{
    protected $fillable = [
        'isp_id',
        'template_key',
        'template_name',
        'logo_path',
        'background_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'welcome_text',
        'footer_text',
        'care_phone',
        'redirect_url',
        'language',
        'purchase_instructions',
        'custom_css',
    ];

    protected function casts(): array
    {
        return [
            'purchase_instructions' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }
}
