<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIAgentChatSession;
use App\Models\User;

class AIAgentChatSessionSeeder extends Seeder
{
    public function run($userId = null): void
    {
        if (!$userId) {
            return;
        }

        // Clear existing demo sessions
        AIAgentChatSession::where('user_id', $userId)->delete();

        $sessions = [
            [
                'user_id' => $userId,
                'creator_id' => $userId,
                'created_by' => $userId,
                'title' => 'Monthly Sales Report',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'user_id' => $userId,
                'creator_id' => $userId,
                'created_by' => $userId,
                'title' => 'Active Leads Overview',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'user_id' => $userId,
                'creator_id' => $userId,
                'created_by' => $userId,
                'title' => 'Project Status Update',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => $userId,
                'creator_id' => $userId,
                'created_by' => $userId,
                'title' => 'Invoice Summary',
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
            [
                'user_id' => $userId,
                'creator_id' => $userId,
                'created_by' => $userId,
                'title' => 'Customer Proposals',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
        ];

        foreach ($sessions as $session) {
            AIAgentChatSession::create($session);
        }
    }
}
