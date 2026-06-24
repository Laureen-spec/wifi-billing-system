<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\WifiBilling\Http\Controllers\DashboardController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck'])
    ->prefix('wifi-billing')
    ->name('wifi-billing.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
