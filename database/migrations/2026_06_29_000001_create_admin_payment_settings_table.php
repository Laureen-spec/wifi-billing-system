<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('mode')->default('system_payment');
            $table->string('gateway')->default('mpesa');
            $table->string('method_type')->nullable();
            $table->string('till_number')->nullable();
            $table->string('paybill_number')->nullable();
            $table->string('account_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('fee_handling')->default('add_to_checkout');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_payment_settings');
    }
};
