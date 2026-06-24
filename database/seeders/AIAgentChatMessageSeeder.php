<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIAgentChatMessage;
use App\Models\AIAgentChatSession;

class AIAgentChatMessageSeeder extends Seeder
{
    public function run($userId = null): void
    {
        $sessions = AIAgentChatSession::orderBy('created_at')->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $conversations = [
            // Session 1: Monthly Sales Report
            [
                ['role' => 'user', 'content' => 'Show me the total sales for this month'],
                ['role' => 'assistant', 'content' => 'I found the sales data for this month. Your total sales amount is $45,230.75 across 28 invoices.'],
                ['role' => 'user', 'content' => 'How does that compare to last month?'],
                ['role' => 'assistant', 'content' => 'Last month your total sales were $38,450.50. This represents a 17.6% increase, which is excellent growth!'],
            ],
            // Session 2: Active Leads Overview
            [
                ['role' => 'user', 'content' => 'Show me all active leads'],
                ['role' => 'assistant', 'content' => "I found 15 active leads:\n• John Smith | john@example.com | +1234567890 | Active\n• Sarah Johnson | sarah@example.com | +9876543210 | Active\n• Mike Davis | mike@example.com | +5555555555 | Active\n• Emily Brown | emily@example.com | +4444444444 | Active\n• David Wilson | david@example.com | +3333333333 | Active\n(showing first 5 of 15)"],
                ['role' => 'user', 'content' => 'Which ones are from this week?'],
                ['role' => 'assistant', 'content' => 'From this week, you have 6 new leads: John Smith, Sarah Johnson, Mike Davis, Emily Brown, David Wilson, and Lisa Anderson.'],
            ],
            // Session 3: Project Status Update
            [
                ['role' => 'user', 'content' => 'List all ongoing projects'],
                ['role' => 'assistant', 'content' => "I found 4 ongoing projects:\n• Website Redesign | Ongoing | $15,000.00 | Jan 1 - Mar 31\n• Mobile App Development | Ongoing | $25,000.00 | Feb 1 - Jun 30\n• CRM Integration | Ongoing | $8,500.00 | Jan 15 - Apr 15\n• Marketing Campaign | Ongoing | $12,000.00 | Feb 15 - May 15"],
                ['role' => 'user', 'content' => 'What is the total budget for these projects?'],
                ['role' => 'assistant', 'content' => 'The total budget for all ongoing projects is $60,500.00.'],
            ],
            // Session 4: Invoice Summary
            [
                ['role' => 'user', 'content' => 'Show all draft sales invoices'],
                ['role' => 'assistant', 'content' => "I found 5 draft invoices:\n• INV-001 | Draft | $1,250.00 | Jan 15, 2024\n• INV-002 | Draft | $3,400.50 | Jan 18, 2024\n• INV-003 | Draft | $890.00 | Jan 20, 2024\n• INV-004 | Draft | $2,150.75 | Jan 22, 2024\n• INV-005 | Draft | $1,680.00 | Jan 25, 2024"],
                ['role' => 'user', 'content' => 'What is the total amount of draft invoices?'],
                ['role' => 'assistant', 'content' => 'The total amount of all draft invoices is $9,371.25.'],
            ],
            // Session 5: Customer Proposals
            [
                ['role' => 'user', 'content' => 'Show this month accepted proposals'],
                ['role' => 'assistant', 'content' => "I found 3 accepted proposals this month:\n• PROP-001 | Accepted | $5,500.00 | Jan 10, 2024\n• PROP-003 | Accepted | $8,200.00 | Jan 18, 2024\n• PROP-005 | Accepted | $12,300.00 | Jan 25, 2024"],
            ],
        ];

        foreach ($sessions as $index => $session) {
            if (isset($conversations[$index])) {
                $messageTime = $session->created_at;
                foreach ($conversations[$index] as $message) {
                    AIAgentChatMessage::create([
                        'session_id' => $session->id,
                        'role' => $message['role'],
                        'content' => $message['content'],
                        'created_at' => $messageTime,
                        'updated_at' => $messageTime,
                    ]);
                    // Add 2-5 minutes between each message
                    $messageTime = $messageTime->addMinutes(rand(2, 5));
                }
            }
        }
    }
}
