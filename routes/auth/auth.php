<?php

use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['verify_device'])
    ->name('login');

Route::middleware(['auth:sanctum', 'verify_device'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users/{user}', [AuthController::class, 'getUserDetails']);
    Route::post('/register', [AuthController::class, 'register'])->middleware(['is_admin']);
});
