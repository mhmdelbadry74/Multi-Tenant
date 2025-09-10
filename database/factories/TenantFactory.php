<?php

namespace Database\Factories\System;

use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = strtolower(str_replace(' ', '-', $name));

        return [
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'db_name' => 'tenant_' . $slug . '_' . $this->faker->randomNumber(4),
            'db_user' => 'user_' . $slug . '_' . $this->faker->randomNumber(4),
            'db_pass' => $this->faker->password(12, 20),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
