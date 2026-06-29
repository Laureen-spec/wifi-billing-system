<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mpesa_transactions')) {
            return;
        }

        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();

            // ISP/admin who owns this payment record
            $table->unsignedBigInteger('isp_id')->nullable()->index();

            // Customer/package from WiFiBilling
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('internet_package_id')->nullable()->index();
            $table->unsignedBigInteger('mikrotik_router_id')->nullable()->index();

            // M-Pesa setting used: platform or ISP direct
            $table->unsignedBigInteger('mpesa_setting_id')->nullable()->index();

            $table->string('collection_mode')->default('platform');
            // platform | isp_direct

            $table->string('environment')->default('sandbox');
            // sandbox | live

            $table->string('payment_type')->default('stk_push');
            // stk_push | c2b | manual

            $table->string('phone')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);

            $table->string('currency')->default('KES');

            $table->string('merchant_request_id')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt_number')->nullable()->index();

            $table->string('account_reference')->nullable();
            $table->string('transaction_desc')->nullable();

            $table->string('status')->default('pending')->index();
            // pending | stk_sent | paid | failed | cancelled | expired | reversed

            $table->integer('result_code')->nullable();
            $table->text('result_desc')->nullable();

            // Raw responses for audit/debug
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('callback_payload')->nullable();

            // Wallet/commission calculation
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('isp_amount', 12, 2)->default(0);
            $table->boolean('wallet_posted')->default(false);

            // Provisioning link
            $table->unsignedBigInteger('provisioning_token_id')->nullable()->index();
            $table->boolean('provisioning_triggered')->default(false);
            $table->timestamp('provisioned_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['isp_id', 'status']);
            $table->index(['collection_mode', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};