<?php

use Illuminate\Support\Facades\Route;
use StudyRoomTechLab\Leads\Http\Controllers\LeadDeskController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Leads'])
    ->prefix('lead-desk')
    ->name('studyroom-leads.')
    ->group(function () {
        Route::get('/', [LeadDeskController::class, 'index'])->name('index');
        Route::post('/', [LeadDeskController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{lead}', [LeadDeskController::class, 'update'])->name('update');
        Route::post('/{lead}/contacted', [LeadDeskController::class, 'markContacted'])->name('contacted');
        Route::post('/{lead}/convert', [LeadDeskController::class, 'convert'])->name('convert');
        Route::delete('/{lead}', [LeadDeskController::class, 'destroy'])->name('destroy');
    });
