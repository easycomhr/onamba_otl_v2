<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:5,15'])->group(function () {
    Route::post('/auth/login', \App\Http\Controllers\Api\LoginController::class);
});

Route::middleware('api.key')->group(function () {
    Route::get('/approved-ot', [\App\Http\Controllers\Api\ApprovedOtController::class, 'index']);
    Route::get('/approved-leaves', [\App\Http\Controllers\Api\ApprovedLeavesController::class, 'index']);
    Route::patch('/v1/users/update-leave-balances', \App\Http\Controllers\Api\UpdateLeaveBalanceController::class);
});
