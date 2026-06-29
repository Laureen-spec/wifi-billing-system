<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_wallet_ledger')) {
            return;
        }

        Schema::create('isp_wallet_ledger', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('isp_id')->index();
            $table->unsignedBigInteger('isp_wallet_id')->nullable()->index();

            $table->string('type')->index();
            // credit | debit | platform_fee | settlement | reversal | adjustment

            $table->string('source')->nullable()->index();
            // mpesa_payment | settlement | manual_adjustment | reversal

            $table->unsignedBigInteger('mpesa_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('isp_settlement_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('balance_before', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);

            $table->string('currency')->default('KES');

            $table->string('reference')->nullable()->index();
            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['isp_id', 'type']);
            $table->index(['isp_id', 'created_at']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_wallet_ledger');
    }
};