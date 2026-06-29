<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mpesa_settings')) {
            return;
        }

        Schema::create('mpesa_settings', function (Blueprint $table) {
            $table->id();

            // null = Super Admin/platform M-Pesa
            // value = specific ISP/admin M-Pesa
            $table->unsignedBigInteger('isp_id')->nullable()->index();

            $table->string('owner_type')->default('platform'); 
            // platform | isp

            $table->string('collection_mode')->default('platform');
            // platform | isp_direct

            $table->string('environment')->default('sandbox');
            // sandbox | live

            $table->string('business_name')->nullable();
            $table->string('shortcode')->nullable();
            $table->string('account_reference')->nullable();

            // Store encrypted values here
            $table->text('consumer_key')->nullable();
            $table->text('consumer_secret')->nullable();
            $table->text('passkey')->nullable();

            $table->string('callback_url')->nullable();

            $table->string('commission_type')->default('percentage');
            // percentage | fixed | none

            $table->decimal('commission_value', 12, 2)->default(0);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_isp_direct')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['owner_type', 'is_active']);
            $table->index(['collection_mode', 'is_active']);
            $table->index(['environment', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_settings');
    }
};