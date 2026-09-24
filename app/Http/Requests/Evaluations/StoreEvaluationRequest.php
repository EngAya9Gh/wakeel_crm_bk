<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'channel' => ['nullable', 'string', 'in:manual,whatsapp,chat_widget,api'],
        ];
    }
}
