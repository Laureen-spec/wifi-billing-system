<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_whatsapp_settings')) {
            Schema::create('isp_whatsapp_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->string('scope')->default('isp')->index();
                $table->string('provider_mode')->default('platform')->index();
                $table->string('provider')->default('platform')->index();
                $table->string('business_phone')->nullable();
                $table->string('phone_number_id')->nullable();
                $table->string('waba_id')->nullable();
                $table->string('sender_name')->nullable();
                $table->string('api_base_url', 500)->nullable();
                $table->text('credentials')->nullable();
                $table->string('webhook_verify_token')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('allow_platform_api')->default(true);
                $table->boolean('allow_own_api')->default(true);
                $table->unsignedInteger('reply_window_minutes')->default(120);
                $table->decimal('whatsapp_balance', 12, 2)->default(0);
                $table->decimal('estimated_cost_per_message', 12, 2)->default(1);
                $table->decimal('low_balance_threshold', 12, 2)->default(10);
                $table->boolean('billing_enabled')->default(true);
                $table->string('billing_status')->default('active')->index();
                $table->string('topup_payment_status')->nullable()->index();
                $table->unsignedInteger('messages_sent')->default(0);
                $table->unsignedInteger('messages_failed')->default(0);
                $table->timestamp('last_billed_at')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status')->nullable();
                $table->text('last_test_message')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();

                $table->unique(['scope', 'isp_id'], 'isp_whatsapp_settings_scope_isp_unique');
            });
        }

        if (! Schema::hasTable('isp_whatsapp_templates')) {
            Schema::create('isp_whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->string('name');
                $table->string('key')->index();
                $table->string('category')->default('support')->index();
                $table->string('provider_template_name')->nullable();
                $table->string('language', 20)->default('en');
                $table->text('body');
                $table->json('variables')->nullable();
                $table->string('status')->default('approved')->index();
                $table->boolean('enabled')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();

                $table->unique(['isp_id', 'key'], 'isp_whatsapp_templates_isp_key_unique');
            });
        }

        if (! Schema::hasTable('isp_whatsapp_conversations')) {
            Schema::create('isp_whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->string('customer_name')->nullable();
                $table->string('status')->default('open')->index();
                $table->boolean('opted_out')->default(false)->index();
                $table->boolean('blocked')->default(false)->index();
                $table->timestamp('last_customer_message_at')->nullable();
                $table->timestamp('handover_at')->nullable();
                $table->timestamp('reply_window_expires_at')->nullable()->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('source')->default('whatsapp')->index();
                $table->text('summary')->nullable();
                $table->json('tags')->nullable();
                $table->text('last_message_preview')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();

                $table->index(['isp_id', 'phone']);
            });
        }

        if (! Schema::hasTable('isp_whatsapp_messages')) {
            Schema::create('isp_whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->string('direction')->default('outbound')->index();
                $table->string('message_type')->default('text')->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('provider_mode')->default('platform')->index();
                $table->string('provider')->nullable()->index();
                $table->text('body')->nullable();
                $table->json('payload')->nullable();
                $table->decimal('cost', 12, 2)->default(0);
                $table->string('status')->default('queued')->index();
                $table->text('error_message')->nullable();
                $table->string('provider_message_id')->nullable()->index();
                $table->unsignedBigInteger('sent_by')->nullable()->index();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'status']);
                $table->index(['isp_id', 'direction', 'created_at'], 'isp_whatsapp_messages_scope_time_index');
            });
        }

        if (! Schema::hasTable('isp_whatsapp_usage_logs')) {
            Schema::create('isp_whatsapp_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('message_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->string('message_type')->default('text')->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('provider_mode')->default('platform')->index();
                $table->string('provider')->nullable()->index();
                $table->decimal('cost', 12, 2)->default(0);
                $table->string('status')->default('queued')->index();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_whatsapp_payment_requests')) {
            Schema::create('isp_whatsapp_payment_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('internet_package_id')->nullable()->index();
                $table->unsignedBigInteger('mpesa_transaction_id')->nullable()->index();
                $table->string('payment_center_record_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 10)->default('KES');
                $table->string('method')->default('mpesa')->index();
                $table->string('status')->default('pending')->index();
                $table->string('checkout_request_id')->nullable()->index();
                $table->string('receipt_code')->nullable()->index();
                $table->string('payment_link', 500)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_whatsapp_receipts')) {
            Schema::create('isp_whatsapp_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('payment_request_id')->nullable()->index();
                $table->string('phone', 40)->index();
                $table->string('receipt_code')->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 10)->default('KES');
                $table->string('status')->default('issued')->index();
                $table->timestamp('sent_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_whatsapp_support_tickets')) {
            Schema::create('isp_whatsapp_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('ticket_number')->unique();
                $table->string('phone', 40)->index();
                $table->string('subject')->default('WhatsApp support request');
                $table->string('status')->default('open')->index();
                $table->string('priority')->default('normal')->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_whatsapp_broadcasts')) {
            Schema::create('isp_whatsapp_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('name');
                $table->string('audience')->default('specific')->index();
                $table->json('filters')->nullable();
                $table->unsignedInteger('recipient_count')->default(0);
                $table->string('status')->default('draft')->index();
                $table->boolean('requires_confirmation')->default(true);
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedBigInteger('confirmed_by')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('isp_whatsapp_topups')) {
            Schema::create('isp_whatsapp_topups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 10)->default('KES');
                $table->string('status')->default('pending')->index();
                $table->string('payment_reference')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_whatsapp_topups');
        Schema::dropIfExists('isp_whatsapp_broadcasts');
        Schema::dropIfExists('isp_whatsapp_support_tickets');
        Schema::dropIfExists('isp_whatsapp_receipts');
        Schema::dropIfExists('isp_whatsapp_payment_requests');
        Schema::dropIfExists('isp_whatsapp_usage_logs');
        Schema::dropIfExists('isp_whatsapp_messages');
        Schema::dropIfExists('isp_whatsapp_conversations');
        Schema::dropIfExists('isp_whatsapp_templates');
        Schema::dropIfExists('isp_whatsapp_settings');
    }
};
