<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
            'assigned_to' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:ticket_categories,id'],
            'sub_category_id' => ['nullable', 'exists:ticket_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,pending_client,resolved,closed'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,critical'],
            'source' => ['sometimes', 'string', 'in:manual,whatsapp,email,chat_widget,api,phone'],
        ];
    }
}
