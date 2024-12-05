<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

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
            'patient_id' => User::factory(),
            'appointment_date' => $this->faker->dateTimeBetween('2024-11-01', '2024-11-30')->format('Y-m-d'),
            'preference' => $this->faker->randomElement(['Morning', 'Afternoon']),
            'appointment_time' => $this->faker->time(),
            'status' => $this->faker->randomElement(['Pending',  'Cancelled', 'Accepted', 'Rejected', 'Missed', 'Ongoing', 'Completed']),
            'service' => $this->faker->randomElement(['Consultation', 'Extraction', 'Orthodontic']),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
