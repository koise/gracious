<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service' => $this->faker->unique()->randomElement(['Orthodontic', 'Diagnostic', 'Filling', 'Extraction', 'Root planing']),
            'with_authorization_form' => $this->faker->randomElement([0, 1]),
            'with_procedure_form' => $this->faker->randomElement([0, 1]),
        ];
    }
}
