<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['client:id,name,phone,email', 'user:id,name', 'city:id,name', 'items', 'tags'])
            ->withCount('items');

        // Apply Filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        
        if (!empty($filters['city_id'])) { // Added filter by city
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('client', function ($cq) use ($filters) {
                      $cq->where('name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(int $id): Invoice
    {
        return Invoice::with(['client', 'user', 'city', 'items.product', 'tags'])
            ->findOrFail($id);
    }

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            $tags = $data['tags'] ?? [];
            unset($data['items'], $data['tags']);

            // Generate invoice number
            $data['invoice_number'] = $this->generateInvoiceNumber();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += $itemTotal;
            }
            
            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $subtotal * (($data['tax_rate'] ?? 0) / 100);
            $data['total'] = $subtotal + $data['tax_amount'] - ($data['discount'] ?? 0);

            $invoice = Invoice::create($data);

            // Create items
            foreach ($items as $item) {
                $item['invoice_id'] = $invoice->id;
                $item['total'] = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $invoice->items()->create($item);
            }

            // Sync tags
            if (!empty($tags)) {
                $invoice->tags()->sync($tags);
            }

            return $invoice->load(['client', 'user', 'items.product', 'tags']);
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $items = $data['items'] ?? null;
            $tags = $data['tags'] ?? null;
            unset($data['items'], $data['tags']);

            // Recalculate if items provided
            if ($items !== null) {
                $invoice->items()->delete();
                
                $subtotal = 0;
                foreach ($items as $item) {
                    $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                    $subtotal += $itemTotal;
                    
                    $item['invoice_id'] = $invoice->id;
                    $item['total'] = $itemTotal;
                    $invoice->items()->create($item);
                }
                
                $data['subtotal'] = $subtotal;
                $data['tax_amount'] = $subtotal * (($data['tax_rate'] ?? $invoice->tax_rate) / 100);
                $data['total'] = $subtotal + $data['tax_amount'] - ($data['discount'] ?? $invoice->discount);
            }

            $invoice->update($data);

            if ($tags !== null) {
                $invoice->tags()->sync($tags);
            }

            return $invoice->load(['client', 'user', 'items.product', 'tags']);
        });
    }

    public function delete(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            return $invoice->delete();
        });
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = now()->format('Y');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -5)) + 1 : 1;
        
        return $prefix . $year . '-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
