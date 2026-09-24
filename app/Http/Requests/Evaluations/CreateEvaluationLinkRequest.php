<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

class CreateEvaluationLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assume standard middleware takes care of auth
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'channel' => ['required', 'string', 'in:link,whatsapp,email'],
            'note_for_client' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
