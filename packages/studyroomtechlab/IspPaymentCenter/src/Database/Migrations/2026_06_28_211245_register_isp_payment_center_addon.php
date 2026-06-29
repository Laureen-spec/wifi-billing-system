<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $query = DB::table('add_ons');

        if (Schema::hasColumn('add_ons', 'module')) {
            $query->where('module', 'IspPaymentCenter');
        } elseif (Schema::hasColumn('add_ons', 'name')) {
            $query->where('name', 'Payment Center');
        } else {
            return;
        }

        if ($query->exists()) {
            return;
        }

        $record = [];

        $this->setIfColumnExists($record, 'module', 'IspPaymentCenter');
        $this->setIfColumnExists($record, 'name', 'Payment Center');
        $this->setIfColumnExists($record, 'alias', 'Payment Center');
        $this->setIfColumnExists($record, 'description', 'Payment collections, transaction review, reconciliation, and settlement workspace.');
        $this->setIfColumnExists($record, 'is_enable', 1);
        $this->setIfColumnExists($record, 'status', 1);
        $this->setIfColumnExists($record, 'priority', 52);
        $this->setIfColumnExists($record, 'version', 1.0);
        $this->setIfColumnExists($record, 'monthly_price', 0);
        $this->setIfColumnExists($record, 'yearly_price', 0);
        $this->setIfColumnExists($record, 'parent_module', json_encode(['WifiBilling']));
        $this->setIfColumnExists($record, 'package_name', 'isp-payment-center');
        $this->setIfColumnExists($record, 'display', 1);
        $this->setIfColumnExists($record, 'for_admin', 0);
        $this->setIfColumnExists($record, 'created_at', now());
        $this->setIfColumnExists($record, 'updated_at', now());

        if ($record !== []) {
            DB::table('add_ons')->insert($record);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        if (Schema::hasColumn('add_ons', 'module')) {
            DB::table('add_ons')->where('module', 'IspPaymentCenter')->delete();
        }
    }

    private function setIfColumnExists(array &$record, string $column, mixed $value): void
    {
        if (Schema::hasColumn('add_ons', $column)) {
            $record[$column] = $value;
        }
    }
};
