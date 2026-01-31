<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy/Controller
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,cheque'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ الدفعة مطلوب',
            'amount.numeric' => 'مبلغ الدفعة يجب أن يكون رقماً',
            'amount.min' => 'مبلغ الدفعة يجب أن يكون أكبر من 0',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            'payment_date.required' => 'تاريخ الدفع مطلوب',
            'payment_date.date' => 'صيغة التاريخ غير صحيحة',
        ];
    }
}
