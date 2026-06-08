<?php

declare(strict_types=1);

namespace App\Services\Integrations\ERP\Contracts;

use App\Models\Invoice;
use App\Models\Client;

interface ErpProviderInterface
{
    /**
     * Get stock quantity for a product by SKU/ID.
     */
    public function getProductStock(string $identifier): int;

    /**
     * Sync client data to ERP.
     */
    public function syncClient(Client $client): ErpSyncResult;

    /**
     * Sync invoice data to ERP.
     */
    public function syncInvoice(Invoice $invoice): ErpSyncResult;

    /**
     * Check stock availability for multiple items.
     * Returns list of unavailable items with reasons.
     * Input: [['sku' => '...', 'quantity' => 5], ...]
     * Output: [['sku' => '...', 'available' => 2, 'requested' => 5], ...]
     */
    public function checkStockAvailability(array $items): array;
}
