<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            if (! Schema::hasColumn('mikrotik_routers', 'board_name')) {
                $table->string('board_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'identity')) {
                $table->string('identity')->nullable()->after('board_name');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'routeros_version')) {
                $table->string('routeros_version')->nullable()->after('identity');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'architecture')) {
                $table->string('architecture')->nullable()->after('routeros_version');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'uptime')) {
                $table->string('uptime')->nullable()->after('architecture');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'cpu_usage')) {
                $table->unsignedTinyInteger('cpu_usage')->nullable()->after('uptime');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'memory_usage')) {
                $table->decimal('memory_usage', 10, 2)->nullable()->after('cpu_usage');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_connected_at');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'winbox_endpoint')) {
                $table->string('winbox_endpoint')->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'winbox_port')) {
                $table->unsignedInteger('winbox_port')->default(8291)->after('winbox_endpoint');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'winbox_username')) {
                $table->string('winbox_username')->nullable()->after('winbox_port');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'winbox_password')) {
                $table->text('winbox_password')->nullable()->after('winbox_username');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'hotspot_status')) {
                $table->string('hotspot_status')->default('pending')->after('winbox_password');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'pppoe_status')) {
                $table->string('pppoe_status')->default('pending')->after('hotspot_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'sync_status')) {
                $table->string('sync_status')->default('pending')->after('pppoe_status');
            }
            if (! Schema::hasColumn('mikrotik_routers', 'time_sync_status')) {
                $table->string('time_sync_status')->default('pending')->after('sync_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            foreach ([
                'board_name',
                'identity',
                'routeros_version',
                'architecture',
                'uptime',
                'cpu_usage',
                'memory_usage',
                'last_seen_at',
                'winbox_endpoint',
                'winbox_port',
                'winbox_username',
                'winbox_password',
                'hotspot_status',
                'pppoe_status',
                'sync_status',
                'time_sync_status',
            ] as $column) {
                if (Schema::hasColumn('mikrotik_routers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
