<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('id');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|required|string|max:20|unique:clients,phone,' . $clientId,
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            
            'status_id' => 'sometimes|required|exists:client_statuses,id',
            'priority' => 'sometimes|required|in:high,medium,low',
            'lead_rating' => 'nullable|in:hot,warm,cold',
            
            'source_id' => 'nullable|exists:sources,id',
            'source_status' => 'nullable|in:valid,invalid',
            'invalid_reason_id' => 'nullable|exists:invalid_reasons,id',
            'behavior_id' => 'nullable|exists:behaviors,id',
            
            'region_id' => 'nullable|exists:regions,id',
            'city_id' => 'nullable|exists:cities,id',
            
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            
            'exclusion_reason' => 'nullable|string',
            'converted_at' => 'nullable|date',
        ];
    }
}
