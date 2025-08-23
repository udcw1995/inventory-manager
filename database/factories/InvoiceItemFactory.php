<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 50);
        $returnedEmpty = $this->faker->numberBetween(0, $qty);
        $damagedLost = $this->faker->numberBetween(0, $qty - $returnedEmpty);
        $lineFine = $this->faker->randomFloat(2, 0, 10);

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $qty * $unitPrice,
            'returned_empty' => $returnedEmpty,
            'damaged_lost' => $damagedLost,
            'line_fine' => $lineFine,
        ];
    }
}
