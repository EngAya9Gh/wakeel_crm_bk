<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class BulkStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
            'status_id' => 'required|exists:client_statuses,id',
        ];
    }
}
