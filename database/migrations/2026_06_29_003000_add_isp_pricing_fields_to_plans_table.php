<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'hotspot_revenue_fee_percent')) {
                $table->decimal('hotspot_revenue_fee_percent', 5, 2)->default(2.50)->after('trial_days');
            }

            if (!Schema::hasColumn('plans', 'router_limit')) {
                $table->unsignedInteger('router_limit')->nullable()->after('hotspot_revenue_fee_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('plans')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'router_limit')) {
                $table->dropColumn('router_limit');
            }

            if (Schema::hasColumn('plans', 'hotspot_revenue_fee_percent')) {
                $table->dropColumn('hotspot_revenue_fee_percent');
            }
        });
    }
};
