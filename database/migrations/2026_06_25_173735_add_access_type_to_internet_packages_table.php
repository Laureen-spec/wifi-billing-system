<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('internet_packages', 'access_type')) {
                $table->string('access_type')->default('hotspot')->after('name');
            }
        });

        DB::table('internet_packages')
            ->whereNull('access_type')
            ->update(['access_type' => 'hotspot']);

        DB::table('internet_packages')
            ->whereRaw('LOWER(name) LIKE ?', ['%pppoe%'])
            ->update(['access_type' => 'pppoe']);
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (Schema::hasColumn('internet_packages', 'access_type')) {
                $table->dropColumn('access_type');
            }
        });
    }
};