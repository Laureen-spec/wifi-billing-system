<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069DashboardController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069DeviceController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069FirmwareController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069JobController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069LogController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069ProfileController;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069SettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Tr069'])
    ->prefix('tr069')
    ->name('tr069.')
    ->group(function (): void {
        Route::get('/', [Tr069DashboardController::class, 'index'])->name('index');

        Route::get('/devices', [Tr069DeviceController::class, 'index'])->name('devices.index');
        Route::get('/devices/{device}', [Tr069DeviceController::class, 'show'])->name('devices.show');
        Route::post('/devices/{device}/provision', [Tr069DeviceController::class, 'queueProvision'])->name('devices.provision');
        Route::post('/devices/{device}/reboot', [Tr069DeviceController::class, 'queueReboot'])->name('devices.reboot');
        Route::post('/devices/{device}/push-profile', [Tr069DeviceController::class, 'pushProfile'])->name('devices.push-profile');

        Route::get('/profiles', [Tr069ProfileController::class, 'index'])->name('profiles.index');
        Route::get('/profiles/create', [Tr069ProfileController::class, 'create'])->name('profiles.create');
        Route::post('/profiles', [Tr069ProfileController::class, 'store'])->name('profiles.store');
        Route::get('/profiles/{profile}/edit', [Tr069ProfileController::class, 'edit'])->name('profiles.edit');
        Route::match(['put', 'patch'], '/profiles/{profile}', [Tr069ProfileController::class, 'update'])->name('profiles.update');
        Route::delete('/profiles/{profile}', [Tr069ProfileController::class, 'destroy'])->name('profiles.destroy');

        Route::get('/config-jobs', [Tr069JobController::class, 'index'])->name('jobs.index');
        Route::get('/config-jobs/create', [Tr069JobController::class, 'create'])->name('jobs.create');
        Route::post('/config-jobs', [Tr069JobController::class, 'store'])->name('jobs.store');

        Route::get('/firmware', [Tr069FirmwareController::class, 'index'])->name('firmware.index');
        Route::post('/firmware', [Tr069FirmwareController::class, 'store'])->name('firmware.store');
        Route::post('/firmware/{firmware}/queue', [Tr069FirmwareController::class, 'queue'])->name('firmware.queue');

        Route::get('/logs', [Tr069LogController::class, 'index'])->name('logs.index');
        Route::get('/settings', [Tr069SettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [Tr069SettingsController::class, 'update'])->name('settings.save');
    });
