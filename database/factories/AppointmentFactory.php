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
        $faker = \Faker\Factory::create();
        $start = $faker->dateTimeBetween('-1 month', '+2 months');
        $end = (clone $start)->modify('+1 hour');

        return [
            'type' => $faker->randomElement(['call', 'meeting', 'visit']),
            'title' => $faker->sentence(3),
            'description' => $faker->paragraph,
            'start_at' => $start,
            'end_at' => $end,
            'status' => $faker->randomElement(['scheduled', 'completed', 'cancelled', 'no_show']),
            'location' => $faker->address,
            // client_id and user_id should be provided
        ];
    }
}
