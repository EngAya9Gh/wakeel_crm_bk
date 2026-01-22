<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class AssignClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['user_id' => 'required|exists:users,id']; }
}
