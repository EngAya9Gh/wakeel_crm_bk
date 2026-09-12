<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        
        if ($appointment && $appointment->status === 'completed') {
            if (!$this->user()->hasPermission('appointments.manage_completed')) {
                return false;
            }
        }
        
        return true;
    }

    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
