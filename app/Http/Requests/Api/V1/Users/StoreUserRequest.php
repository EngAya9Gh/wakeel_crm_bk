<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle via Policy
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
            'team_id' => ['nullable', 'exists:teams,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقاً',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'role_id.required' => 'يجب تحديد دور المستخدم',
            'role_id.exists' => 'الدور المحدد غير موجود',
            'team_id.exists' => 'الفريق المحدد غير موجود',
        ];
    }
}
