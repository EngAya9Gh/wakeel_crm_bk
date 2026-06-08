<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Invoice;
use App\Services\Integrations\ERP\ErpService;
use Illuminate\Support\Facades\Log;

class PostInvoiceToErp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ErpService $erpService): void
    {
        try {
            $erpService->syncInvoice($this->invoice);
        } catch (\Exception $e) {
            Log::error('Background ERP Sync Failed for Invoice #' . $this->invoice->id . ': ' . $e->getMessage());
            
            // Retry the job next minute in case ERP was temporarily out of service
            $this->release(60); 
        }
    }
}
