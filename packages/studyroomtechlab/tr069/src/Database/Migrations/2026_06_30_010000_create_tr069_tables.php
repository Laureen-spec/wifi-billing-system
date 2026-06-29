<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tr069_settings')) {
            Schema::create('tr069_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->boolean('enabled')->default(false);
                $table->string('acs_url')->nullable();
                $table->string('api_token')->nullable();
                $table->unsignedInteger('inform_interval')->nullable();
                $table->string('connection_request_username')->nullable();
                $table->string('connection_request_password')->nullable();
                $table->unsignedBigInteger('default_profile_id')->nullable()->index();
                $table->boolean('allow_auto_register')->default(true);
                $table->boolean('require_known_serial')->default(false);
                $table->timestamps();

                $table->unique('isp_id', 'tr069_settings_isp_unique');
            });
        }

        if (! Schema::hasTable('tr069_cpe_devices')) {
            Schema::create('tr069_cpe_devices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('serial_number')->index();
                $table->string('oui')->nullable();
                $table->string('product_class')->nullable();
                $table->string('manufacturer')->nullable();
                $table->string('model')->nullable();
                $table->string('firmware_version')->nullable();
                $table->string('hardware_version')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('mac_address')->nullable()->index();
                $table->string('connection_request_url')->nullable();
                $table->string('connection_username')->nullable();
                $table->string('connection_password')->nullable();
                $table->timestamp('last_inform_at')->nullable()->index();
                $table->string('last_seen_ip')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['isp_id', 'serial_number'], 'tr069_devices_isp_serial_unique');
            });
        }

        if (! Schema::hasTable('tr069_profiles')) {
            Schema::create('tr069_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('wan_mode', 30)->default('dhcp');
                $table->string('pppoe_username')->nullable();
                $table->string('pppoe_password')->nullable();
                $table->string('static_ip')->nullable();
                $table->string('static_gateway')->nullable();
                $table->string('static_dns')->nullable();
                $table->unsignedInteger('vlan_id')->nullable();
                $table->string('wifi_ssid')->nullable();
                $table->string('wifi_password')->nullable();
                $table->boolean('wifi_enabled')->default(true);
                $table->json('tr069_parameters')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('tr069_config_jobs')) {
            Schema::create('tr069_config_jobs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('cpe_device_id')->index();
                $table->unsignedBigInteger('tr069_profile_id')->nullable()->index();
                $table->string('job_type', 40)->index();
                $table->json('payload')->nullable();
                $table->string('status', 30)->default('queued')->index();
                $table->text('result_message')->nullable();
                $table->timestamp('queued_at')->nullable()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tr069_firmware_updates')) {
            Schema::create('tr069_firmware_updates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name');
                $table->string('version');
                $table->string('manufacturer')->nullable();
                $table->string('model')->nullable();
                $table->string('file_url')->nullable();
                $table->string('checksum')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tr069_device_logs')) {
            Schema::create('tr069_device_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('cpe_device_id')->nullable()->index();
                $table->string('event_type', 40)->index();
                $table->string('level', 30)->default('info')->index();
                $table->text('message');
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tr069_device_logs');
        Schema::dropIfExists('tr069_firmware_updates');
        Schema::dropIfExists('tr069_config_jobs');
        Schema::dropIfExists('tr069_profiles');
        Schema::dropIfExists('tr069_cpe_devices');
        Schema::dropIfExists('tr069_settings');
    }
};
