<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('repair_pos_customers')) {
            Schema::create('repair_pos_customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable()->index();
                $table->string('email')->nullable();
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('repair_pos_jobs')) {
            Schema::create('repair_pos_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->string('phone')->nullable()->index();
                $table->string('device_type')->default('phone');
                $table->string('device_model')->nullable();
                $table->string('serial_number')->nullable();
                $table->text('issue')->nullable();
                $table->text('diagnosis')->nullable();
                $table->decimal('estimated_cost', 12, 2)->default(0);
                $table->decimal('final_cost', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('received')->index();
                $table->date('expected_pickup_date')->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('repair_pos_products')) {
            Schema::create('repair_pos_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable()->unique();
                $table->string('category')->nullable()->index();
                $table->string('brand')->nullable();
                $table->string('supplier')->nullable();
                $table->decimal('purchase_price', 12, 2)->default(0);
                $table->decimal('selling_price', 12, 2)->default(0);
                $table->unsignedInteger('stock')->default(0);
                $table->unsignedInteger('low_stock_alert')->default(2);
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('repair_pos_sales')) {
            Schema::create('repair_pos_sales', function (Blueprint $table) {
                $table->id();
                $table->string('sale_number')->unique();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('repair_job_id')->nullable()->index();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->string('payment_method')->default('cash');
                $table->string('payment_reference')->nullable();
                $table->string('status')->default('paid')->index();
                $table->dateTime('sold_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('repair_pos_sale_items')) {
            Schema::create('repair_pos_sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('item_name');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_pos_sale_items');
        Schema::dropIfExists('repair_pos_sales');
        Schema::dropIfExists('repair_pos_products');
        Schema::dropIfExists('repair_pos_jobs');
        Schema::dropIfExists('repair_pos_customers');
    }
};
