<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Invoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->getPaymentMethodLabel($this->payment_method),
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name ?? 'Unknown',
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }

    protected function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'نقدي',
            'bank_transfer' => 'تحويل بنكي',
            'card' => 'بطاقة ائتمانية',
            'cheque' => 'شيك',
            default => $method,
        };
    }
}
