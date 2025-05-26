<?php

use App\Http\Controllers\Api\RolePermissionController;

Route::group(['prefix' => 'role-permission'], function () {
    Route::get('/', [RolePermissionController::class, 'getALlRoles']);
    Route::post('/', [RolePermissionController::class, 'createRole']);
    Route::post('/{roleId}', [RolePermissionController::class, 'updateRole']);
    Route::post('/{roleId}/delete}', [RolePermissionController::class, 'deleteRole']);
    Route::get('/{roleId}/details', [RolePermissionController::class, 'getRolePermissions']);
    Route::get('/permissions', [RolePermissionController::class, 'getListAllPermissions']);
    Route::post('/permissions', [RolePermissionController::class, 'createPermission']);
    Route::post('/permissions/{permissionId}', [RolePermissionController::class, 'updatePermission']);
    Route::post('/role-sync-permissions', [RolePermissionController::class, 'syncPermissionsToRole']);
    Route::post('/assign-role-user', [RolePermissionController::class, 'assignRoleToUser']);
    Route::post('/remove-role-from-user', [RolePermissionController::class, 'removeRoleFromUser']);
    Route::post('/revoke-role-from-user', [RolePermissionController::class, 'revokePermissionFromRole']);
});
