<?php

namespace Workdo\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Workdo\Pos\Models\PosBillingCounter;

class DemoPosBillingCounterSeeder extends Seeder
{
    public function run($userId): void
    {
        if (PosBillingCounter::where('created_by', $userId)->exists()) {
            return;
        }

        $counters = [
            [
                'name' => 'Billing Counter 1',
                'code' => 'CNT-001',
                'status' => true,
                'description' => 'Main Billing Counter for ground floor',
            ],
            [
                'name' => 'Billing Counter 2',
                'code' => 'CNT-002',
                'status' => true,
                'description' => 'Second Billing Counter for ground floor',
            ],
            [
                'name' => 'Billing Counter 3',
                'code' => 'CNT-003',
                'status' => true,
                'description' => 'First floor Billing Counter',
            ],
            [
                'name' => 'Table 1',
                'code' => 'TBL-001',
                'status' => true,
                'description' => 'Table 1 - Window side',
            ],
            [
                'name' => 'Table 2',
                'code' => 'TBL-002',
                'status' => false,
                'description' => 'Table 2 - Center area',
            ],
            [
                'name' => 'Table 3',
                'code' => 'TBL-003',
                'status' => true,
                'description' => 'Table 3 - Corner area',
            ],
            [
                'name' => 'Drive Through',
                'code' => 'DRV-001',
                'status' => true,
                'description' => 'Drive through Billing Counter',
            ],
            [
                'name' => 'Online Orders',
                'code' => 'ONL-001',
                'status' => false,
                'description' => 'Online order processing Billing Counter',
            ],
        ];

        foreach ($counters as $counter) {
            PosBillingCounter::create([
                'name' => $counter['name'],
                'code' => $counter['code'],
                'status' => $counter['status'],
                'description' => $counter['description'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
