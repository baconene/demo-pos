<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web'], ['description' => 'Cashier staff']);
        $kitchenRole = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web'], ['description' => 'Kitchen/Queue staff']);
        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web'], ['description' => 'Auditor/Inventory staff']);
        $adminRole   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web'], ['description' => 'Administrator']);

        // Create permissions
        $permissions = [
            ['name' => 'create orders',    'description' => 'Create new orders'],
            ['name' => 'view orders',       'description' => 'View orders'],
            ['name' => 'update orders',     'description' => 'Update order status'],
            ['name' => 'delete orders',     'description' => 'Delete orders'],
            ['name' => 'process payments',  'description' => 'Process order payments'],
            ['name' => 'refund payments',   'description' => 'Issue refunds'],
            ['name' => 'view inventory',    'description' => 'View inventory'],
            ['name' => 'manage inventory',  'description' => 'Manage inventory'],
            ['name' => 'view reports',      'description' => 'View reports'],
            ['name' => 'export reports',    'description' => 'Export reports'],
            ['name' => 'manage users',      'description' => 'Manage users'],
            ['name' => 'manage roles',      'description' => 'Manage roles and permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name'], 'guard_name' => 'web'], ['description' => $permission['description']]);
        }

        // Assign permissions to roles
        $cashierPermissions = ['create orders', 'view orders', 'update orders', 'process payments', 'view inventory'];
        $kitchenPermissions = ['view orders', 'update orders', 'view inventory'];
        $auditorPermissions = ['view orders', 'view inventory', 'manage inventory', 'view reports', 'export reports'];
        $adminPermissions = Permission::all()->pluck('name')->toArray();

        $cashierRole->syncPermissions($cashierPermissions);
        $kitchenRole->syncPermissions($kitchenPermissions);
        $auditorRole->syncPermissions($auditorPermissions);
        $adminRole->syncPermissions($adminPermissions);
    }
}
