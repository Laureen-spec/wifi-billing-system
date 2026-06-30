<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\IspWhatsapp\Http\Controllers\IspWhatsappController;

Route::match(['get', 'post'], '/isp/whatsapp/webhook/{ispId}', [IspWhatsappController::class, 'webhook'])
    ->whereNumber('ispId')
    ->name('isp.whatsapp.webhook');

Route::middleware(['web', 'auth', 'PlanModuleCheck:IspWhatsapp'])
    ->prefix('isp/whatsapp')
    ->name('isp.whatsapp.')
    ->group(function () {
        Route::get('/', [IspWhatsappController::class, 'overview'])->name('index');
        Route::get('/inbox', [IspWhatsappController::class, 'inbox'])->name('inbox');
        Route::get('/bot-flows', [IspWhatsappController::class, 'botFlows'])->name('bot-flows');
        Route::get('/payment-requests', [IspWhatsappController::class, 'paymentRequests'])->name('payment-requests');
        Route::get('/receipts', [IspWhatsappController::class, 'receipts'])->name('receipts');
        Route::get('/support-tickets', [IspWhatsappController::class, 'supportTickets'])->name('support-tickets');
        Route::get('/broadcasts', [IspWhatsappController::class, 'broadcasts'])->name('broadcasts');
        Route::get('/templates', [IspWhatsappController::class, 'templates'])->name('templates');
        Route::get('/usage', [IspWhatsappController::class, 'usage'])->name('usage');
        Route::get('/api-settings', [IspWhatsappController::class, 'apiSettings'])->name('api-settings');
        Route::get('/logs', [IspWhatsappController::class, 'logs'])->name('logs');
        Route::get('/settings', [IspWhatsappController::class, 'settings'])->name('settings');

        Route::post('/conversations/{conversation}/reply', [IspWhatsappController::class, 'sendReply'])->name('conversations.reply');
        Route::post('/conversations/{conversation}/template', [IspWhatsappController::class, 'sendTemplate'])->name('conversations.template');
        Route::post('/conversations/{conversation}/note', [IspWhatsappController::class, 'internalNote'])->name('conversations.note');
        Route::post('/conversations/{conversation}/handover', [IspWhatsappController::class, 'handover'])->name('conversations.handover');
        Route::patch('/conversations/{conversation}', [IspWhatsappController::class, 'updateConversation'])->name('conversations.update');

        Route::post('/api-settings', [IspWhatsappController::class, 'saveSettings'])->name('api-settings.save');
        Route::post('/api-settings/test', [IspWhatsappController::class, 'testConnection'])->name('api-settings.test');

        Route::post('/templates', [IspWhatsappController::class, 'saveTemplate'])->name('templates.save');
        Route::post('/payment-requests', [IspWhatsappController::class, 'createPaymentRequest'])->name('payment-requests.store');
        Route::post('/payment-requests/{paymentRequest}/confirm', [IspWhatsappController::class, 'confirmPaymentRequest'])->name('payment-requests.confirm');
        Route::post('/broadcasts', [IspWhatsappController::class, 'saveBroadcast'])->name('broadcasts.save');
        Route::post('/broadcasts/{broadcast}/confirm', [IspWhatsappController::class, 'confirmBroadcast'])->name('broadcasts.confirm');
    });
