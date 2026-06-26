<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provisioning_tokens')) {
            Schema::create('provisioning_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->nullable()->constrained('isps')->nullOnDelete();
                $table->foreignId('mikrotik_router_id')->nullable()->constrained('mikrotik_routers')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('isp_customers')->nullOnDelete();
                $table->foreignId('internet_package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
                $table->string('token')->unique();
                $table->string('provision_type')->default('hotspot');
                $table->string('status')->default('active');
                $table->timestamp('used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            return;
        }

        Schema::table('provisioning_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('provisioning_tokens', 'isp_id')) {
                $table->foreignId('isp_id')->nullable()->after('id')->constrained('isps')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_tokens', 'mikrotik_router_id')) {
                $table->foreignId('mikrotik_router_id')->nullable()->after('isp_id')->constrained('mikrotik_routers')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_tokens', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('mikrotik_router_id')->constrained('isp_customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_tokens', 'internet_package_id')) {
                $table->foreignId('internet_package_id')->nullable()->after('customer_id')->constrained('internet_packages')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_tokens', 'token')) {
                $table->string('token')->unique()->after('internet_package_id');
            }
            if (! Schema::hasColumn('provisioning_tokens', 'provision_type')) {
                $table->string('provision_type')->default('hotspot')->after('token');
            }
            if (! Schema::hasColumn('provisioning_tokens', 'status')) {
                $table->string('status')->default('active')->after('provision_type');
            }
            if (! Schema::hasColumn('provisioning_tokens', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('provisioning_tokens', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('used_at');
            }
            if (! Schema::hasColumn('provisioning_tokens', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('expires_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('provisioning_tokens', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_tokens');
    }
};
