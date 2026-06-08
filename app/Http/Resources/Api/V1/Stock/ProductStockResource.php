<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'erp_id' => $this->erp_id,
            'stock_quantity' => $this->stock_quantity,
            'last_synced_at' => $this->stock_last_synced_at,
            'is_stockable' => $this->is_stockable,
        ];
    }
}
