<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provisioning_logs')) {
            Schema::create('provisioning_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provisioning_token_id')->nullable()->constrained('provisioning_tokens')->nullOnDelete();
                $table->foreignId('mikrotik_router_id')->nullable()->constrained('mikrotik_routers')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('isp_customers')->nullOnDelete();
                $table->string('token')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('status');
                $table->text('message')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('provisioning_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('provisioning_logs', 'provisioning_token_id')) {
                $table->foreignId('provisioning_token_id')->nullable()->after('id')->constrained('provisioning_tokens')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_logs', 'mikrotik_router_id')) {
                $table->foreignId('mikrotik_router_id')->nullable()->after('provisioning_token_id')->constrained('mikrotik_routers')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_logs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('mikrotik_router_id')->constrained('isp_customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_logs', 'token')) {
                $table->string('token')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('provisioning_logs', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('token');
            }
            if (! Schema::hasColumn('provisioning_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('provisioning_logs', 'status')) {
                $table->string('status')->after('user_agent');
            }
            if (! Schema::hasColumn('provisioning_logs', 'message')) {
                $table->text('message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('provisioning_logs', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_logs');
    }
};
