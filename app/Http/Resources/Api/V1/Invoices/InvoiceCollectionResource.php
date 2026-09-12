<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Invoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceCollectionResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'client_name' => $invoice->client?->name,
                'total' => number_format((float) $invoice->total, 2, '.', ''),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'items_count' => $invoice->items_count ?? $invoice->items->count(),
                'tags' => $invoice->relationLoaded('tags') ? $invoice->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ]) : [],
                'created_at' => $invoice->created_at->format('Y-m-d'),
            ]),
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
            ],
        ];
    }
}
