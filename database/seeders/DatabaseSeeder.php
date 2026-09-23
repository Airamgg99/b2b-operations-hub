<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Empresa Principal (Para acceso como administrador)
        $mainCompany = Company::factory()->create([
            'name' => 'Acme Corp (Admin)',
            'vat_number' => 'B12345678',
        ]);

        Subscription::factory()->create([
            'company_id' => $mainCompany->id,
            'plan_name' => 'Enterprise',
        ]);

        User::factory()->create([
            'name' => 'Admin Panel',
            'email' => 'admin@b2b.com',
            'password' => bcrypt('password'), // Contraseña universal para pruebas
            'company_id' => $mainCompany->id,
        ]);

        // 2. Generar 10 empresas sintéticas, cada una con 1 suscripción y 5 empleados
        Company::factory(10)
            ->has(Subscription::factory())
            ->has(User::factory()->count(5))
            ->create();
    }
}
