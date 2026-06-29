<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\Expenses\Http\Controllers\IspExpenseController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Expenses'])
    ->prefix('expenses')
    ->name('expenses.')
    ->group(function (): void {
        Route::get('/', [IspExpenseController::class, 'index'])->name('index');
        Route::get('/create', [IspExpenseController::class, 'create'])->name('create');
        Route::post('/', [IspExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}/edit', [IspExpenseController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{expense}', [IspExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [IspExpenseController::class, 'destroy'])->name('destroy');
    });
