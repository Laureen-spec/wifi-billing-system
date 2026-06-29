<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotspot_free_access_logs')) {
            Schema::create('hotspot_free_access_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable();
                $table->unsignedBigInteger('mikrotik_router_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('mac_address', 80)->nullable();
                $table->string('ip_address', 80)->nullable();
                $table->string('username')->nullable();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('cooldown_until')->nullable();
                $table->string('status', 30)->default('active');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['mac_address', 'cooldown_until'], 'hotspot_free_access_mac_cooldown_idx');
                $table->index(['phone', 'cooldown_until'], 'hotspot_free_access_phone_cooldown_idx');
                $table->index(['status', 'expires_at'], 'hotspot_free_access_status_expires_idx');
            });
        }

        if (Schema::hasTable('wifi_billing_hotspot_template_settings')) {
            Schema::table('wifi_billing_hotspot_template_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'enable_datalan_free_access')) {
                    $table->boolean('enable_datalan_free_access')->default(false);
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_duration_minutes')) {
                    $table->unsignedInteger('free_access_duration_minutes')->default(60);
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_cooldown_hours')) {
                    $table->unsignedInteger('free_access_cooldown_hours')->default(24);
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_package_id')) {
                    $table->unsignedBigInteger('free_access_package_id')->nullable();
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_speed_limit')) {
                    $table->string('free_access_speed_limit')->nullable();
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_identity_mode')) {
                    $table->string('free_access_identity_mode', 20)->default('mac');
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_requires_phone')) {
                    $table->boolean('free_access_requires_phone')->default(false);
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_requires_name')) {
                    $table->boolean('free_access_requires_name')->default(false);
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_button_text')) {
                    $table->string('free_access_button_text')->nullable()->default('Get 1 hour free access');
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_cooldown_message')) {
                    $table->string('free_access_cooldown_message')->nullable()->default('You already used free access. Come back after @time_remaining.');
                }

                if (! Schema::hasColumn('wifi_billing_hotspot_template_settings', 'free_access_success_message')) {
                    $table->string('free_access_success_message')->nullable()->default('Free access is active for @duration minutes.');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wifi_billing_hotspot_template_settings')) {
            Schema::table('wifi_billing_hotspot_template_settings', function (Blueprint $table) {
                foreach ([
                    'enable_datalan_free_access',
                    'free_access_duration_minutes',
                    'free_access_cooldown_hours',
                    'free_access_package_id',
                    'free_access_speed_limit',
                    'free_access_identity_mode',
                    'free_access_requires_phone',
                    'free_access_requires_name',
                    'free_access_button_text',
                    'free_access_cooldown_message',
                    'free_access_success_message',
                ] as $column) {
                    if (Schema::hasColumn('wifi_billing_hotspot_template_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('hotspot_free_access_logs');
    }
};
