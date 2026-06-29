<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\RentalManagement\Http\Controllers\RentalDashboardController;
use StudyRoomTechLab\RentalManagement\Http\Controllers\RentalInvoiceController;
use StudyRoomTechLab\RentalManagement\Http\Controllers\RentalPropertyController;
use StudyRoomTechLab\RentalManagement\Http\Controllers\RentalTenantController;
use StudyRoomTechLab\RentalManagement\Http\Controllers\RentalUnitController;

Route::middleware(['web', 'auth', 'PlanModuleCheck:RentalManagement'])
    ->prefix('rental-management')
    ->name('rental-management.')
    ->group(function () {
        Route::get('/', [RentalDashboardController::class, 'index'])->name('dashboard');
        Route::get('/properties', [RentalPropertyController::class, 'index'])->name('properties.index');
        Route::post('/properties', [RentalPropertyController::class, 'store'])->name('properties.store');
        Route::get('/units', [RentalUnitController::class, 'index'])->name('units.index');
        Route::post('/units', [RentalUnitController::class, 'store'])->name('units.store');
        Route::get('/tenants', [RentalTenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [RentalTenantController::class, 'store'])->name('tenants.store');
        Route::post('/tenants/{tenant}/move-out', [RentalTenantController::class, 'moveOut'])->name('tenants.move-out');
        Route::get('/invoices', [RentalInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [RentalInvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{invoice}/payments', [RentalInvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    });
