<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random valid IDs (fallback to 1 if empty/testing)
        $regionId = \App\Models\Region::inRandomOrder()->value('id') ?? 1;
        $cityId = \App\Models\City::where('region_id', $regionId)->inRandomOrder()->value('id') ?? 1;

        return [
            'name' => 'العميل ' . $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '05' . $this->faker->numberBetween(10000000, 99999999),
            'company' => $this->faker->boolean(40) ? $this->faker->company : null,
            'address' => $this->faker->address,
            'status_id' => \Illuminate\Support\Facades\DB::table('client_statuses')->inRandomOrder()->value('id') ?? 1,
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'lead_rating' => $this->faker->randomElement(['hot', 'warm', 'cold']),
            'source_id' => \Illuminate\Support\Facades\DB::table('sources')->inRandomOrder()->value('id') ?? 1,
            'source_status' => $this->faker->randomElement(['valid', 'invalid']),
            // 'invalid_reason_id' => null, // Mostly valid
            'behavior_id' => \Illuminate\Support\Facades\DB::table('behaviors')->inRandomOrder()->value('id') ?? 1,
            'region_id' => $regionId,
            'city_id' => $cityId,
            'assigned_to' => null, // Will be assigned later or null
            'first_contact_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'converted_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
        ];
    }
}
