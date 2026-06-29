<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_settlements')) {
            return;
        }

        Schema::create('isp_settlements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('isp_id')->index();
            $table->unsignedBigInteger('isp_wallet_id')->nullable()->index();

            $table->string('settlement_number')->unique();

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->string('currency')->default('KES');

            $table->string('payout_method')->default('mpesa');
            // mpesa | bank | manual

            $table->string('payout_name')->nullable();
            $table->string('payout_phone')->nullable();
            $table->string('payout_shortcode')->nullable();

            $table->string('status')->default('pending')->index();
            // pending | approved | processing | paid | failed | cancelled

            $table->string('mpesa_receipt_number')->nullable()->index();
            $table->string('transaction_reference')->nullable()->index();

            $table->text('notes')->nullable();
            $table->text('failure_reason')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index(['isp_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['payout_method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_settlements');
    }
};