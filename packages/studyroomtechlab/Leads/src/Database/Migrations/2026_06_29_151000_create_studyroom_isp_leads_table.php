<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_leads')) {
            Schema::create('isp_leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('isp_id')->nullable()->index();
                $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
                $table->unsignedBigInteger('converted_customer_id')->nullable()->index();
                $table->string('name');
                $table->string('phone', 40)->nullable()->index();
                $table->string('email')->nullable();
                $table->string('location')->nullable();
                $table->string('source')->default('walk-in')->index();
                $table->string('interest')->nullable();
                $table->string('status')->default('new')->index();
                $table->string('priority')->default('warm')->index();
                $table->decimal('value_estimate', 12, 2)->nullable();
                $table->timestamp('next_follow_up_at')->nullable()->index();
                $table->timestamp('last_contact_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('converted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('isp_leads', function (Blueprint $table) {
            $this->addColumnIfMissing($table, 'isp_id', fn (Blueprint $table) => $table->unsignedBigInteger('isp_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'assigned_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('assigned_user_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'converted_customer_id', fn (Blueprint $table) => $table->unsignedBigInteger('converted_customer_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'name', fn (Blueprint $table) => $table->string('name')->nullable());
            $this->addColumnIfMissing($table, 'phone', fn (Blueprint $table) => $table->string('phone', 40)->nullable()->index());
            $this->addColumnIfMissing($table, 'email', fn (Blueprint $table) => $table->string('email')->nullable());
            $this->addColumnIfMissing($table, 'location', fn (Blueprint $table) => $table->string('location')->nullable());
            $this->addColumnIfMissing($table, 'source', fn (Blueprint $table) => $table->string('source')->default('walk-in')->index());
            $this->addColumnIfMissing($table, 'interest', fn (Blueprint $table) => $table->string('interest')->nullable());
            $this->addColumnIfMissing($table, 'status', fn (Blueprint $table) => $table->string('status')->default('new')->index());
            $this->addColumnIfMissing($table, 'priority', fn (Blueprint $table) => $table->string('priority')->default('warm')->index());
            $this->addColumnIfMissing($table, 'value_estimate', fn (Blueprint $table) => $table->decimal('value_estimate', 12, 2)->nullable());
            $this->addColumnIfMissing($table, 'next_follow_up_at', fn (Blueprint $table) => $table->timestamp('next_follow_up_at')->nullable()->index());
            $this->addColumnIfMissing($table, 'last_contact_at', fn (Blueprint $table) => $table->timestamp('last_contact_at')->nullable());
            $this->addColumnIfMissing($table, 'notes', fn (Blueprint $table) => $table->text('notes')->nullable());
            $this->addColumnIfMissing($table, 'converted_at', fn (Blueprint $table) => $table->timestamp('converted_at')->nullable());
            $this->addColumnIfMissing($table, 'created_by', fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable()->index());
            $this->addColumnIfMissing($table, 'updated_by', fn (Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable()->index());
        });
    }

    public function down(): void
    {
        // Keep lead records for safety. Disable/remove the add-on row separately if needed.
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $callback): void
    {
        if (! Schema::hasColumn('isp_leads', $column)) {
            $callback($table);
        }
    }
};
