<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\Tr069\Http\Controllers\Tr069ApiController;

Route::middleware(['api'])
    ->prefix('api/tr069')
    ->name('api.tr069.')
    ->group(function (): void {
        Route::post('/inform', [Tr069ApiController::class, 'inform'])->name('inform');
        Route::get('/devices/{device}/jobs', [Tr069ApiController::class, 'jobs'])->name('devices.jobs');
        Route::post('/jobs/{job}/complete', [Tr069ApiController::class, 'completeJob'])->name('jobs.complete');
        Route::post('/jobs/{job}/fail', [Tr069ApiController::class, 'failJob'])->name('jobs.fail');
    });
