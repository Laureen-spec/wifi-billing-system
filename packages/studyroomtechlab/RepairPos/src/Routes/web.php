<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\RepairPos\Http\Controllers\RepairCustomerController;
use StudyRoomTechLab\RepairPos\Http\Controllers\RepairJobController;
use StudyRoomTechLab\RepairPos\Http\Controllers\RepairPosDashboardController;
use StudyRoomTechLab\RepairPos\Http\Controllers\RepairProductController;
use StudyRoomTechLab\RepairPos\Http\Controllers\RepairSaleController;

Route::middleware(['web', 'auth', 'PlanModuleCheck:RepairPos'])
    ->prefix('repair-pos')
    ->name('repair-pos.')
    ->group(function () {
        Route::get('/', [RepairPosDashboardController::class, 'index'])->name('dashboard');
        Route::get('/customers', [RepairCustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [RepairCustomerController::class, 'store'])->name('customers.store');
        Route::get('/jobs', [RepairJobController::class, 'index'])->name('jobs.index');
        Route::post('/jobs', [RepairJobController::class, 'store'])->name('jobs.store');
        Route::patch('/jobs/{job}/status', [RepairJobController::class, 'updateStatus'])->name('jobs.status');
        Route::get('/products', [RepairProductController::class, 'index'])->name('products.index');
        Route::post('/products', [RepairProductController::class, 'store'])->name('products.store');
        Route::get('/sales', [RepairSaleController::class, 'index'])->name('sales.index');
        Route::post('/sales', [RepairSaleController::class, 'store'])->name('sales.store');
    });
