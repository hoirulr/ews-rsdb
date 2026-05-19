<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'input-ews',
            'view-own-ews',
            'view-all-ews',
            'input-patient',
            'view-own-patient',
            'view-all-patient',
            'receive-ews-alert',
            'tangani-alert',
            'export-ews',
            'manage-user',
            'manage-faskes',
            'manage-role',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'puskesmas'])->syncPermissions([
            'input-ews',
            'view-own-ews',
            'input-patient',
            'view-own-patient',
        ]);

        Role::firstOrCreate(['name' => 'rs_perujuk'])->syncPermissions([
            'input-ews',
            'view-own-ews',
            'input-patient',
            'view-own-patient',
        ]);

        Role::firstOrCreate(['name' => 'admin_rsud'])->syncPermissions([
            'view-all-ews',
            'view-all-patient',
            'receive-ews-alert',
            'tangani-alert',
            'export-ews',
        ]);

        Role::firstOrCreate(['name' => 'admin_sistem'])->syncPermissions(Permission::all());
    }
}
