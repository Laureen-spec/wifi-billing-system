<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltyActivityLogController;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltyCustomerPointController;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltyDashboardController;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltyRewardRuleController;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltySettingsController;
use StudyRoomTechLab\Loyalty\Http\Controllers\LoyaltyVoucherController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Loyalty'])
    ->prefix('loyalty')
    ->name('loyalty.')
    ->group(function (): void {
        Route::get('/', [LoyaltyDashboardController::class, 'index'])->name('index');
        Route::get('/customers', [LoyaltyCustomerPointController::class, 'index'])->name('customers');
        Route::post('/customers/{customer}/manual-points', [LoyaltyCustomerPointController::class, 'awardManualPoints'])
            ->name('customers.manual-points');

        Route::get('/rules', [LoyaltyRewardRuleController::class, 'index'])->name('rules.index');
        Route::get('/rules/create', [LoyaltyRewardRuleController::class, 'create'])->name('rules.create');
        Route::post('/rules', [LoyaltyRewardRuleController::class, 'store'])->name('rules.store');
        Route::get('/rules/{rule}/edit', [LoyaltyRewardRuleController::class, 'edit'])->name('rules.edit');
        Route::match(['put', 'patch'], '/rules/{rule}', [LoyaltyRewardRuleController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{rule}', [LoyaltyRewardRuleController::class, 'destroy'])->name('rules.destroy');

        Route::get('/vouchers', [LoyaltyVoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers/{voucher}/redeem', [LoyaltyVoucherController::class, 'redeem'])->name('vouchers.redeem');
        Route::get('/logs', [LoyaltyActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/settings', [LoyaltySettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [LoyaltySettingsController::class, 'update'])->name('settings.save');
    });
