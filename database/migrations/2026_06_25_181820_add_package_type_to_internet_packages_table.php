<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('internet_packages', 'package_type')) {
                $table->string('package_type')->default('hotspot')->after('name');
            }
        });

        DB::table('internet_packages')
            ->whereNull('package_type')
            ->update(['package_type' => 'hotspot']);

        DB::table('internet_packages')
            ->where('price', 0)
            ->update(['package_type' => 'free_trial']);

        DB::table('internet_packages')
            ->whereRaw('LOWER(name) LIKE ?', ['%pppoe%'])
            ->update(['package_type' => 'pppoe']);

        DB::table('internet_packages')
            ->whereRaw('LOWER(name) LIKE ?', ['%bundle%'])
            ->update(['package_type' => 'data_bundle']);
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            if (Schema::hasColumn('internet_packages', 'package_type')) {
                $table->dropColumn('package_type');
            }
        });
    }
};