<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'exists:clients,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:meeting,call,visit'],
            'status' => ['nullable', 'in:scheduled,completed,cancelled,no_show'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after:start_at'],
            'reminder_at' => ['nullable', 'date'],
        ];
    }
}
