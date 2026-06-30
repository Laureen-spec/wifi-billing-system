<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_menu_label_preferences')) {
            return;
        }

        Schema::create('user_menu_label_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('menu_key', 160);
            $table->string('default_label')->nullable();
            $table->string('custom_label', 80);
            $table->timestamps();

            $table->unique(['user_id', 'menu_key'], 'user_menu_label_preferences_unique');
            $table->index(['user_id', 'menu_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_menu_label_preferences');
    }
};
