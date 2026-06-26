<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_customers')) {
            return;
        }

        Schema::create('isp_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('isps')->cascadeOnDelete();
            $table->foreignId('internet_package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->string('connection_status')->default('pending');
            $table->string('billing_status')->default('unpaid');
            $table->decimal('monthly_amount', 10, 2)->default(0);
            $table->date('installation_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_customers');
    }
};
