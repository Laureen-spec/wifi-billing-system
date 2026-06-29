<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_payment_center_manual_payments')) {
            return;
        }

        Schema::table('isp_payment_center_manual_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('isp_payment_center_manual_payments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('isp_payment_center_manual_payments') || ! Schema::hasColumn('isp_payment_center_manual_payments', 'customer_id')) {
            return;
        }

        Schema::table('isp_payment_center_manual_payments', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
