<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_payment_center_manual_payments')) {
            return;
        }

        Schema::create('isp_payment_center_manual_payments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('account')->nullable();
            $table->string('receipt')->nullable()->index();
            $table->string('method')->default('Cash')->index();
            $table->string('source')->default('Manual');
            $table->string('package')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('KES');
            $table->string('status')->default('confirmed')->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_payment_center_manual_payments');
    }
};
