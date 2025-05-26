<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    public function getListAllRoles(?string $roleName = null, ?int $perPage = 10): LengthAwarePaginator
    {
        return Role::query()
            ->when($roleName, fn ($query, $roleName) => $query->where('name', 'like', "%{$roleName}%"))
            ->paginate($perPage);
    }

    public function createRole(array $data): Role
    {
        return Role::create($data);
    }

    public function updateRole(int $roleId, array $data): Role
    {
        $role = Role::find( $roleId);
        if (!$role) {
            throw new ModelNotFoundException('Role not found.');
        }

        $role->update($data);
        return $role->fresh();
    }

    public function deleteRole(int $roleId): bool
    {
        $role = Role::where('id', $roleId);
        if (!$role) {
            throw new ModelNotFoundException('Role not found.');
        }

        return $role->delete();
    }

    public function getRoleWithPermissions(int $roleId): Role
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new ModelNotFoundException('Role not found.');
        }

        return $role->load('permissions');
    }

    public function getListAllPermissions(?string $permissionName = null, ?int $perPage = 10): LengthAwarePaginator
    {
        return Permission::query()
            ->when($permissionName, fn ($query, $permissionName) => $query->where('name', 'like', "%{$permissionName}%"))
            ->paginate($perPage);
    }

    public function createPermission(array $data): Permission
    {
        return Permission::create($data);
    }

    public function updatePermission(int $permissionId, array $data): Permission
    {
        $permission = Permission::find($permissionId);
        if (!$permission) {
            throw new ModelNotFoundException('Permission not found.');
        }

        $permission->update($data);
        return $permission->fresh();
    }

    public function syncPermissionsToRole(int $roleId, array $permissionIds): void
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new ModelNotFoundException('Role not found.');
        }

        $role->syncPermissions($permissionIds);
    }

    public function revokePermissionFromRole(int $roleId, int $permission): void
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new ModelNotFoundException('Role not found.');
        }

        $role->revokePermissionTo($permission);
    }

    public function assignRoleToUser(int $userId, string $roleName): void
    {
        $user = User::find($userId);
        $role = Role::where('name', $roleName)->first();

        if (!$user || !$role) {
            throw new ModelNotFoundException('User or role not found.');
        }

        $user->assignRole($role);
    }

    public function removeRoleFromUser(int $userId, string $roleName): void
    {
        $user = User::find($userId);
        $user->removeRole($roleName);
    }

    public function roleIsAssignedToUsers(string $roleName): bool
    {
        $result = User::whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->first();

        return $result->exists();
    }
}
