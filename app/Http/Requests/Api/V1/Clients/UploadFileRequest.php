<?php

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240', // 10MB
            'type' => 'required|in:contract,identity,document,image',
        ];
    }
}
