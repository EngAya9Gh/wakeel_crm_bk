<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Stock\ProductStockResource;
use App\Models\Product;
use App\Services\Integrations\ERP\ErpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected ErpService $erpService
    ) {}

    /**
     * Get real-time stock for a product from ERP.
     * GET /api/v1/stock/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        try {
            // This pulls from ERP and updates the local cache
            $this->erpService->getProductStock($product);
            
            return $this->successResponse(
                new ProductStockResource($product->fresh()),
                'تم تحديث المخزون من نظام ERP بنجاح'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل الاتصال بنظام ERP: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Scan product by SKU or Barcode.
     * GET /api/v1/stock/scan?sku=...
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string',
        ]);

        $product = Product::where('sku', $validated['sku'])
            ->orWhere('erp_id', $validated['sku'])
            ->first();

        if (!$product) {
            return $this->errorResponse('المنتج غير موجود', 404);
        }

        return $this->show($product);
    }

    /**
     * Validate stock availability for a cart/order.
     * POST /api/v1/stock/validate
     * Body: { "items": [{ "sku": "...", "quantity": 5 }] }
     */
    public function validateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $unavailable = $this->erpService->checkStockAvailability($validated['items']);

            if (count($unavailable) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'بعض المنتجات غير متوفرة',
                    'unavailable_items' => $unavailable
                ], 200);
            }

            return $this->successResponse(null, 'جميع المنتجات متوفرة');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل التحقق من المخزون: ' . $e->getMessage(),
                500
            );
        }
    }
}
