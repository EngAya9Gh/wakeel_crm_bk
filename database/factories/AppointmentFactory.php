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
        $start = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $end = (clone $start)->modify('+1 hour');

        return [
            'type' => $this->faker->randomElement(['call', 'meeting', 'visit']),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'start_at' => $start,
            'end_at' => $end,
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled', 'no_show']),
            'location' => $this->faker->address,
            // 'meeting_link' => $this->faker->url, // Column does not exist
            // client_id and user_id should be provided
        ];
    }
}
