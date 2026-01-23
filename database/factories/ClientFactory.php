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
        $faker = \Faker\Factory::create('ar_SA');

        // Get random valid IDs (fallback to 1 if empty/testing)
        $regionId = \App\Models\Region::inRandomOrder()->value('id') ?? 1;
        $cityId = \App\Models\City::where('region_id', $regionId)->inRandomOrder()->value('id') ?? 1;

        return [
            'name' => 'العميل ' . $faker->name,
            'email' => $faker->unique()->safeEmail,
            'phone' => '05' . $faker->numberBetween(10000000, 99999999),
            'company' => $faker->boolean(40) ? $faker->company : null,
            'address' => $faker->address,
            'status_id' => \Illuminate\Support\Facades\DB::table('client_statuses')->inRandomOrder()->value('id') ?? 1,
            'priority' => $faker->randomElement(['high', 'medium', 'low']),
            'lead_rating' => $faker->randomElement(['hot', 'warm', 'cold']),
            'source_id' => \Illuminate\Support\Facades\DB::table('sources')->inRandomOrder()->value('id') ?? 1,
            'source_status' => $faker->randomElement(['valid', 'invalid']),
            // 'invalid_reason_id' => null, // Mostly valid
            'behavior_id' => \Illuminate\Support\Facades\DB::table('behaviors')->inRandomOrder()->value('id') ?? 1,
            'region_id' => $regionId,
            'city_id' => $cityId,
            'assigned_to' => null, // Will be assigned later or null
            'first_contact_at' => $faker->dateTimeBetween('-1 year', 'now'),
            'converted_at' => $faker->boolean(20) ? $faker->dateTimeBetween('-6 months', 'now') : null,
        ];
    }
}
