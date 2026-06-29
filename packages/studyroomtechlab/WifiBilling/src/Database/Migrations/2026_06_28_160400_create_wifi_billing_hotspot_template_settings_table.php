<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wifi_billing_hotspot_template_settings')) {
            return;
        }

        Schema::create('wifi_billing_hotspot_template_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->nullable()->constrained('isps')->cascadeOnDelete();
            $table->string('template_key')->default('modern');
            $table->string('template_name')->default('Modern Hotspot');
            $table->string('logo_path')->nullable();
            $table->string('background_path')->nullable();
            $table->string('primary_color', 20)->default('#0f766e');
            $table->string('secondary_color', 20)->default('#0f172a');
            $table->string('accent_color', 20)->default('#f59e0b');
            $table->text('welcome_text')->nullable();
            $table->string('footer_text')->nullable();
            $table->string('care_phone')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->string('language', 12)->default('en');
            $table->json('purchase_instructions')->nullable();
            $table->text('custom_css')->nullable();
            $table->timestamps();

            $table->unique('isp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_billing_hotspot_template_settings');
    }
};
