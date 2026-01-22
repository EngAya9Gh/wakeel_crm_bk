<?php

namespace App\Http\Resources\Api\V1\Clients;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * مخصص لعرض تفاصيل العميل الكاملة (صفحة البروفايل)
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'address' => $this->address,
            
            // Status
            'status' => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
                'color' => $this->status?->color,
            ],
            
            // Classification
            'priority' => $this->priority,
            'lead_rating' => $this->lead_rating,
            'source_status' => $this->source_status,
            
            // Relations
            'source' => $this->source ? ['id' => $this->source->id, 'name' => $this->source->name] : null,
            'behavior' => $this->behavior ? ['id' => $this->behavior->id, 'name' => $this->behavior->name, 'color' => $this->behavior->color] : null,
            'invalid_reason' => $this->invalidReason ? ['id' => $this->invalidReason->id, 'name' => $this->invalidReason->name] : null,
            'region' => $this->region ? ['id' => $this->region->id, 'name' => $this->region->name] : null,
            'city' => $this->city ? ['id' => $this->city->id, 'name' => $this->city->name] : null,
            
            // Assigned User
            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'avatar' => $this->assignedTo->avatar,
                'email' => $this->assignedTo->email,
            ] : null,
            
            // Tags
            'tags' => $this->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ]),
            
            // Additional Info
            'exclusion_reason' => $this->exclusion_reason,
            'first_contact_at' => $this->first_contact_at?->format('Y-m-d H:i'),
            'converted_at' => $this->converted_at?->format('Y-m-d H:i'),
            
            // Counts
            'comments_count' => $this->comments_count ?? $this->comments->count(),
            'files_count' => $this->files_count ?? $this->files->count(),
            'invoices_count' => $this->invoices_count ?? $this->invoices->count(),
            'appointments_count' => $this->appointments_count ?? $this->appointments->count(),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
