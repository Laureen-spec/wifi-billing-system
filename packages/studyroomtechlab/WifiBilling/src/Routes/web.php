<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\WifiBilling\Http\Controllers\DashboardController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:WifiBilling'])
    ->prefix('wifi-billing')
    ->name('wifi-billing.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/packages', [DashboardController::class, 'packages'])->name('packages.index');
        Route::get('/customers', [DashboardController::class, 'customers'])->name('customers.index');
        Route::get('/routers', [DashboardController::class, 'routers'])->name('routers.index');
    });
