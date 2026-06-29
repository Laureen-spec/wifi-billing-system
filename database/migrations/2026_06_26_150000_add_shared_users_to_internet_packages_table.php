<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('internet_packages', 'shared_users')) {
                $table->unsignedInteger('shared_users')->default(1)->after('hidden_from_client');
            }
        });
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (Schema::hasColumn('internet_packages', 'shared_users')) {
                $table->dropColumn('shared_users');
            }
        });
    }
};
