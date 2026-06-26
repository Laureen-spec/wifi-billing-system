<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\WifiBillingHrm\Http\Controllers\DashboardController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:WifiBillingHrm'])
    ->prefix('wifi-billing/hrm')
    ->name('wifi-billing-hrm.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
