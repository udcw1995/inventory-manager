<?php

namespace Database\Factories;

use App\Models\Grn;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GrnItem>
 */
class GrnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 10);
        $unitCost = $this->faker->randomFloat(2, 5, 50);

        return [
            'grn_id' => Grn::factory(),
            'product_id' => Product::factory(),
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'line_total' => $qty * $unitCost,
        ];
    }
}
