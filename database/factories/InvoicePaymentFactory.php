<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Invoice;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'card', 'cheque']),
            'payment_date' => $this->faker->date(),
            'reference' => strtoupper($this->faker->bothify('TRX-#####????')),
            'notes' => $this->faker->sentence(),
            'user_id' => User::inRandomOrder()->first()->id ?? 1,
            'invoice_id' => Invoice::factory(), // default, but usually overridden
        ];
    }
}
