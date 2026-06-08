<?php

declare(strict_types=1);

namespace App\Services\Integrations\ERP;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ErpSyncLog;
use App\Services\Integrations\ERP\Contracts\ErpProviderInterface;
use App\Services\Integrations\ERP\Contracts\ErpSyncResult;

class ErpService
{
    public function __construct(
        protected ErpProviderInterface $provider
    ) {}

    /**
     * Get real-time stock from ERP and update local cache.
     */
    public function getProductStock(Product $product): int
    {
        $identifier = $product->sku ?? $product->erp_id ?? (string) $product->id;
        
        $quantity = $this->provider->getProductStock($identifier);

        // Update local cache
        $product->update([
            'stock_quantity' => $quantity,
            'stock_last_synced_at' => now(),
        ]);

        return $quantity;
    }

    /**
     * Sync Client to ERP.
     */
    public function syncClient(Client $client): ErpSyncResult
    {
        $result = $this->provider->syncClient($client);

        if ($result->success && $result->erpId) {
            $client->forceFill([
                'erp_id' => $result->erpId,
                'erp_synced_at' => now(),
            ])->saveQuietly(); // Use saveQuietly to avoid triggering observers if any
        }

        $this->log('Client', $client->id, 'sync_client', $result);

        return $result;
    }

    /**
     * Sync Invoice to ERP.
     */
    public function syncInvoice(Invoice $invoice): ErpSyncResult
    {
        $result = $this->provider->syncInvoice($invoice);

        if ($result->success && $result->erpId) {
            $invoice->forceFill([
                'erp_id' => $result->erpId,
                'erp_synced_at' => now(),
                'erp_sync_status' => 'synced',
            ])->saveQuietly();
        } else {
            $invoice->forceFill([
                'erp_sync_status' => 'failed',
            ])->saveQuietly();
        }

        $this->log('Invoice', $invoice->id, 'push_invoice', $result);

        return $result;
    }

    protected function log(string $type, int $id, string $action, ErpSyncResult $result): void
    {
        ErpSyncLog::create([
            'entity_type' => $type,
            'entity_id' => $id,
            'action' => $action,
            'status' => $result->success ? 'success' : 'failed',
            'response_payload' => $result->data ?? [],
            'error_message' => $result->message,
        ]);
    }

    /**
     * Check stock availability for multiple items.
     * @param array $items [['sku' => '...', 'quantity' => 10]]
     * @return array List of unavailable items
     */
    public function checkStockAvailability(array $items): array
    {
        return $this->provider->checkStockAvailability($items);
    }
}
