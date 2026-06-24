<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck'])
    ->prefix('wifi-billing/hrm')
    ->name('wifi-billing-hrm.')
    ->group(function () {
        //
    });
