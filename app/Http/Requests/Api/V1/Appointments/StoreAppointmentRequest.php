<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:meeting,call,visit'],
            'status' => ['nullable', 'in:scheduled,completed,cancelled,no_show'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reminder_at' => ['nullable', 'date', 'before:start_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'يجب تحديد العميل',
            'title.required' => 'عنوان الموعد مطلوب',
            'type.required' => 'نوع الموعد مطلوب',
            'start_at.required' => 'وقت البداية مطلوب',
            'end_at.required' => 'وقت النهاية مطلوب',
            'end_at.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ];
    }
}
