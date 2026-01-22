<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'city_id' => ['nullable', 'exists:cities,id'], // Added city_id
            'status' => ['nullable', 'in:draft,sent,paid,overdue,cancelled'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            
            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            
            // Tags
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:invoice_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'يجب تحديد العميل',
            'client_id.exists' => 'العميل غير موجود',
            'items.required' => 'يجب إضافة عنصر واحد على الأقل',
            'items.min' => 'يجب إضافة عنصر واحد على الأقل',
            'items.*.description.required' => 'وصف العنصر مطلوب',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
        ];
    }
}
