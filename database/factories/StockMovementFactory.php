<?php

namespace Database\Factories;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementSourceType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'direction' => $this->faker->randomElement(StockMovementDirection::cases()),
            'quantity' => $this->faker->numberBetween(1, 100),
            'value' => $this->faker->randomFloat(2, 1, 100),
            'source_type' => $this->faker->randomElement(StockMovementSourceType::cases()),
            'source_id' => $this->faker->randomNumber(5),
            'occurred_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'reversal_of_id' => null,
            'meta' => [],
        ];
    }
}
