<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Dto\JsonResponseDto;
use App\Http\Requests\RolePermission\AssignRoleToUserRequest;
use App\Http\Requests\RolePermission\RemoveRoleFromUserRequest;
use App\Libs\HttpStatusCode;
use App\Services\Auth\RolePermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    public function __construct(protected RolePermissionService $service)
    {
    }

    public function getALlRoles(Request $request)
    {
        $result = $this->service->getListAllRoles($request->get('name'), $request->get('perPage'));

        $responseData = JsonResponseDto::success($result);

        return $this->sendResponse($responseData);
    }

    public function createRole(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['string', 'required', 'unique:roles,name'],
        ]);

        $result = $this->service->createRole($validatedData);

        $responseData = JsonResponseDto::success(
            data: $result,
            message: 'Role created successfully.',
            status: HttpStatusCode::CREATED,
        );

        return $this->sendResponse($responseData);
    }

    public function updateRole(Request $request, $roleId)
    {
        $validatedData = $request->validate([
            'name' => ['string', 'required', Rule::unique('roles', 'name')->ignore($roleId)],
        ]);

        $result = $this->service->updateRole($roleId, $validatedData);

        $responseData = JsonResponseDto::success(
            data: $result,
            message: 'Role updated successfully.',
        );

        return $this->sendResponse($responseData);
    }

    public function deleteRole(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string'],
        ]);

        if ($this->service->roleIsAssignedToUsers($validatedData['name'])) {
            return $this->sendResponse(JsonResponseDto::error(message: 'Role is assigned to users.'));
        }

        $this->service->deleteRole($validatedData['name']);
        return $this->sendResponse(JsonResponseDto::success(message: 'Role deleted successfully.'));
    }

    public function getRolePermissions(int $roleName)
    {
        $result = $this->service->getRoleWithPermissions($roleName);
        $responseData = JsonResponseDto::success(
            data: $result,
        );

        return $this->sendResponse($responseData);
    }

    public function getListAllPermissions(Request $request)
    {
        $result = $this->service->getListAllPermissions($request->get('name'), $request->get('perPage'));

        $responseData = JsonResponseDto::success($result);
        return $this->sendResponse($responseData);
    }

    public function createPermission(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['string', 'required', 'unique:permissions,name'],
        ]);

        $result = $this->service->createPermission($validatedData);

        $responseData = JsonResponseDto::success(
            data: $result,
            message: 'Permission created successfully.',
        );

        return $this->sendResponse($responseData);
    }

    public function updatePermission(Request $request, int $permissionId)
    {
        $validatedData = $request->validate([
            'name' => ['string', 'required', Rule::unique('permissions', 'name')->ignore($permissionId)],
        ]);

        $result = $this->service->updatePermission($validatedData['name'], $validatedData);

        $responseData = JsonResponseDto::success(
            data: $result,
            message: 'Permission updated successfully.',
        );

        return $this->sendResponse($responseData);
    }


    public function syncPermissionsToRole(Request $request, int $roleId)
    {
        $validatedData = $request->validate([
            'permissions' => ['array', 'required'],
        ]);

        $this->service->syncPermissionsToRole($roleId, $validatedData['permissions']);

        $responseData = JsonResponseDto::success(
            message: 'Permissions synced successfully.',
        );

        return $this->sendResponse($responseData);
    }

    public function assignRoleToUser(AssignRoleToUserRequest $request)
    {
        $validatedData = $request->validated();

        $this->service->assignRoleToUser($validatedData['user_id'], $validatedData['role']);

        $responseData = JsonResponseDto::success(
            message: 'Role assigned successfully.',
        );

        return $this->sendResponse($responseData);
    }

    public function removeRoleFromUser(RemoveRoleFromUserRequest $request)
    {
        $validatedData = $request->validated();

        $this->service->removeRoleFromUser($validatedData['user_id'], $validatedData['role']);

        return $this->sendResponse(JsonResponseDto::success(message: 'Role removed user successfully.'));
    }

    public function revokePermissionFromRole(Request $request)
    {
        $validatedData = $request->validate([
            'permission' => ['required'],
            'role' => ['required'],
        ]);

        $this->service->revokePermissionFromRole($validatedData['permission'], $validatedData['role']);

        $responseData = JsonResponseDto::success(
            message: 'Permission revoked successfully.',
        );

        return $this->sendResponse($responseData);
    }
}
