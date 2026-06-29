<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\IspSms\Http\Controllers\IspSmsController;
use StudyRoomTechLab\IspSms\Http\Controllers\IspSmsSettingsController;
use StudyRoomTechLab\IspSms\Http\Controllers\IspSmsTemplateController;
use StudyRoomTechLab\IspSms\Http\Controllers\IspSmsTopupController;

Route::middleware(['web', 'auth', 'PlanModuleCheck:IspSms'])
    ->prefix('isp')
    ->name('isp.')
    ->group(function () {
        Route::get('/sms', [IspSmsController::class, 'index'])->name('sms.index');
        Route::get('/sms/create', [IspSmsController::class, 'newMessage'])->name('sms.create');
        Route::get('/sms/new-message', [IspSmsController::class, 'newMessage'])->name('sms.new-message');
        Route::post('/sms/new-message/send', [IspSmsController::class, 'sendNewMessage'])->name('sms.new-message.send');
        Route::post('/sms', [IspSmsController::class, 'store'])->name('sms.store');

        Route::get('/sms/settings', [IspSmsController::class, 'settings'])->name('sms.settings');
        Route::post('/sms/settings', [IspSmsSettingsController::class, 'update'])->name('sms.settings.save');
        Route::put('/sms/settings', [IspSmsSettingsController::class, 'update'])->name('sms.settings.update');

        Route::get('/sms/topup', [IspSmsTopupController::class, 'create'])->name('sms.topup');
        Route::post('/sms/topup', [IspSmsTopupController::class, 'store'])->name('sms.topup.store');

        Route::get('/sms/templates', [IspSmsController::class, 'templates'])->name('sms.templates.index');
        Route::post('/sms/templates', [IspSmsTemplateController::class, 'store'])->name('sms.templates.store');

        Route::get('/sms/{message}', [IspSmsController::class, 'show'])->name('sms.show');
    });
