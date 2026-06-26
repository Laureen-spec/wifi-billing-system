<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('internet_packages', 'enable_burst')) {
                $table->boolean('enable_burst')->default(false)->after('status');
            }
            if (! Schema::hasColumn('internet_packages', 'burst_download')) {
                $table->unsignedInteger('burst_download')->nullable()->after('enable_burst');
            }
            if (! Schema::hasColumn('internet_packages', 'burst_upload')) {
                $table->unsignedInteger('burst_upload')->nullable()->after('burst_download');
            }
            if (! Schema::hasColumn('internet_packages', 'burst_threshold')) {
                $table->string('burst_threshold')->nullable()->after('burst_upload');
            }
            if (! Schema::hasColumn('internet_packages', 'burst_time')) {
                $table->string('burst_time')->nullable()->after('burst_threshold');
            }
            if (! Schema::hasColumn('internet_packages', 'priority')) {
                $table->unsignedTinyInteger('priority')->nullable()->after('burst_time');
            }
            if (! Schema::hasColumn('internet_packages', 'limit_at')) {
                $table->string('limit_at')->nullable()->after('priority');
            }
            if (! Schema::hasColumn('internet_packages', 'enable_schedule')) {
                $table->boolean('enable_schedule')->default(false)->after('limit_at');
            }
            if (! Schema::hasColumn('internet_packages', 'schedule_start')) {
                $table->time('schedule_start')->nullable()->after('enable_schedule');
            }
            if (! Schema::hasColumn('internet_packages', 'schedule_end')) {
                $table->time('schedule_end')->nullable()->after('schedule_start');
            }
            if (! Schema::hasColumn('internet_packages', 'schedule_days')) {
                $table->json('schedule_days')->nullable()->after('schedule_end');
            }
            if (! Schema::hasColumn('internet_packages', 'schedule_recurring')) {
                $table->boolean('schedule_recurring')->default(true)->after('schedule_days');
            }
            if (! Schema::hasColumn('internet_packages', 'available_on_all_mikrotik')) {
                $table->boolean('available_on_all_mikrotik')->default(true)->after('schedule_recurring');
            }
            if (! Schema::hasColumn('internet_packages', 'hidden_from_client')) {
                $table->boolean('hidden_from_client')->default(false)->after('available_on_all_mikrotik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            foreach ([
                'enable_burst',
                'burst_download',
                'burst_upload',
                'burst_threshold',
                'burst_time',
                'priority',
                'limit_at',
                'enable_schedule',
                'schedule_start',
                'schedule_end',
                'schedule_days',
                'schedule_recurring',
                'available_on_all_mikrotik',
                'hidden_from_client',
            ] as $column) {
                if (Schema::hasColumn('internet_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
