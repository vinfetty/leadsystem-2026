<?php

use App\Http\Controllers\Api\LeadIntakeController;
use App\Http\Middleware\AuthenticateLeadSource;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/leads', [LeadIntakeController::class, 'store'])
        ->middleware([AuthenticateLeadSource::class, 'throttle:60,1'])
        ->name('api.v1.leads.store');
});
