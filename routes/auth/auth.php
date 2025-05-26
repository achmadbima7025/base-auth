<?php

use App\Http\Controllers\Api\AuthController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(app()->environment('testing') ? [] : ['verify_device'])
        ->name('login');

    Route::middleware(['auth:sanctum', 'verify_device'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/users/{userId}', [AuthController::class, 'getUserDetails']);
        Route::post('/register', [AuthController::class, 'register'])->middleware(['is_admin']);
    });
});
