<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        Schema::create('menu_visibility_settings', function (Blueprint $table) {
            $table->id();
            $table->string('menu_key')->unique();
            $table->string('label');
            $table->string('menu_group')->nullable()->index();
            $table->string('parent_key')->nullable()->index();
            $table->string('route_name')->nullable()->index();
            $table->string('url')->nullable();
            $table->json('aliases')->nullable();
            $table->unsignedInteger('sort_order')->default(999)->index();
            $table->boolean('visible_to_superadmin')->default(true);
            $table->boolean('visible_to_admin')->default(true);
            $table->boolean('visible_to_isp_admin')->default(true);
            $table->boolean('block_route_access')->default(false);
            $table->boolean('is_system')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_visibility_settings');
    }
};
