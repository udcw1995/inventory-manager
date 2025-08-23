<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grn>
 */
class GrnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'GRN-' . $this->faker->unique()->randomNumber(5),
            'delivery_person_name' => $this->faker->name(),
            'delivery_person_contact' => $this->faker->phoneNumber(),
            'vehicle_no' => $this->faker->regexify('[A-Z]{3}[0-9]{4}'),
            'delivered_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total_cost' => $this->faker->randomFloat(2, 100, 1000),
            'total_items' => $this->faker->numberBetween(1, 50),
            'total_refillable' => $this->faker->numberBetween(0, 20),
            'total_non_refillable' => $this->faker->numberBetween(0, 30),
            'created_by' => User::factory(),
        ];
    }
}
