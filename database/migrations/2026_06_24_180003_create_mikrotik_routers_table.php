<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('isps')->cascadeOnDelete();
            $table->string('name');
            $table->string('host');
            $table->unsignedInteger('api_port')->default(8728);
            $table->string('username');
            $table->text('password')->nullable();
            $table->string('provision_token')->nullable()->unique();
            $table->string('provision_status')->default('pending');
            $table->timestamp('provisioned_at')->nullable();
            $table->string('connection_type')->default('api');
            $table->string('status')->default('inactive');
            $table->timestamp('last_connected_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_routers');
    }
};
