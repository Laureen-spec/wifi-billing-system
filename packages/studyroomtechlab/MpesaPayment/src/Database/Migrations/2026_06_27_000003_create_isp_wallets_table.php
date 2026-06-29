<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_wallets')) {
            return;
        }

        Schema::create('isp_wallets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('isp_id')->unique();

            $table->decimal('available_balance', 12, 2)->default(0);
            $table->decimal('pending_balance', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid_out', 12, 2)->default(0);
            $table->decimal('total_platform_fee', 12, 2)->default(0);

            $table->string('currency')->default('KES');

            // ISP payout details
            $table->string('payout_name')->nullable();
            $table->string('payout_phone')->nullable();
            $table->string('payout_shortcode')->nullable();
            $table->string('payout_method')->default('mpesa');
            // mpesa | bank | manual

            $table->boolean('auto_settlement_enabled')->default(false);
            $table->decimal('minimum_settlement_amount', 12, 2)->default(500);
            $table->string('settlement_schedule')->default('manual');
            // manual | daily | weekly | monthly

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active']);
            $table->index(['auto_settlement_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_wallets');
    }
};