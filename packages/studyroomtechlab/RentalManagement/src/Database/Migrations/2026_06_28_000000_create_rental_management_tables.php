<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rental_properties')) {
            Schema::create('rental_properties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('name');
                $table->string('type')->default('apartment');
                $table->string('location')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_phone')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rental_units')) {
            Schema::create('rental_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->index();
                $table->string('unit_number');
                $table->string('unit_type')->default('bedsitter');
                $table->decimal('rent_amount', 12, 2)->default(0);
                $table->decimal('deposit_amount', 12, 2)->default(0);
                $table->unsignedInteger('capacity')->default(1);
                $table->string('status')->default('vacant')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rental_tenants')) {
            Schema::create('rental_tenants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->unsignedBigInteger('unit_id')->nullable()->index();
                $table->string('name');
                $table->string('phone')->nullable()->index();
                $table->string('email')->nullable();
                $table->string('guardian_phone')->nullable();
                $table->date('move_in_date')->nullable();
                $table->date('move_out_date')->nullable();
                $table->decimal('rent_balance', 12, 2)->default(0);
                $table->decimal('water_balance', 12, 2)->default(0);
                $table->decimal('internet_balance', 12, 2)->default(0);
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('rental_invoices')) {
            Schema::create('rental_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->unsignedBigInteger('unit_id')->nullable()->index();
                $table->string('invoice_number')->unique();
                $table->string('invoice_type')->default('rent');
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->date('billing_month')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status')->default('unpaid')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rental_payments')) {
            Schema::create('rental_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method')->default('cash');
                $table->string('reference')->nullable()->index();
                $table->dateTime('paid_at')->nullable();
                $table->string('status')->default('paid')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rental_expenses')) {
            Schema::create('rental_expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->string('name');
                $table->string('category')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->date('expense_date')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expenses');
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rental_invoices');
        Schema::dropIfExists('rental_tenants');
        Schema::dropIfExists('rental_units');
        Schema::dropIfExists('rental_properties');
    }
};
