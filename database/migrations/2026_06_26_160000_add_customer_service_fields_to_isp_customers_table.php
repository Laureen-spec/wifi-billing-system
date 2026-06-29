<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_customers')) return;

        Schema::table('isp_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('isp_customers', 'mikrotik_router_id')) $table->foreignId('mikrotik_router_id')->nullable()->after('internet_package_id')->constrained('mikrotik_routers')->nullOnDelete();
            if (! Schema::hasColumn('isp_customers', 'access_type')) $table->string('access_type')->default('hotspot')->after('mikrotik_router_id');
            if (! Schema::hasColumn('isp_customers', 'username')) $table->string('username')->nullable()->after('access_type');
            if (! Schema::hasColumn('isp_customers', 'password')) $table->string('password')->nullable()->after('username');
            if (! Schema::hasColumn('isp_customers', 'mac_address')) $table->string('mac_address')->nullable()->after('password');
            if (! Schema::hasColumn('isp_customers', 'ip_address')) $table->string('ip_address')->nullable()->after('mac_address');
            if (! Schema::hasColumn('isp_customers', 'shared_users')) $table->unsignedInteger('shared_users')->default(1)->after('ip_address');
            if (! Schema::hasColumn('isp_customers', 'data_used_bytes')) $table->unsignedBigInteger('data_used_bytes')->default(0)->after('shared_users');
            if (! Schema::hasColumn('isp_customers', 'last_online_at')) $table->timestamp('last_online_at')->nullable()->after('data_used_bytes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('isp_customers')) return;
        Schema::table('isp_customers', function (Blueprint $table) {
            foreach (['last_online_at','data_used_bytes','shared_users','ip_address','mac_address','password','username','access_type'] as $column) {
                if (Schema::hasColumn('isp_customers', $column)) $table->dropColumn($column);
            }
            if (Schema::hasColumn('isp_customers', 'mikrotik_router_id')) $table->dropConstrainedForeignId('mikrotik_router_id');
        });
    }
};
