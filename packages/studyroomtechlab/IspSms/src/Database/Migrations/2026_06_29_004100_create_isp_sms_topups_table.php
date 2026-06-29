<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_sms_topups')) {
            Schema::create('isp_sms_topups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('topup_number', 40)->unique();
                $table->string('order_id', 80)->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 12)->default('KES');
                $table->unsignedInteger('sms_units')->default(0);
                $table->string('payment_method', 40)->default('checkout')->index();
                $table->string('status', 40)->default('pending')->index();
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_sms_topups');
    }
};
