<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Invoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            
            'client' => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
            ]),
            
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'city' => $this->whenLoaded('city', fn() => [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ]),
            
            // Financial
            'subtotal' => number_format((float) $this->subtotal, 2, '.', ''),
            'tax_rate' => $this->tax_rate,
            'tax_amount' => number_format((float) $this->tax_amount, 2, '.', ''),
            'discount' => number_format((float) $this->discount, 2, '.', ''),
            'total' => number_format((float) $this->total, 2, '.', ''),
            'paid_amount' => number_format((float) $this->paid_amount, 2, '.', ''),
            'remaining_amount' => number_format((float) $this->remaining_amount, 2, '.', ''),
            
            // Dates
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i'),
            
            'notes' => $this->notes,
            
            // Items
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'discount' => number_format((float) $item->discount, 2, '.', ''),
                'total' => number_format((float) $item->total, 2, '.', ''),
            ])),
            
            // Tags
            'tags' => $this->whenLoaded('tags', fn() => $this->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])),
            
            'items_count' => $this->items_count ?? $this->items->count(),
            
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
            
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
