<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'vat_number' => fake()->unique()->bothify('?########'), // Genera un formato tipo NIF/CIF falso
            'is_active' => true,
        ];
    }
}
