<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_sms_settings')) {
            return;
        }

        Schema::table('isp_sms_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('isp_sms_settings', 'allow_system_sms')) {
                $table->boolean('allow_system_sms')->default(true)->after('callback_url');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'allow_own_sms')) {
                $table->boolean('allow_own_sms')->default(true)->after('allow_system_sms');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'free_sms_remaining')) {
                $table->unsignedInteger('free_sms_remaining')->default(5)->after('allow_own_sms');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'sms_balance')) {
                $table->decimal('sms_balance', 12, 2)->default(0)->after('free_sms_remaining');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'estimated_cost_per_sms')) {
                $table->decimal('estimated_cost_per_sms', 12, 2)->default(1)->after('sms_balance');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'low_balance_alert_enabled')) {
                $table->boolean('low_balance_alert_enabled')->default(true)->after('estimated_cost_per_sms');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'low_balance_alert_threshold')) {
                $table->decimal('low_balance_alert_threshold', 12, 2)->default(10)->after('low_balance_alert_enabled');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'low_balance_alert_phone')) {
                $table->string('low_balance_alert_phone', 40)->nullable()->after('low_balance_alert_threshold');
            }

            if (! Schema::hasColumn('isp_sms_settings', 'low_balance_alerted_at')) {
                $table->timestamp('low_balance_alerted_at')->nullable()->after('low_balance_alert_phone');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('isp_sms_settings')) {
            return;
        }

        Schema::table('isp_sms_settings', function (Blueprint $table) {
            foreach (['allow_system_sms', 'allow_own_sms', 'free_sms_remaining', 'sms_balance', 'estimated_cost_per_sms', 'low_balance_alert_enabled', 'low_balance_alert_threshold', 'low_balance_alert_phone', 'low_balance_alerted_at'] as $column) {
                if (Schema::hasColumn('isp_sms_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
