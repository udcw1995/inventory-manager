<?php

namespace Database\Factories;

use App\Enums\BottleMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BottleMovement>
 */
class BottleMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(BottleMovementType::cases()),
            'quantity' => $this->faker->numberBetween(1, 100),
            'invoice_id' => Invoice::factory(),
            'occurred_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'meta' => [],
        ];
    }
}
