<?php

use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

// All device routes are protected with Sanctum auth middleware
Route::prefix('devices')->group(function () {

    Route::middleware(['auth:sanctum', 'verify_device'])->group(function () {
        Route::get('/', [DeviceController::class, 'listAllDevice']);
        Route::get('/{deviceId}', [DeviceController::class, 'getDetailDevice']);
        Route::get('/user/{userId}', [DeviceController::class, 'listUserDevices']);
        Route::get('/user/{userId}/identifier/{identifier}', [DeviceController::class, 'getDeviceForUserByIdentifier']);

        // Admin routes
        Route::middleware(['is_admin'])->group(function () {
            Route::post('/register', [DeviceController::class, 'registerDeviceForUserByAdmin']);
            Route::post('/approve', [DeviceController::class, 'approveDevice']);
            Route::post('/reject', [DeviceController::class, 'rejectDevice']);
            Route::post('/revoke', [DeviceController::class, 'revokeDevice']);
        });

        // PUT /api/devices/{deviceId}/update-last-used - Update the last used timestamp for a device
        Route::put('/{deviceId}/update-last-used', [DeviceController::class, 'updateDeviceLastUsed']);
    });
});
