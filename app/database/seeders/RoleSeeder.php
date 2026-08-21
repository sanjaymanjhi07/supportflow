<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    protected array $permissions = [
        'tickets.view',
        'tickets.create',
        'tickets.update',
        'tickets.delete',
        'tickets.assign',
        'users.manage',
        'webhooks.manage',
        'sla.manage',
    ];

    protected array $roleMap = [
        'owner' => '*',
        'admin' => '*',
        'agent' => ['tickets.view', 'tickets.create', 'tickets.update', 'tickets.assign'],
        'customer' => ['tickets.view', 'tickets.create'],
    ];

    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        // Roles are tenant-scoped (spatie "teams" mode), so seed them for
        // every existing tenant.
        Tenant::all()->each(function (Tenant $tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            foreach ($this->roleMap as $roleName => $abilities) {
                $role = Role::findOrCreate($roleName, 'api');

                if ($abilities === '*') {
                    $role->syncPermissions($this->permissions);
                } else {
                    $role->syncPermissions($abilities);
                }
            }
        });
    }
}
