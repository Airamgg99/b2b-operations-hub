<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_name' => fake()->randomElement(['Basic', 'Pro', 'Enterprise']),
            'status' => 'active',
            'starts_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ends_at' => fake()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
