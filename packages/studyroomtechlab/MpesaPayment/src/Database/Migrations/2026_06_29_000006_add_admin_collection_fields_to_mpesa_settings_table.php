<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mpesa_settings')) {
            return;
        }

        Schema::table('mpesa_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('mpesa_settings', 'active_gateway')) {
                $table->string('active_gateway')->default('mpesa')->after('collection_mode');
            }

            if (! Schema::hasColumn('mpesa_settings', 'system_payment_channel')) {
                $table->string('system_payment_channel')->nullable()->after('allow_isp_direct');
            }

            if (! Schema::hasColumn('mpesa_settings', 'system_till_number')) {
                $table->string('system_till_number')->nullable()->after('system_payment_channel');
            }

            if (! Schema::hasColumn('mpesa_settings', 'system_paybill_number')) {
                $table->string('system_paybill_number')->nullable()->after('system_till_number');
            }

            if (! Schema::hasColumn('mpesa_settings', 'system_account_number')) {
                $table->string('system_account_number')->nullable()->after('system_paybill_number');
            }

            if (! Schema::hasColumn('mpesa_settings', 'system_phone_number')) {
                $table->string('system_phone_number')->nullable()->after('system_account_number');
            }

            if (! Schema::hasColumn('mpesa_settings', 'documentation_url')) {
                $table->string('documentation_url')->nullable()->after('system_phone_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('mpesa_settings')) {
            return;
        }

        Schema::table('mpesa_settings', function (Blueprint $table) {
            foreach ([
                'documentation_url',
                'system_phone_number',
                'system_account_number',
                'system_paybill_number',
                'system_till_number',
                'system_payment_channel',
                'active_gateway',
            ] as $column) {
                if (Schema::hasColumn('mpesa_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
