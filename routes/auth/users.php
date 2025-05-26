<?php

use App\Http\Controllers\Api\UserController;

Route::group([
    'prefix' => 'users',
    'middleware' => ['auth:sanctum', 'verify_device']
], function () {
    Route::get('/', [UserController::class, 'listAllUsers'])->middleware(['is_admin']);
    Route::post('/{userId}/update', [UserController::class, 'updateUser']);
});
