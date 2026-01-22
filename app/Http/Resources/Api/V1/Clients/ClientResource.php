<?php

namespace App\Http\Resources\Api\V1\Clients;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * مخصص لعرض العميل في الكرت (البيانات الأساسية فقط)
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            
            // Status with color
            'status' => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
                'color' => $this->status?->color,
            ],
            
            // Priority & Rating
            'priority' => $this->priority,
            'lead_rating' => $this->lead_rating,
            
            // Location (basic)
            'city' => $this->city?->name,
            'region' => $this->region?->name,
            
            // Assigned User (with avatar for display)
            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'avatar' => $this->assignedTo->avatar,
            ] : null,
            
            // Tags (for filtering/display)
            'tags' => $this->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ]),
            
            // Timestamps
            'first_contact_at' => $this->first_contact_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
