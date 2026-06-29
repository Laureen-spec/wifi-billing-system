<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_sms_settings')) {
            Schema::create('isp_sms_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->string('scope')->default('isp')->index();
                $table->string('mode')->default('platform');
                $table->string('provider')->nullable();
                $table->string('sender_id')->nullable();
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->string('username')->nullable();
                $table->string('callback_url', 500)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_sms_templates')) {
            Schema::create('isp_sms_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->string('name');
                $table->string('key')->index();
                $table->text('body');
                $table->boolean('enabled')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->unique(['isp_id', 'key'], 'isp_sms_templates_isp_key_unique');
            });
        }

        if (! Schema::hasTable('isp_sms_messages')) {
            Schema::create('isp_sms_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->text('message');
                $table->string('channel')->default('sms');
                $table->string('direction')->default('outbound')->index();
                $table->string('sending_mode')->default('platform')->index();
                $table->string('provider')->nullable()->index();
                $table->string('status')->default('queued')->index();
                $table->string('provider_message_id')->nullable()->index();
                $table->json('provider_response')->nullable();
                $table->text('result_message')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable()->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_sms_messages');
        Schema::dropIfExists('isp_sms_templates');
        Schema::dropIfExists('isp_sms_settings');
    }
};
