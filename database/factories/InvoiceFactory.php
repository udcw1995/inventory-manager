<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'INV-' . $this->faker->unique()->randomNumber(5),
            'shop_id' => Shop::factory(),
            'issued_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total_cost' => $this->faker->randomFloat(2, 100, 1000),
            'fines_total' => $this->faker->randomFloat(2, 0, 50),
            'final_total' => $this->faker->randomFloat(2, 100, 1000),
            'total_items' => $this->faker->numberBetween(1, 50),
            'total_refillable' => $this->faker->numberBetween(0, 20),
            'total_non_refillable' => $this->faker->numberBetween(0, 30),
            'returned_refillable_total' => $this->faker->numberBetween(0, 10),
            'damaged_lost_total' => $this->faker->numberBetween(0, 5),
            'issued_by' => User::factory(),
        ];
    }
}
