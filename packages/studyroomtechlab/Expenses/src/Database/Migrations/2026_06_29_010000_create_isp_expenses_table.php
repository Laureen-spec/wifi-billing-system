<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isp_expenses')) {
            return;
        }

        Schema::create('isp_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('isps')->cascadeOnDelete();
            $table->string('expense_number', 40)->unique();
            $table->string('category', 60)->default('other')->index();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method', 40)->default('cash')->index();
            $table->string('receipt_number', 120)->nullable();
            $table->date('expense_date')->index();
            $table->string('status', 40)->default('paid')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['isp_id', 'expense_date']);
            $table->index(['isp_id', 'category']);
            $table->unique(['isp_id', 'receipt_number'], 'isp_expenses_isp_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_expenses');
    }
};
