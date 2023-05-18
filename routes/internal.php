<?php

use App\Http\Controllers\Internal\Company\CompanyManagementController;
use App\Http\Controllers\Internal\Users\AuthenticationController;
use Illuminate\Support\Facades\Route;


/* A way of grouping routes. */

Route::controller(AuthenticationController::class)->group(function () {
    Route::post('/login', 'staffLogin');
    Route::post('/register', 'registerStaff')->middleware(['auth:sanctum', 'type.staff']);
});

Route::middleware(['auth:sanctum', 'type.staff'])->group(function () {
    Route::prefix('companies')->group(function () {
        Route::controller(CompanyManagementController::class)->group(function () {
            Route::get('/list', 'listCompanies');
            Route::post('/activate', 'activateCompany');
            Route::post('/deactivate', 'deactivateCompany');
            Route::prefix('smssettings')->group(function () {
                Route::get('/listsupported', 'smsProviders');
                Route::get('/listsmsaccounts', 'smsAccounts');
                Route::post('/save', 'saveCompanySMSSetting');
                Route::post('/update', 'updateCompanySMSSetting');
            });
        });
    });
});
