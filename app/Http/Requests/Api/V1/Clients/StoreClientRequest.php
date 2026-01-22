<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle via middleware/policies
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20|unique:clients,phone',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            
            'status_id' => 'required|exists:client_statuses,id',
            'priority' => 'required|in:high,medium,low',
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
        ];
    }
}
