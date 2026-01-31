<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Add a new payment to an invoice.
     */
    public function addPayment(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Validate amount
            if ($data['amount'] > $invoice->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => ['مبلغ الدفعة أكبر من المبلغ المتبقي للفاتورة (' . $invoice->remaining_amount . ')'],
                ]);
            }

            $payment = $invoice->payments()->create($data);

            $this->updateInvoiceStatus($invoice);

            return $payment;
        });
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(InvoicePayment $payment, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($payment, $data) {
            // Calculate hypothetical remaining amount if we revert this payment first
            $invoice = $payment->invoice;
            $currentRemaining = $invoice->total - ($invoice->paid_amount - $payment->amount);
            
            if (isset($data['amount']) && $data['amount'] > $currentRemaining) {
                 throw ValidationException::withMessages([
                    'amount' => ['مبلغ الدفعة أكبر من المبلغ المتبقي للفاتورة (' . $currentRemaining . ')'],
                ]);
            }

            $payment->update($data);

            $this->updateInvoiceStatus($invoice->fresh());

            return $payment;
        });
    }

    /**
     * Delete a payment.
     */
    public function deletePayment(InvoicePayment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            
            $deleted = $payment->delete();

            $this->updateInvoiceStatus($invoice->fresh());

            return $deleted ?? false;
        });
    }

    /**
     * Update invoice status based on paid amount.
     */
    protected function updateInvoiceStatus(Invoice $invoice): void
    {
        $total = $invoice->total;
        $paid = $invoice->payments()->sum('amount'); // Recalculate directly from DB to be safe

        if ($paid >= $total) {
            $status = 'paid';
            $invoice->paid_at = $invoice->paid_at ?? now();
        } elseif ($paid > 0) {
            $status = 'partially_paid';
            // Keep paid_at null if not fully paid? Or maybe we don't change paid_at
             if ($invoice->paid_at && $paid < $total) {
                $invoice->paid_at = null; // Reset if it was paid but now is partial (e.g. payment deleted)
            }
        } else {
            // Revert to sent or draft? Usually if it has payments it was likely sent.
            // If it was overdue, it might still be overdue. 
            // Valid statuses: draft, sent, overdue, cancelled.
            // We'll default to 'sent' if it was paid/partial, preserving 'overdue' if applicable is logic heavy.
            // Simplest logic: If it was 'paid' or 'partially_paid', revert to 'sent'. 
            // If it was 'overdue', keep it.
            
            if (in_array($invoice->status, ['paid', 'partially_paid'])) {
                $status = 'sent';
                 $invoice->paid_at = null;
            } else {
                $status = $invoice->status; // Keep existing (e.g. overdue)
            }
        }

        $invoice->status = $status;
        $invoice->save();
    }
}
