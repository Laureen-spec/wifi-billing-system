<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\IspPaymentCenter\Http\Controllers\PaymentCenterController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('wifi-billing')
    ->group(function () {
        Route::get('/payment-center', [PaymentCenterController::class, 'index'])
            ->name('isp-payment-center.index');

        Route::get('/payment-center/export', [PaymentCenterController::class, 'export'])
            ->name('isp-payment-center.export');

        Route::post('/payment-center/manual-payments', [PaymentCenterController::class, 'storeManualPayment'])
            ->name('isp-payment-center.manual-payments.store');
    });
