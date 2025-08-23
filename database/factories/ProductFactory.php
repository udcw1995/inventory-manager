<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'flavor' => $this->faker->word(),
            'is_refillable' => $this->faker->boolean(),
            'purchase_cost' => $this->faker->randomFloat(2, 10, 100),
            'selling_price' => $this->faker->randomFloat(2, 100, 200),
            'fine_per_damaged' => null,
            'active' => $this->faker->boolean(),
        ];
    }

    public function refillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_refillable' => true,
            'fine_per_damaged' => $this->faker->randomFloat(2, 1, 10),
        ]);
    }
}
