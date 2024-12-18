<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\City;
use App\Models\Province;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
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
            'number_verified' => fake()->randomElement([false, true]),
            'age' => fake()->numberBetween(18, 64), // Assuming age is a range
            'number' => '0' . fake()->unique()->numerify('##########'), // Philippine format example
            'street_address' => fake()->streetAddress(),
            'city_id' => 1,
            'province_id' => 1, // If it's for the Philippines, you may customize this
            'status' => fake()->randomElement(['Activated', 'Deactivated']),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }
    /**
     * Indicate that the model's email address should be unverified.
     */
}