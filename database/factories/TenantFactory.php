<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'plan' => $this->faker->randomElement(['free', 'pro', 'enterprise']),
            'is_active' => true,
        ];
    }
}
