<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['status_id' => 'required|exists:client_statuses,id']; }
}
