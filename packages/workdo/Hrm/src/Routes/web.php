<?php
use Workdo\Hrm\Http\Controllers\IpRestrictController;
use Workdo\Hrm\Http\Controllers\PayrollController;
use Workdo\Hrm\Http\Controllers\LoanTypeController;
use Workdo\Hrm\Http\Controllers\DeductionTypeController;
use Workdo\Hrm\Http\Controllers\AllowanceTypeController;
use Workdo\Hrm\Http\Controllers\AllowanceController;
use Workdo\Hrm\Http\Controllers\DeductionController;
use Workdo\Hrm\Http\Controllers\LoanController;
use Workdo\Hrm\Http\Controllers\AttendanceController;
use Workdo\Hrm\Http\Controllers\ShiftController;
use Workdo\Hrm\Http\Controllers\CompanyPolicyController;
use Workdo\Hrm\Http\Controllers\LeaveApplicationController;
use Workdo\Hrm\Http\Controllers\LeaveTypeController;
use Workdo\Hrm\Http\Controllers\EventController;
use Workdo\Hrm\Http\Controllers\EventTypeController;
use Workdo\Hrm\Http\Controllers\AnnouncementController;
use Workdo\Hrm\Http\Controllers\AnnouncementCategoryController;
use Workdo\Hrm\Http\Controllers\AcknowledgmentController;
use Workdo\Hrm\Http\Controllers\DocumentCategoryController;
use Workdo\Hrm\Http\Controllers\HolidayController;
use Workdo\Hrm\Http\Controllers\HolidayTypeController;
use Workdo\Hrm\Http\Controllers\EmployeeTransferController;
use Workdo\Hrm\Http\Controllers\ComplaintTypeController;
use Workdo\Hrm\Http\Controllers\WarningController;
use Workdo\Hrm\Http\Controllers\WarningTypeController;
use Workdo\Hrm\Http\Controllers\ComplaintController;
use Workdo\Hrm\Http\Controllers\TerminationController;
use Workdo\Hrm\Http\Controllers\TerminationTypeController;
use Workdo\Hrm\Http\Controllers\ResignationController;
use Workdo\Hrm\Http\Controllers\PromotionController;
use Workdo\Hrm\Http\Controllers\AwardController;
use Workdo\Hrm\Http\Controllers\AwardTypeController;
use Workdo\Hrm\Http\Controllers\EmployeeController;
use Workdo\Hrm\Http\Controllers\EmployeeDocumentTypeController;
use Workdo\Hrm\Http\Controllers\DesignationController;
use Workdo\Hrm\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;
use Workdo\Hrm\Http\Controllers\DashboardController;
use Workdo\Hrm\Http\Controllers\BranchController;
use Workdo\Hrm\Http\Controllers\HrmDocumentController;
use Workdo\Hrm\Http\Controllers\LeaveBalanceController;
use Workdo\Hrm\Http\Controllers\OvertimeController;
use Workdo\Hrm\Http\Controllers\SetSalaryController; 
use Workdo\Hrm\Http\Controllers\WorkingDaysController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Hrm'])->group(function () {
    Route::get('/hrm/dashboard', [DashboardController::class, 'index'])->name('hrm.index');


    Route::prefix('hrm/branches')->name('hrm.branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
        Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/departments')->name('hrm.departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/designations')->name('hrm.designations.')->group(function () {
        Route::get('/', [DesignationController::class, 'index'])->name('index');
        Route::post('/', [DesignationController::class, 'store'])->name('store');
        Route::put('/{designation}', [DesignationController::class, 'update'])->name('update');
        Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/employee-document-types')->name('hrm.employee-document-types.')->group(function () {
        Route::get('/', [EmployeeDocumentTypeController::class, 'index'])->name('index');
        Route::post('/', [EmployeeDocumentTypeController::class, 'store'])->name('store');
        Route::put('/{employeedocumenttype}', [EmployeeDocumentTypeController::class, 'update'])->name('update');
        Route::delete('/{employeedocumenttype}', [EmployeeDocumentTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/employees')->name('hrm.employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    });

    // Delete employee document
    Route::delete('hrm/employees/{employeeId}/documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('hrm.employee-documents.destroy');

    Route::prefix('hrm/award-types')->name('hrm.award-types.')->group(function () {
        Route::get('/', [AwardTypeController::class, 'index'])->name('index');
        Route::post('/', [AwardTypeController::class, 'store'])->name('store');
        Route::put('/{awardtype}', [AwardTypeController::class, 'update'])->name('update');
        Route::delete('/{awardtype}', [AwardTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/awards')->name('hrm.awards.')->group(function () {
        Route::get('/', [AwardController::class, 'index'])->name('index');
        Route::post('/', [AwardController::class, 'store'])->name('store');
        Route::put('/{award}', [AwardController::class, 'update'])->name('update');
        Route::delete('/{award}', [AwardController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/promotions')->name('hrm.promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::post('/', [PromotionController::class, 'store'])->name('store');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
        Route::put('/{promotion}/status', [PromotionController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/resignations')->name('hrm.resignations.')->group(function () {
        Route::get('/', [ResignationController::class, 'index'])->name('index');
        Route::post('/', [ResignationController::class, 'store'])->name('store');
        Route::put('/{resignation}', [ResignationController::class, 'update'])->name('update');
        Route::delete('/{resignation}', [ResignationController::class, 'destroy'])->name('destroy');
        Route::put('/{resignation}/status/{status}', [ResignationController::class, 'updateStatus'])->name('update-status');
    });

    Route::prefix('hrm/termination-types')->name('hrm.termination-types.')->group(function () {
        Route::get('/', [TerminationTypeController::class, 'index'])->name('index');
        Route::post('/', [TerminationTypeController::class, 'store'])->name('store');
        Route::put('/{terminationtype}', [TerminationTypeController::class, 'update'])->name('update');
        Route::delete('/{terminationtype}', [TerminationTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/terminations')->name('hrm.terminations.')->group(function () {
        Route::get('/', [TerminationController::class, 'index'])->name('index');
        Route::post('/', [TerminationController::class, 'store'])->name('store');
        Route::put('/{termination}', [TerminationController::class, 'update'])->name('update');
        Route::put('/{termination}/status', [TerminationController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{termination}', [TerminationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/warning-types')->name('hrm.warning-types.')->group(function () {
        Route::get('/', [WarningTypeController::class, 'index'])->name('index');
        Route::post('/', [WarningTypeController::class, 'store'])->name('store');
        Route::put('/{warningtype}', [WarningTypeController::class, 'update'])->name('update');
        Route::delete('/{warningtype}', [WarningTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/warnings')->name('hrm.warnings.')->group(function () {
        Route::get('/', [WarningController::class, 'index'])->name('index');
        Route::post('/', [WarningController::class, 'store'])->name('store');
        Route::put('/{warning}', [WarningController::class, 'update'])->name('update');
        Route::delete('/{warning}', [WarningController::class, 'destroy'])->name('destroy');
        Route::put('/{warning}/response', [WarningController::class, 'response'])->name('response');
    });

    Route::prefix('hrm/complaints')->name('hrm.complaints.')->group(function () {
        Route::get('/', [ComplaintController::class, 'index'])->name('index');
        Route::post('/', [ComplaintController::class, 'store'])->name('store');
        Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
        Route::put('/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes

    Route::prefix('hrm/complaint-types')->name('hrm.complaint-types.')->group(function () {
        Route::get('/', [ComplaintTypeController::class, 'index'])->name('index');
        Route::post('/', [ComplaintTypeController::class, 'store'])->name('store');
        Route::put('/{complainttype}', [ComplaintTypeController::class, 'update'])->name('update');
        Route::delete('/{complainttype}', [ComplaintTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/employee-transfers')->name('hrm.employee-transfers.')->group(function () {
        Route::get('/', [EmployeeTransferController::class, 'index'])->name('index');
        Route::post('/', [EmployeeTransferController::class, 'store'])->name('store');
        Route::put('/{employeetransfer}', [EmployeeTransferController::class, 'update'])->name('update');
        Route::put('/{employeetransfer}/status', [EmployeeTransferController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{employeetransfer}', [EmployeeTransferController::class, 'destroy'])->name('destroy');
    });



    Route::prefix('hrm/holiday-types')->name('hrm.holiday-types.')->group(function () {
        Route::get('/', [HolidayTypeController::class, 'index'])->name('index');
        Route::post('/', [HolidayTypeController::class, 'store'])->name('store');
        Route::put('/{holidaytype}', [HolidayTypeController::class, 'update'])->name('update');
        Route::delete('/{holidaytype}', [HolidayTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/holidays')->name('hrm.holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/document-categories')->name('hrm.document-categories.')->group(function () {
        Route::get('/', [DocumentCategoryController::class, 'index'])->name('index');
        Route::post('/', [DocumentCategoryController::class, 'store'])->name('store');
        Route::put('/{documentcategory}', [DocumentCategoryController::class, 'update'])->name('update');
        Route::delete('/{documentcategory}', [DocumentCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/documents')->name('hrm.documents.')->group(function () {
        Route::get('/', [HrmDocumentController::class, 'index'])->name('index');
        Route::post('/', [HrmDocumentController::class, 'store'])->name('store');
        Route::put('/{hrmDocument}', [HrmDocumentController::class, 'update'])->name('update');
        Route::put('/{hrmDocument}/status', [HrmDocumentController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{hrmDocument}', [HrmDocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/acknowledgments')->name('hrm.acknowledgments.')->group(function () {
        Route::get('/', [AcknowledgmentController::class, 'index'])->name('index');
        Route::post('/', [AcknowledgmentController::class, 'store'])->name('store');
        Route::put('/{acknowledgment}', [AcknowledgmentController::class, 'update'])->name('update');
        Route::put('/{acknowledgment}/status', [AcknowledgmentController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{acknowledgment}', [AcknowledgmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/announcement-categories')->name('hrm.announcement-categories.')->group(function () {
        Route::get('/', [AnnouncementCategoryController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementCategoryController::class, 'store'])->name('store');
        Route::put('/{announcementcategory}', [AnnouncementCategoryController::class, 'update'])->name('update');
        Route::delete('/{announcementcategory}', [AnnouncementCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/announcements')->name('hrm.announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::put('/{announcement}/status', [AnnouncementController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/event-types')->name('hrm.event-types.')->group(function () {
        Route::get('/', [EventTypeController::class, 'index'])->name('index');
        Route::post('/', [EventTypeController::class, 'store'])->name('store');
        Route::put('/{eventtype}', [EventTypeController::class, 'update'])->name('update');
        Route::delete('/{eventtype}', [EventTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/events')->name('hrm.events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/event-calendar', [EventController::class, 'calendar'])->name('calendar');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::put('/{event}/status-update', [EventController::class, 'statusUpdate'])->name('status-update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes

    Route::prefix('hrm/leave-types')->name('hrm.leave-types.')->group(function () {
        Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
        Route::post('/', [LeaveTypeController::class, 'store'])->name('store');
        Route::put('/{leavetype}', [LeaveTypeController::class, 'update'])->name('update');
        Route::delete('/{leavetype}', [LeaveTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/leave-applications')->name('hrm.leave-applications.')->group(function () {
        Route::get('/', [LeaveApplicationController::class, 'index'])->name('index');
        Route::get('/calendar', [LeaveApplicationController::class, 'calendar'])->name('calendar');
        Route::post('/', [LeaveApplicationController::class, 'store'])->name('store');
        Route::put('/{leaveapplication}', [LeaveApplicationController::class, 'update'])->name('update');
        Route::put('/{leaveapplication}/status', [LeaveApplicationController::class, 'updateStatus'])->name('updateStatus');
        Route::delete('/{leaveapplication}', [LeaveApplicationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/leave-balance')->name('hrm.leave-balance.')->group(function () {
        Route::get('/', [LeaveBalanceController::class, 'index'])->name('index');
    });

    Route::prefix('hrm/company-policies')->name('hrm.company-policies.')->group(function () {
        Route::get('/', [CompanyPolicyController::class, 'index'])->name('index');
        Route::post('/', [CompanyPolicyController::class, 'store'])->name('store');
        Route::put('/{companyPolicy}', [CompanyPolicyController::class, 'update'])->name('update');
        Route::delete('/{companyPolicy}', [CompanyPolicyController::class, 'destroy'])->name('destroy');
    });

    // Dependent dropdown routes
    Route::get('hrm/users/{employee}/leave_types', [LeaveApplicationController::class, 'getLeaveTypesByEmployee'])->name('hrm.users.leave_types');
    Route::get('hrm/leave-balance/{employee}/{leaveType}', [LeaveApplicationController::class, 'getLeaveBalance'])->name('hrm.leave-balance');

    Route::prefix('hrm/shifts')->name('hrm.shifts.')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::post('/', [ShiftController::class, 'store'])->name('store');
        Route::put('/{shift}', [ShiftController::class, 'update'])->name('update');
        Route::delete('/{shift}', [ShiftController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/attendances')->name('hrm.attendances.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');
        Route::get('/clock-status', [AttendanceController::class, 'getClockStatus'])->name('clock-status');
    });

    Route::prefix('hrm/set-salary')->name('hrm.set-salary.')->group(function () {
        Route::get('/', [SetSalaryController::class, 'index'])->name('index');
        Route::get('/{employee}', [SetSalaryController::class, 'show'])->name('show');
        Route::put('/{employee}', [SetSalaryController::class, 'update'])->name('update');
    });

    Route::prefix('hrm/allowance-types')->name('hrm.allowance-types.')->group(function () {
        Route::get('/', [AllowanceTypeController::class, 'index'])->name('index');
        Route::post('/', [AllowanceTypeController::class, 'store'])->name('store');
        Route::put('/{allowancetype}', [AllowanceTypeController::class, 'update'])->name('update');
        Route::delete('/{allowancetype}', [AllowanceTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/deduction-types')->name('hrm.deduction-types.')->group(function () {
        Route::get('/', [DeductionTypeController::class, 'index'])->name('index');
        Route::post('/', [DeductionTypeController::class, 'store'])->name('store');
        Route::put('/{deductiontype}', [DeductionTypeController::class, 'update'])->name('update');
        Route::delete('/{deductiontype}', [DeductionTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/loan-types')->name('hrm.loan-types.')->group(function () {
        Route::get('/', [LoanTypeController::class, 'index'])->name('index');
        Route::post('/', [LoanTypeController::class, 'store'])->name('store');
        Route::put('/{loantype}', [LoanTypeController::class, 'update'])->name('update');
        Route::delete('/{loantype}', [LoanTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hrm/working-days')->name('hrm.working-days.')->group(function () {
        Route::get('/', [WorkingDaysController::class, 'index'])->name('index');
        Route::put('/', [WorkingDaysController::class, 'update'])->name('update');
    });

    // Allowances routes
    Route::prefix('hrm/allowances')->name('hrm.allowances.')->group(function () {
        Route::post('/', [AllowanceController::class, 'store'])->name('store');
        Route::put('/{allowance}', [AllowanceController::class, 'update'])->name('update');
        Route::delete('/{allowance}', [AllowanceController::class, 'destroy'])->name('destroy');
    });

    // Deductions routes
    Route::prefix('hrm/deductions')->name('hrm.deductions.')->group(function () {
        Route::post('/', [DeductionController::class, 'store'])->name('store');
        Route::put('/{deduction}', [DeductionController::class, 'update'])->name('update');
        Route::delete('/{deduction}/{employee}', [DeductionController::class, 'destroy'])->name('destroy');
    });

    // Loans routes
    Route::prefix('hrm/loans')->name('hrm.loans.')->group(function () {
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::put('/{loan}', [LoanController::class, 'update'])->name('update');
        Route::delete('/{loan}/{employee}', [LoanController::class, 'destroy'])->name('destroy');
    });

    // Overtimes routes
    Route::prefix('hrm/overtimes')->name('hrm.overtimes.')->group(function () {
        Route::post('/', [OvertimeController::class, 'store'])->name('store');
        Route::put('/{overtime}', [OvertimeController::class, 'update'])->name('update');
        Route::delete('/{overtime}/{employee}', [OvertimeController::class, 'destroy'])->name('destroy');
    });



    Route::prefix('hrm/payrolls')->name('hrm.payrolls.')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::post('/', [PayrollController::class, 'store'])->name('store');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
        Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');
        Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');
        Route::post('/{payroll}/run', [PayrollController::class, 'runPayroll'])->name('run');
    });

    Route::delete('/hrm/payroll-entries/{payrollEntry}', [PayrollController::class, 'destroyEntry'])->name('hrm.payroll-entries.destroy');
    Route::get('/hrm/payroll-entries/{payrollEntry}/print', [PayrollController::class, 'printPayslip'])->name('hrm.payroll-entries.print');
    Route::patch('/hrm/payroll-entries/{payrollEntry}/pay', [PayrollController::class, 'paySalary'])->name('hrm.payroll-entries.pay');

    Route::prefix('hrm/ip-restricts')->name('hrm.ip-restricts.')->group(function () {
        Route::get('/', [IpRestrictController::class, 'index'])->name('index');
        Route::post('/', [IpRestrictController::class, 'store'])->name('store');
        Route::put('/{iprestrict}', [IpRestrictController::class, 'update'])->name('update');
        Route::delete('/{iprestrict}', [IpRestrictController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-setting', [IpRestrictController::class, 'toggleSetting'])->name('toggle-setting');
    });
});
