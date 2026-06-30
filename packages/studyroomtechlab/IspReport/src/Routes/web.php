<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\IspReport\Http\Controllers\IspReportController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:IspReport'])
    ->prefix('wifi-billing/isp-reports')
    ->name('isp-reports.')
    ->group(function () {
        Route::get('/', [IspReportController::class, 'index'])->name('index');
        Route::get('/staff-logs', [IspReportController::class, 'staffLogs'])->name('staff-logs');
        Route::get('/connection-logs', [IspReportController::class, 'connectionLogs'])->name('connection-logs');
        Route::get('/payment-logs', [IspReportController::class, 'paymentLogs'])->name('payment-logs');
    });
