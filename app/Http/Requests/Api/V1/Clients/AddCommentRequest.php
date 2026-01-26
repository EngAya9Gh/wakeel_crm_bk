<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'type_id' => 'required|exists:comment_types,id',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'outcome' => 'nullable|in:positive,neutral,negative',
            'next_follow_up' => 'nullable|date',
            'mentions' => 'nullable|array',
            'mentions.*' => 'exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120', // 5MB max each
        ];
    }
}
