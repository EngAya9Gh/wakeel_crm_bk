<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Public endpoint - no user authentication required
     * API Key validation is handled by middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Required fields
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'phone' => [
                'required',
                'string',
                'regex:/^(\+966|00966|966|05)[0-9]{8,9}$/', // Saudi phone format
                // Note: Duplicate phone numbers are allowed - customers can register multiple times
            ],
            
            // Optional fields
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            
            // Form-specific fields
            'subject' => ['nullable', 'string', 'max:255'], // For contact form
            'message' => ['nullable', 'string', 'max:2000'], // For contact form
            
            // Source identification (required)
            'source' => [
                'required',
                'string',
                'in:contact_form,landing_page,website_form'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم الكامل مطلوب',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
            
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.regex' => 'رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 أو +966',
            
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرف',
            
            'company.max' => 'اسم الشركة يجب ألا يتجاوز 255 حرف',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرف',
            
            'subject.max' => 'الموضوع يجب ألا يتجاوز 255 حرف',
            'message.max' => 'الرسالة يجب ألا تتجاوز 2000 حرف',
            
            'source.required' => 'مصدر النموذج مطلوب',
            'source.in' => 'مصدر النموذج غير صحيح',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors()
            ], 422)
        );
    }
    
    /**
     * Prepare the data for validation.
     * 
     * Normalize phone number format
     */
    protected function prepareForValidation()
    {
        if ($this->has('phone')) {
            $phone = $this->phone;
            
            // Remove spaces, dashes, and parentheses
            $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
            
            // Normalize to +966 format
            if (str_starts_with($phone, '00966')) {
                $phone = '+966' . substr($phone, 5);
            } elseif (str_starts_with($phone, '966')) {
                $phone = '+966' . substr($phone, 3);
            } elseif (str_starts_with($phone, '05')) {
                $phone = '+966' . substr($phone, 1);
            }
            
            $this->merge(['phone' => $phone]);
        }
    }
}
