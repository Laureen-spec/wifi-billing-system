<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_customers')) {
            Schema::table('isp_customers', function (Blueprint $table) {
                if (! Schema::hasColumn('isp_customers', 'mikrotik_router_id')) {
                    $table->foreignId('mikrotik_router_id')->nullable()->after('internet_package_id')->constrained('mikrotik_routers')->nullOnDelete();
                }
                if (! Schema::hasColumn('isp_customers', 'access_type')) {
                    $table->string('access_type')->default('hotspot')->after('mikrotik_router_id');
                }
                if (! Schema::hasColumn('isp_customers', 'username')) {
                    $table->string('username')->nullable()->after('access_type');
                }
                if (! Schema::hasColumn('isp_customers', 'password')) {
                    $table->string('password')->nullable()->after('username');
                }
                if (! Schema::hasColumn('isp_customers', 'mac_address')) {
                    $table->string('mac_address')->nullable()->after('password');
                }
                if (! Schema::hasColumn('isp_customers', 'ip_address')) {
                    $table->string('ip_address')->nullable()->after('mac_address');
                }
                if (! Schema::hasColumn('isp_customers', 'shared_users')) {
                    $table->unsignedInteger('shared_users')->default(1)->after('ip_address');
                }
                if (! Schema::hasColumn('isp_customers', 'data_used_bytes')) {
                    $table->unsignedBigInteger('data_used_bytes')->default(0)->after('shared_users');
                }
                if (! Schema::hasColumn('isp_customers', 'last_online_at')) {
                    $table->timestamp('last_online_at')->nullable()->after('data_used_bytes');
                }
                if (! Schema::hasColumn('isp_customers', 'provisioning_status')) {
                    $table->string('provisioning_status')->default('pending')->after('billing_status');
                }
            });
        }

        if (Schema::hasTable('provisioning_tokens')) {
            Schema::table('provisioning_tokens', function (Blueprint $table) {
                if (! Schema::hasColumn('provisioning_tokens', 'command')) {
                    $table->longText('command')->nullable()->after('status');
                }
                if (! Schema::hasColumn('provisioning_tokens', 'result_message')) {
                    $table->text('result_message')->nullable()->after('command');
                }
                if (! Schema::hasColumn('provisioning_tokens', 'attempted_at')) {
                    $table->timestamp('attempted_at')->nullable()->after('result_message');
                }
                if (! Schema::hasColumn('provisioning_tokens', 'provisioned_at')) {
                    $table->timestamp('provisioned_at')->nullable()->after('attempted_at');
                }
            });
        }
    }

    public function down(): void
    {
        // Keep data safe. Do not drop columns automatically in production billing systems.
    }
};
