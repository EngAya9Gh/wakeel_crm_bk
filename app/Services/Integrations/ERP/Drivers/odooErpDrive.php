<?php

namespace App\Services\Integrations\ERP\Drivers;

use Illuminate\Support\Facades\Http;
use App\Services\Integrations\ERP\Contracts\ErpProviderInterface;
use App\Services\Integrations\ERP\Contracts\ErpSyncResult;
use App\Models\Invoice;
use App\Models\Client;

class OdooErpDriver implements ErpProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // قراءة الإعدادات من ملف config/erp.php
        $this->baseUrl = config('erp.url'); 
        $this->apiKey = config('erp.api_key');
    }

    // 1. الدالة الحقيقية لجلب المخزون
    public function getProductStock(string $sku): int
    {
        // اتصال حقيقي بـ Odoo API
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/api/product/stock", [
                'sku' => $sku
            ]);

        if ($response->successful()) {
            return $response->json('qty_available'); // ارجاع الكمية الحقيقية
        }

        return 0; // أو رمي استثناء Error
    }

    // 2. الدالة الحقيقية لإرسال الفاتورة
    public function syncInvoice(Invoice $invoice): ErpSyncResult
    {
        // تحضير بيانات الفاتورة لصيغة Odoo
        $payload = [
            'partner_id' => $invoice->client->erp_id, // ربط العميل
            'invoice_date' => $invoice->created_at->format('Y-m-d'),
            'invoice_line_ids' => $invoice->items->map(fn($item) => [
                'product_id' => $item->product->erp_id, // ربط المنتج
                'quantity' => $item->quantity,
                'price_unit' => $item->unit_price,
            ])->toArray(),
        ];

        // إرسال لـ Odoo
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/api/account.move", $payload);

        // إرجاع النتيجة
        return new ErpSyncResult(
            success: $response->successful(),
            message: $response->successful() ? 'تم الانشاء' : $response->body(),
            erpId: $response->json('id') // رقم الفاتورة في Odoo
        );
    }

    // 3. الدالة الحقيقية لمزامنة العميل (بنفس المنطق)
    public function syncClient(Client $client): ErpSyncResult
    {
        // ... نفس الفكرة: POST /api/res.partner
        return new ErpSyncResult(false, 'Not implemented');
    }

    public function checkStockAvailability(array $items): array
    {
        $unavailable = [];

        // استخراج أرقام SKUs لإرسالها في طلب واحد (تحسين الأداء - Batch Request)
        $skus = array_column($items, 'sku');

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/api/product/stock/batch", [
                'skus' => $skus
            ]);

        // نفترض أن الـ ERP يرجع { "SKU1": 50, "SKU2": 0 }
        $erpStocks = $response->successful() ? $response->json('stocks') : [];

        foreach ($items as $item) {
            $stock = $erpStocks[$item['sku']] ?? 0;
            
            if ($stock < $item['quantity']) {
                $unavailable[] = [
                    'sku' => $item['sku'],
                    'available' => $stock,
                    'requested' => $item['quantity'],
                    'message' => 'Insufficient stock'
                ];
            }
        }
        return $unavailable;
    }
}