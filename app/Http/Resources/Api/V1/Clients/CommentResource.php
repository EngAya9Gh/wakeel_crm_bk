<?php

namespace App\Http\Resources\Api\V1\Clients;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * يتضمن صورة المستخدم كما طلب المستخدم
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type ? [
                'id' => $this->type->id,
                'name' => $this->type->name,
                'icon' => $this->type->icon,
                'color' => $this->type->color,
            ] : null,
            'subject' => $this->subject,
            'content' => $this->content,
            'outcome' => $this->outcome,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
            'mentions' => $this->mentions->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]),
            'attachments' => $this->attachments->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'url' => $f->url, // Uses accessor
                'type' => $f->type,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
