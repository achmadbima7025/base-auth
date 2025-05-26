<?php

use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

// Device routes
Route::middleware(['auth:sanctum', 'verify_device'])->group(function () {
    // Device routes for a specific user
    Route::get('/users/{userId}/devices', [DeviceController::class, 'listUserDevices']);
    Route::get('/users/{userId}/devices/{identifier}', [DeviceController::class, 'getDeviceForUserByIdentifier']);

    // Admin routes
    Route::middleware(['is_admin'])->group(function () {
        // General device routes (admin only)
        Route::get('/devices', [DeviceController::class, 'listAllDevice']);
        Route::get('/devices/{deviceId}', [DeviceController::class, 'getDetailDevice']);

        // Device management routes
        Route::post('/devices/register', [DeviceController::class, 'registerDeviceForUserByAdmin']);
        Route::post('/devices/{deviceId}/approve', [DeviceController::class, 'approveDevice']);
        Route::post('/devices/{deviceId}/reject', [DeviceController::class, 'rejectDevice']);
        Route::post('/devices/{deviceId}/revoke', [DeviceController::class, 'revokeDevice']);
    });
});
