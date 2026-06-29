<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loyalty_settings')) {
            Schema::create('loyalty_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('default_points_per_payment')->default(10);
                $table->unsignedInteger('points_per_amount')->default(1);
                $table->decimal('amount_step', 12, 2)->default(100);
                $table->unsignedInteger('voucher_threshold')->default(100);
                $table->string('voucher_package_name')->nullable();
                $table->unsignedInteger('voucher_duration_minutes')->default(60);
                $table->unsignedInteger('points_expiry_days')->nullable();
                $table->boolean('auto_generate_voucher')->default(true);
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();

                $table->unique('isp_id', 'loyalty_settings_isp_unique');
            });
        }

        if (! Schema::hasTable('loyalty_reward_rules')) {
            Schema::create('loyalty_reward_rules', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->index();
                $table->string('name');
                $table->string('trigger_type', 40)->index();
                $table->unsignedInteger('points_value')->default(0);
                $table->decimal('amount_step', 12, 2)->nullable();
                $table->unsignedInteger('renewal_count')->nullable();
                $table->boolean('auto_voucher')->default(false);
                $table->unsignedInteger('voucher_threshold')->nullable();
                $table->string('voucher_package_name')->nullable();
                $table->unsignedInteger('voucher_duration_minutes')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('loyalty_customer_points')) {
            Schema::create('loyalty_customer_points', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedInteger('current_points')->default(0);
                $table->unsignedInteger('lifetime_points')->default(0);
                $table->unsignedInteger('redeemed_points')->default(0);
                $table->timestamp('last_awarded_at')->nullable();
                $table->timestamps();

                $table->unique(['isp_id', 'customer_id'], 'loyalty_customer_points_unique');
            });
        }

        if (! Schema::hasTable('loyalty_point_transactions')) {
            Schema::create('loyalty_point_transactions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('loyalty_customer_point_id')->nullable()->index();
                $table->string('type', 30)->index();
                $table->unsignedInteger('points');
                $table->string('source_type')->nullable();
                $table->string('source_id')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('expired_at')->nullable()->index();
                $table->timestamps();

                $table->index(['source_type', 'source_id'], 'loyalty_transactions_source_index');
            });
        }

        if (! Schema::hasTable('loyalty_vouchers')) {
            Schema::create('loyalty_vouchers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('voucher_code')->unique();
                $table->unsignedInteger('points_used');
                $table->string('package_name')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->string('status', 30)->default('unused')->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_vouchers');
        Schema::dropIfExists('loyalty_point_transactions');
        Schema::dropIfExists('loyalty_customer_points');
        Schema::dropIfExists('loyalty_reward_rules');
        Schema::dropIfExists('loyalty_settings');
    }
};
