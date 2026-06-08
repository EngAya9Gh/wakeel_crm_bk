<?php

declare(strict_types=1);

namespace App\Services\Integrations\ERP\Drivers;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\Integrations\ERP\Contracts\ErpProviderInterface;
use App\Services\Integrations\ERP\Contracts\ErpSyncResult;

class MockErpDriver implements ErpProviderInterface
{
    public function getProductStock(string $identifier): int
    {
        // Simulate API call stock check
        return rand(5, 500); 
    }

    public function syncClient(Client $client): ErpSyncResult
    {
        // Simulate API call to create/update client
        $mockId = 'MOCK-CLI-' . ($client->id ?? rand(1000, 9999));
        
        return new ErpSyncResult(
            success: true,
            message: 'Client synced successfully (Mock)',
            erpId: $mockId,
            data: ['mock_response' => 'ok']
        );
    }

    public function syncInvoice(Invoice $invoice): ErpSyncResult
    {
        // Simulate API call to create invoice
        $mockId = 'MOCK-INV-' . ($invoice->invoice_number ?? rand(1000, 9999));

        return new ErpSyncResult(
            success: true,
            message: 'Invoice synced successfully (Mock)',
            erpId: $mockId,
            data: ['mock_status' => 'posted']
        );
    }

    public function checkStockAvailability(array $items): array
    {
        $unavailable = [];
        // Simulate checking stock availability for multiple items
        foreach ($items as $item) {
            $mockStock = rand(0, 100);
            if ($mockStock < $item['quantity']) {
                $unavailable[] = [
                    'sku' => $item['sku'],
                    'available' => $mockStock,
                    'requested' => $item['quantity'],
                    'message' => 'Insufficient stock (Mock)'
                ];
            }
        }
        return $unavailable;
    }
}
