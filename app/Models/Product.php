<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sku',
        'unit_price',
        'unit',
        'is_active',
        'erp_id',
        'stock_quantity',
        'stock_last_synced_at',
        'is_stockable',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_stockable' => 'boolean',
        'stock_last_synced_at' => 'datetime',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the current stock (cached from ERP).
     */
    public function getStockAttribute(): int
    {
        return $this->stock_quantity ?? 0;
    }
}
