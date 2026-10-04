<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia la caché de Spatie antes de crear roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos base (opcional para un control más granular futuro)
        Permission::firstOrCreate(['name' => 'manage everything']);
        Permission::firstOrCreate(['name' => 'manage own company']);

        // 1. Super Admin: Acceso total al SaaS global
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo('manage everything');

        // 2. Company Admin: Administrador de una empresa cliente específica
        $companyAdmin = Role::firstOrCreate(['name' => 'Company Admin']);
        $companyAdmin->givePermissionTo('manage own company');

        // 3. User: Empleado estándar de una empresa cliente
        Role::firstOrCreate(['name' => 'User']);
    }
}
