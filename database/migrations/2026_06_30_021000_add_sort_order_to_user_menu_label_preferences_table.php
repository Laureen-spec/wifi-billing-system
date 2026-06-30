<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_menu_label_preferences')) {
            return;
        }

        Schema::table('user_menu_label_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('user_menu_label_preferences', 'sort_order')) {
                $table->unsignedInteger('sort_order')->nullable()->after('custom_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_menu_label_preferences') || ! Schema::hasColumn('user_menu_label_preferences', 'sort_order')) {
            return;
        }

        Schema::table('user_menu_label_preferences', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
