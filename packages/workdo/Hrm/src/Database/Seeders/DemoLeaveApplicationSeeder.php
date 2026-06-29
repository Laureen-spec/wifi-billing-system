<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoLeaveApplicationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (LeaveApplication::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        // Validate userId
        if (!$userId || !is_numeric($userId)) {
            return;
        }

        // Get available employees and leave types with proper validation
        $employees = Employee::where('created_by', $userId)
            ->whereHas('user')
            ->with('user')
            ->get();

        $leaveTypes = LeaveType::where('created_by', $userId)->get();
        $approvers = User::where('created_by', $userId)
            ->where('type', 'company')
            ->get();

        // Early return if no data available
        if ($employees->isEmpty() || $leaveTypes->isEmpty()) {
            return;
        }

        // Separate paid and unpaid leave types dynamically
        $paidLeaveTypes = $leaveTypes->where('is_paid', true)->values();
        $unpaidLeaveTypes = $leaveTypes->where('is_paid', false)->values();

        // Ensure we have both paid and unpaid leave types
        if ($paidLeaveTypes->isEmpty() || $unpaidLeaveTypes->isEmpty()) {
            return;
        }

        $reasons = [
            'Family emergency and need to attend to urgent matters',
            'Medical appointment and health checkup scheduled',
            'Personal vacation and relaxation time needed',
            'Wedding ceremony of close family member',
            'Child illness and need to provide care',
            'Home renovation work supervision required',
            'Educational course attendance and skill development',
            'Religious festival celebration with family',
            'Travel for family reunion and gathering',
            'Mental health break and stress management'
        ];

        $statuses = ['pending', 'approved', 'rejected'];
        
        // Get current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $months = [$currentMonth]; // Only current month
        $year = $currentYear;
        
        $totalLeaves = 0;
        $maxLeaves = 35;

        // Create maximum 30-35 leave applications
        foreach ($months as $monthIndex => $month) {
            if ($totalLeaves >= $maxLeaves) break;
            
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

            foreach ($employees as $employeeIndex => $employee) {
                if ($totalLeaves >= $maxLeaves) break;
                
                if (!$employee->user_id || !$employee->user) {
                    continue;
                }

                $leavesPerEmployee = ($monthIndex == 0) ? 2 : 1;
                for ($leaveIndex = 0; $leaveIndex < $leavesPerEmployee; $leaveIndex++) {
                    if ($totalLeaves >= $maxLeaves) break;
                    // First 3 leaves are unpaid, last 2 are paid
                    $isPaidLeave = $leaveIndex >= 3;
                    $selectedLeaveTypes = $isPaidLeave ? $paidLeaveTypes : $unpaidLeaveTypes;
                    $leaveType = $selectedLeaveTypes[($employeeIndex + $leaveIndex) % $selectedLeaveTypes->count()];

                    // Generate valid dates within the specific month
                    $startDay = 1 + ($leaveIndex * 4) + ($monthIndex % 3); // Better distribution
                    $startDay = min($startDay, $daysInMonth - 3); // Ensure space for duration
                    $startDay = max($startDay, 1); // Ensure valid day

                    try {
                        $startDate = Carbon::create($year, $month, $startDay);
                    } catch (\Exception $e) {
                        continue; // Skip invalid dates
                    }

                    // Ensure start date is not weekend (Saturday=6, Sunday=0)
                    while ($startDate->dayOfWeek == 0 || $startDate->dayOfWeek == 6) {
                        $startDate->addDay();
                        // Skip if we go beyond month end
                        if ($startDate->month != $month) {
                            continue 2; // Skip this leave application
                        }
                    }

                    // Leave duration: 1-3 days (only weekdays)
                    $maxDuration = min(3, $daysInMonth - $startDate->day + 1);
                    $duration = 1 + ($leaveIndex % $maxDuration);
                    $endDate = $startDate->copy();
                    $daysAdded = 0;
                    
                    // Add only weekdays for duration
                    while ($daysAdded < $duration - 1) {
                        $endDate->addDay();
                        // Skip weekends
                        if ($endDate->dayOfWeek != 0 && $endDate->dayOfWeek != 6) {
                            $daysAdded++;
                        }
                        // Stop if we go beyond month end
                        if ($endDate->month != $month) {
                            break;
                        }
                    }

                    // Dynamic status and reason rotation
                    $statusIndex = ($employeeIndex + $leaveIndex + $monthIndex) % count($statuses);
                    $status = $statuses[$statusIndex];

                    $reasonIndex = ($employeeIndex + $leaveIndex + $monthIndex) % count($reasons);
                    $reason = $reasons[$reasonIndex];

                    $leaveApplication = [
                        'leave_type_id' => $leaveType->id,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'total_days' => $duration,
                        'reason' => $reason,
                        'attachment' => 'leave-application.png',
                        'status' => $status,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ];

                    // Add approval details for approved/rejected applications
                    if ($status !== 'pending' && !$approvers->isEmpty()) {
                        $approverIndex = ($employeeIndex + $leaveIndex) % $approvers->count();
                        $approver = $approvers[$approverIndex];
                        $leaveApplication['approved_by'] = $approver->id;
                        $leaveApplication['approved_at'] = $startDate->subDays(2)->format('Y-m-d H:i:s');

                        if ($status === 'approved') {
                            $approverComments = [
                                'Leave approved. Enjoy your time off.',
                                'Approved as per company policy.',
                                'Leave granted. Please ensure work handover.',
                                'Approved. Have a good break.'
                            ];
                            $commentIndex = ($employeeIndex + $leaveIndex) % count($approverComments);
                            $leaveApplication['approver_comment'] = $approverComments[$commentIndex];
                        } else {
                            $rejectionComments = [
                                'Leave rejected due to project deadlines.',
                                'Cannot approve due to staff shortage.',
                                'Please reschedule for a later date.',
                                'Rejected as per department requirements.'
                            ];
                            $commentIndex = ($employeeIndex + $leaveIndex) % count($rejectionComments);
                            $leaveApplication['approver_comment'] = $rejectionComments[$commentIndex];
                        }
                    }

                    // Use updateOrCreate with comprehensive unique key to prevent duplicates
                    LeaveApplication::updateOrCreate(
                        [
                            'employee_id' => $employee->user_id,
                            'start_date' => $startDate->format('Y-m-d'),
                            'end_date' => $endDate->format('Y-m-d'),
                            'created_by' => $userId
                        ],
                        $leaveApplication
                    );
                    
                    $totalLeaves++;
                }
            }
        }
    }
}
