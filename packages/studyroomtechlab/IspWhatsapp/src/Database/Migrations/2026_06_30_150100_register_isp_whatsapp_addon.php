<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('add_ons')) {
            $addon = [
                'module' => 'IspWhatsapp',
                'name' => 'WhatsApp Desk',
                'alias' => 'WhatsApp Desk',
                'description' => 'WhatsApp inbox, templates, bot flows, API settings, message usage, billing, and payment self-service.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => 1,
                'status' => 1,
                'for_admin' => 0,
                'package_name' => 'isp-whatsapp',
                'parent_module' => json_encode(['WifiBilling']),
                'display' => 1,
                'priority' => 54,
                'version' => 1.0,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('add_ons', 'created_at')) {
                $addon['created_at'] = $now;
            }

            $query = DB::table('add_ons')->where(function ($query) {
                $query->where('module', 'IspWhatsapp')
                    ->orWhere('package_name', 'isp-whatsapp')
                    ->orWhere('name', 'WhatsApp Desk');
            });

            if ((clone $query)->exists()) {
                unset($addon['created_at']);
                $query->update($this->onlyExistingColumns('add_ons', $addon));
            } else {
                DB::table('add_ons')->insert($this->onlyExistingColumns('add_ons', $addon));
            }
        }

        if (Schema::hasTable('menu_visibility_settings')) {
            $metadata = [
                'label' => 'WhatsApp Desk',
                'menu_group' => 'WiFi Billing',
                'parent_key' => 'wifi-billing',
                'route_name' => 'isp.whatsapp.index',
                'url' => '/isp/whatsapp',
                'aliases' => json_encode([
                    'isp-whatsapp',
                    'IspWhatsapp',
                    'WhatsApp Desk',
                    'whatsapp-desk',
                    'isp.whatsapp.index',
                    'isp.whatsapp.inbox',
                    'isp.whatsapp.bot-flows',
                    'isp.whatsapp.payment-requests',
                    'isp.whatsapp.receipts',
                    'isp.whatsapp.support-tickets',
                    'isp.whatsapp.broadcasts',
                    'isp.whatsapp.templates',
                    'isp.whatsapp.usage',
                    'isp.whatsapp.api-settings',
                    'isp.whatsapp.logs',
                    'isp.whatsapp.settings',
                    '/isp/whatsapp',
                    '/isp/whatsapp/inbox',
                    'whatsapp',
                ]),
                'sort_order' => 54,
                'is_system' => 1,
                'updated_at' => $now,
            ];

            $existing = DB::table('menu_visibility_settings')
                ->where('menu_key', 'isp-whatsapp')
                ->first();

            if ($existing) {
                DB::table('menu_visibility_settings')
                    ->where('menu_key', 'isp-whatsapp')
                    ->update($this->onlyExistingColumns('menu_visibility_settings', $metadata));
            } else {
                $defaults = [
                    'menu_key' => 'isp-whatsapp',
                    'visible_to_superadmin' => 1,
                    'visible_to_admin' => 1,
                    'visible_to_isp_admin' => 1,
                    'block_route_access' => 0,
                    'created_at' => $now,
                ];

                DB::table('menu_visibility_settings')->insert(
                    $this->onlyExistingColumns('menu_visibility_settings', array_merge($defaults, $metadata))
                );
            }
        }

        if (Schema::hasTable('isp_whatsapp_templates')) {
            foreach ($this->defaultTemplates() as $template) {
                DB::table('isp_whatsapp_templates')->updateOrInsert(
                    ['isp_id' => null, 'key' => $template['key']],
                    array_merge($template, [
                        'isp_id' => null,
                        'status' => 'approved',
                        'enabled' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }
    }

    public function down(): void
    {
        // Data-only safety migration. Do not remove add-on metadata or templates on rollback.
    }

    private function defaultTemplates(): array
    {
        return [
            [
                'name' => 'Payment request',
                'key' => 'payment_request',
                'category' => 'payment',
                'provider_template_name' => 'payment_request',
                'language' => 'en',
                'body' => 'Hello {{customer_name}}, pay {{currency}} {{amount}} for {{plan_name}} using {{payment_link}}. Receipt code will be sent after confirmation.',
                'variables' => json_encode(['customer_name', 'currency', 'amount', 'plan_name', 'payment_link']),
            ],
            [
                'name' => 'Renewal reminder',
                'key' => 'renewal_reminder',
                'category' => 'renewal',
                'provider_template_name' => 'renewal_reminder',
                'language' => 'en',
                'body' => 'Hello {{customer_name}}, your {{plan_name}} expires on {{expiry_time}}. Renew using {{payment_link}} or contact {{support_phone}}.',
                'variables' => json_encode(['customer_name', 'plan_name', 'expiry_time', 'payment_link', 'support_phone']),
            ],
            [
                'name' => 'Support follow up',
                'key' => 'support_follow_up',
                'category' => 'support',
                'provider_template_name' => 'support_follow_up',
                'language' => 'en',
                'body' => 'Hello {{customer_name}}, support ticket {{ticket_number}} is being reviewed. Reply here or call {{support_phone}}.',
                'variables' => json_encode(['customer_name', 'ticket_number', 'support_phone']),
            ],
            [
                'name' => 'Payment receipt',
                'key' => 'payment_receipt',
                'category' => 'receipt',
                'provider_template_name' => 'payment_receipt',
                'language' => 'en',
                'body' => 'Receipt {{receipt_code}} confirmed for {{customer_name}}. Amount: {{currency}} {{amount}}. Plan: {{plan_name}}.',
                'variables' => json_encode(['receipt_code', 'customer_name', 'currency', 'amount', 'plan_name']),
            ],
            [
                'name' => 'System handover',
                'key' => 'system_handover',
                'category' => 'system',
                'provider_template_name' => 'system_handover',
                'language' => 'en',
                'body' => 'A support admin is reviewing your request, {{customer_name}}. Ticket: {{ticket_number}}.',
                'variables' => json_encode(['customer_name', 'ticket_number']),
            ],
            [
                'name' => 'Broadcast update',
                'key' => 'broadcast_update',
                'category' => 'broadcast',
                'provider_template_name' => 'broadcast_update',
                'language' => 'en',
                'body' => 'Hello {{customer_name}}, service update from support: {{support_phone}}.',
                'variables' => json_encode(['customer_name', 'support_phone']),
            ],
        ];
    }

    private function onlyExistingColumns(string $table, array $values): array
    {
        return array_filter(
            $values,
            fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }
};
