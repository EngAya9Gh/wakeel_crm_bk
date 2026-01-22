<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Appointments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AppointmentCollectionResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($appointment) => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'type' => $appointment->type,
                'status' => $appointment->status,
                'client_name' => $appointment->client?->name,
                'start_at' => $appointment->start_at?->format('Y-m-d H:i'),
                'end_at' => $appointment->end_at?->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
            ],
        ];
    }
}
