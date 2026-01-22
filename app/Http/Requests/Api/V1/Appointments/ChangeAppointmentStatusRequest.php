<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class ChangeAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:scheduled,completed,cancelled,no_show'],
        ];
    }
}
