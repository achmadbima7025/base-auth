<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'device.view',
            'device.create',
            'device.update',
            'device.delete',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',
        ];

        $admin = Role::create(['name' => 'admin']);
        $user = Role::create(['name' => 'user']);

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $admin->syncPermissions($permissions);
        $user->syncPermissions([
            'user.view',
            'user.update',
            'device.view',
            'device.create',
            'device.update',
        ]);
    }
}
