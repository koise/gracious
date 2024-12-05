<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{

    protected static ?string $password;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'number' => '0' . substr(str_shuffle('123456789'), 0, 10),
            'role' => $this->faker->randomElement(['Admin', 'Doctor', 'Staff']),
            'status' => $this->faker->randomElement(['Activated', 'Deactivated']),
            'password' => static::$password ??= Hash::make('password')
        ];
    }
}
