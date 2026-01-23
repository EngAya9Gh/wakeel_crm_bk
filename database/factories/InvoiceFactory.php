<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        $status = $faker->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']);
        $subtotal = $faker->randomFloat(2, 500, 10000);
        $tax = $subtotal * 0.15;
        $total = $subtotal + $tax;

        return [
            'invoice_number' => 'INV-' . $faker->unique()->numberBetween(10000, 99999),
            'subtotal' => $subtotal,
            'tax_rate' => 15.00,
            'tax_amount' => $tax,
            'discount' => 0,
            'total' => $total,
            'status' => $status,
            'due_date' => $faker->dateTimeBetween('now', '+30 days'),
            'paid_at' => $status === 'paid' ? $faker->dateTimeBetween('-1 month', 'now') : null,
            'notes' => $faker->sentence,
            'city_id' => 1,
            // client_id and user_id should be provided when creating
        ];
    }
}
