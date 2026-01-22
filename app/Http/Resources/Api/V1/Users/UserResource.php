<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'is_active' => $this->is_active,
            
            'team' => $this->whenLoaded('team', fn() => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'category' => $this->team->category,
            ]),
            
            'role' => $this->whenLoaded('role', fn() => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ]),
            
            'permissions' => $this->when(
                $this->relationLoaded('role') && $this->role?->relationLoaded('permissions'),
                fn() => $this->role->permissions->pluck('name')
            ),
            
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
