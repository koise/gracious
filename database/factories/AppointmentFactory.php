<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => $this->faker->numberBetween(1, 50),
            'appointment_date' => $this->faker->dateTimeBetween('2024-12-01', '2024-12-30')->format('Y-m-d'),
            'preference' => $this->faker->randomElement(['Morning', 'Afternoon']),
            'appointment_time' => $this->faker->time(),
            'status' => $this->faker->randomElement(['Pending',  'Cancelled', 'Accepted', 'Rejected', 'Missed', 'Ongoing', 'Completed']),
            'procedures' => $this->faker->randomElement(['Extraction', 'Root planing', 'Orthodontic Treatment', 'Diagnostic', 'Filling']),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
