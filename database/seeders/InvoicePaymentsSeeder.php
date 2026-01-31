<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;

class InvoicePaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();
        $user = User::first() ?? User::factory()->create();

        foreach ($invoices as $invoice) {
            // Skip draft or cancelled invoices from having payments for now, unless we want to simulate messy data.
            // But let's stick to logical consistency.
            if (in_array($invoice->status, ['draft', 'cancelled'])) {
                continue;
            }

            // Decide on payment scenario based on status
            if ($invoice->status === 'paid') {
                // Create one or two payments that sum up to total
                $this->createFullPayments($invoice, $user);
            } elseif ($invoice->status === 'partially_paid') {
                // Create payments that sum to less than total
                $this->createPartialPayments($invoice, $user);
            } elseif ($invoice->status === 'sent' || $invoice->status === 'overdue') {
                // Maybe add a partial payment to some 'sent' invoices and update their status to match reality?
                // Or leave them with 0 payments.
                // To demo functionality, let's pick some random 'sent' invoices and make them partially paid
                if (rand(0, 10) > 7) {
                    $this->createPartialPayments($invoice, $user);
                    $invoice->update(['status' => 'partially_paid']);
                }
            }
        }
    }

    protected function createFullPayments(Invoice $invoice, User $user)
    {
        $total = $invoice->total;
        
        // 50% chance of split payment
        if (rand(0, 1) === 1) {
            $amount1 = round($total / 2, 2);
            $amount2 = $total - $amount1;

            InvoicePayment::factory()->create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $amount1,
                'payment_date' => $invoice->created_at->addDays(rand(1, 5)),
                'notes' => 'دفعة أولى',
            ]);

            InvoicePayment::factory()->create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $amount2,
                'payment_date' => $invoice->created_at->addDays(rand(6, 15)),
                'notes' => 'دفعة ثانية',
            ]);
        } else {
            InvoicePayment::factory()->create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $total,
                'payment_date' => $invoice->paid_at ?? $invoice->created_at->addDays(rand(1, 10)),
                'notes' => 'سداد كامل',
            ]);
        }
    }

    protected function createPartialPayments(Invoice $invoice, User $user)
    {
        $total = $invoice->total;
        $amount = round($total * (rand(20, 70) / 100), 2); // 20% to 70% paid

        InvoicePayment::factory()->create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_date' => $invoice->created_at->addDays(rand(1, 5)),
            'notes' => 'دفعة جزئية',
        ]);
    }
}
