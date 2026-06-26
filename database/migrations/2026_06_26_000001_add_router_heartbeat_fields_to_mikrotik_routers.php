<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            if (! Schema::hasColumn('mikrotik_routers', 'routeros_version')) {
                $table->string('routeros_version')->nullable()->after('identity');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'board_name')) {
                $table->string('board_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'cpu_load')) {
                $table->unsignedTinyInteger('cpu_load')->nullable()->after('uptime');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'memory_free')) {
                $table->unsignedBigInteger('memory_free')->nullable()->after('cpu_load');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'memory_total')) {
                $table->unsignedBigInteger('memory_total')->nullable()->after('memory_free');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'uptime')) {
                $table->string('uptime')->nullable()->after('architecture');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'router_time')) {
                $table->string('router_time')->nullable()->after('uptime');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'heartbeat_token')) {
                $table->string('heartbeat_token')->nullable()->unique()->after('provision_token');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'hotspot_files_status')) {
                $table->string('hotspot_files_status')->nullable()->after('pppoe_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'remote_winbox_status')) {
                $table->string('remote_winbox_status')->nullable()->after('winbox_password');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'remote_winbox_error')) {
                $table->text('remote_winbox_error')->nullable()->after('remote_winbox_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'last_heartbeat_payload')) {
                $table->json('last_heartbeat_payload')->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_connected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            foreach ([
                'cpu_load',
                'memory_free',
                'memory_total',
                'router_time',
                'heartbeat_token',
                'hotspot_files_status',
                'remote_winbox_status',
                'remote_winbox_error',
                'last_heartbeat_payload',
            ] as $column) {
                if (Schema::hasColumn('mikrotik_routers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
