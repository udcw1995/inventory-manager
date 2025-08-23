<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'owner_name' => $this->faker->name(),
            'owner_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'refillable_balance' => $this->faker->numberBetween(0, 100),
        ];
    }
}
