<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaDashboardController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaHotspotPortalController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaPaymentController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaSettingsController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaSettlementController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaTransactionController;
use StudyRoomTechLab\MpesaPayment\Http\Controllers\MpesaWalletController;

Route::middleware(['web'])
    ->prefix('mpesa-payment')
    ->name('mpesa-payment.')
    ->group(function () {

        Route::get('/status', function () {
            return response('MpesaPayment add-on loaded');
        })->name('status');

        /*
        |--------------------------------------------------------------------------
        | Public Hotspot Portal Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/hotspot', [MpesaHotspotPortalController::class, 'index'])
            ->name('hotspot.index');

        Route::post('/hotspot/validate-phone', [MpesaHotspotPortalController::class, 'validatePhone'])
            ->name('hotspot.validate-phone');

        Route::post('/hotspot/stk-push', [MpesaHotspotPortalController::class, 'stkPush'])
            ->name('hotspot.stk-push');

        Route::post('/hotspot/simulate-payment', [MpesaHotspotPortalController::class, 'simulatePayment'])
            ->name('hotspot.simulate-payment');

        Route::get('/hotspot/transactions/{transaction}/status', [MpesaHotspotPortalController::class, 'status'])
            ->name('hotspot.transactions.status');

        /*
        |--------------------------------------------------------------------------
        | M-Pesa Callback
        |--------------------------------------------------------------------------
        */
        Route::post('/callback', [MpesaPaymentController::class, 'callback'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('callback');

        /*
        |--------------------------------------------------------------------------
        | Admin / Super Admin Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth'])->group(function () {
            Route::get('/', [MpesaDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/dashboard', [MpesaDashboardController::class, 'index'])
                ->name('dashboard.index');

            Route::get('/settings', [MpesaSettingsController::class, 'index'])
                ->name('settings.index');

            Route::post('/settings/platform', [MpesaSettingsController::class, 'savePlatform'])
                ->name('settings.platform.save');

            Route::post('/stk-push', [MpesaPaymentController::class, 'initiate'])
                ->name('stk-push');

            Route::post('/plan-subscription/stk-push', [MpesaPaymentController::class, 'initiatePlanSubscription'])
                ->name('plan-subscription.stk-push');

            Route::get('/plan-subscription/transactions/{transaction}/status', [MpesaPaymentController::class, 'planSubscriptionStatus'])
                ->name('plan-subscription.status');

            Route::get('/transactions', [MpesaTransactionController::class, 'index'])
                ->name('transactions.index');

            Route::get('/transactions/{transaction}/status', [MpesaPaymentController::class, 'status'])
                ->name('transactions.status');

            Route::get('/transactions/{transaction}', [MpesaTransactionController::class, 'show'])
                ->name('transactions.show');

            Route::get('/wallets', [MpesaWalletController::class, 'index'])
                ->name('wallets.index');

            Route::get('/wallets/{wallet}', [MpesaWalletController::class, 'show'])
                ->name('wallets.show');

            Route::get('/settlements', [MpesaSettlementController::class, 'index'])
                ->name('settlements.index');

            Route::get('/settlements/{settlement}', [MpesaSettlementController::class, 'show'])
                ->name('settlements.show');

            Route::post('/settlements/{settlement}/approve', [MpesaSettlementController::class, 'approve'])
                ->name('settlements.approve');

            Route::post('/settlements/{settlement}/mark-paid', [MpesaSettlementController::class, 'markPaid'])
                ->name('settlements.mark-paid');

            Route::post('/settlements/{settlement}/mark-failed', [MpesaSettlementController::class, 'markFailed'])
                ->name('settlements.mark-failed');
        });
    });