<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internet_package_mikrotik_router')) {
            return;
        }

        Schema::create('internet_package_mikrotik_router', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internet_package_id')->constrained('internet_packages')->cascadeOnDelete();
            $table->foreignId('mikrotik_router_id')->constrained('mikrotik_routers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['internet_package_id', 'mikrotik_router_id'], 'pkg_router_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internet_package_mikrotik_router');
    }
};
