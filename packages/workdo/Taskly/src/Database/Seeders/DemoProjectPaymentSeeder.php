<?php

namespace Workdo\Taskly\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectMilestone;
use Workdo\Taskly\Models\ProjectPayment;
use Workdo\Taskly\Models\ProjectPaymentItem;

class DemoProjectPaymentSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ProjectPayment::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            $projects = Project::where('created_by', $userId)->with('milestones')->get();
            if ($projects->isEmpty()) {
                return;
            }

            $paymentCounter = 1;

            foreach ($projects as $project) {
                if ($project->milestones->isEmpty()) {
                    continue;
                }

                $clients = $project->clients;
                $customerId = $clients->isNotEmpty() ? $clients->first()->id : null;

                // Create 1-3 payments per project
                $paymentCount = rand(1, 3);

                for ($i = 0; $i < $paymentCount; $i++) {
                    $paymentDate = Carbon::parse($project->start_date)->addDays(rand(10, 60));
                    $dueDate = (clone $paymentDate)->addDays(rand(15, 45));

                    // Select 1-3 random milestones for this payment
                    $selectedMilestones = $project->milestones->random(min(rand(1, 3), $project->milestones->count()));

                    $subtotal = 0;
                    $totalDiscount = 0;
                    $items = [];

                    foreach ($selectedMilestones as $milestone) {
                        $price = $milestone->cost;
                        $discountPercentage = rand(0, 15);
                        $discountAmount = ($price * $discountPercentage) / 100;
                        $totalAmount = $price - $discountAmount;

                        $subtotal += $price;
                        $totalDiscount += $discountAmount;

                        $items[] = [
                            'milestone_id' => $milestone->id,
                            'price' => $price,
                            'discount_percentage' => $discountPercentage,
                            'discount_amount' => $discountAmount,
                            'total_amount' => $totalAmount,
                        ];
                    }

                    $finalTotal = $subtotal - $totalDiscount;
                    $status = rand(1, 100) <= 70 ? 'posted' : 'draft';
                    $paidAmount = $status === 'posted' ? rand(0, (int)$finalTotal) : 0;
                    $balanceAmount = $finalTotal - $paidAmount;

                    $payment = ProjectPayment::create([
                        'payment_number' => 'PAY-' . str_pad($paymentCounter++, 5, '0', STR_PAD_LEFT),
                        'payment_date' => $paymentDate,
                        'due_date' => $dueDate,
                        'project_id' => $project->id,
                        'customer_id' => $customerId,
                        'subtotal' => $subtotal,
                        'discount_amount' => $totalDiscount,
                        'total_amount' => $finalTotal,
                        'paid_amount' => $paidAmount,
                        'balance_amount' => $balanceAmount,
                        'bank_account_id' => null,
                        'status' => $status,
                        'payment_terms' => $this->getRandomPaymentTerms(),
                        'notes' => $this->getRandomNotes(),
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ]);

                    // Create payment items
                    foreach ($items as $itemData) {
                        ProjectPaymentItem::create(array_merge($itemData, [
                            'payment_id' => $payment->id,
                        ]));
                    }
                }
            }
        }
    }

    private function getRandomPaymentTerms(): string
    {
        $terms = [
            'Payment due within 30 days of invoice date. Late payments subject to 1.5% monthly interest.',
            'Net 15 days. Payment must be received within 15 days from invoice date.',
            'Net 45 days. Please remit payment within 45 days to avoid late fees.',
            '50% advance payment required, balance due upon project completion.',
            'Payment due upon receipt. Bank transfer or credit card accepted.',
        ];

        return $terms[array_rand($terms)];
    }

    private function getRandomNotes(): ?string
    {
        $notes = [
            'Thank you for your business. Please contact us if you have any questions.',
            'This payment covers the milestone deliverables as per project agreement.',
            'All work completed as per specifications. Invoice attached for your records.',
            'Payment includes development, testing, and deployment services.',
            null,
        ];

        return $notes[array_rand($notes)];
    }
}
