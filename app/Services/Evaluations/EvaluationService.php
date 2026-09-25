<?php

declare(strict_types=1);

namespace App\Services\Evaluations;

use App\Models\Evaluation;
use App\Models\EvaluationLink;
use Illuminate\Support\Str;

class EvaluationService
{
    /**
     * Create a new evaluation (manual or via API/Widget).
     */
    public function createEvaluation(array $data): Evaluation
    {
        $evaluation = Evaluation::create($data);

        // Add to Client Timeline (Comments) if note exists
        if (!empty($data['notes']) && !empty($data['client_id'])) {
            \App\Models\Comment::create([
                'client_id' => $data['client_id'],
                'user_id' => $data['user_id'] ?? null,
                'type_id' => 1,
                'subject' => 'تقييم خدمة - ' . $data['rating'] . ' نجوم',
                'content' => $data['notes'],
            ]);
        }

        return $evaluation;
    }

    /**
     * Create a secure public link for a client to evaluate the service.
     */
    public function generateLink(array $data): EvaluationLink
    {
        $data['token'] = Str::random(64);
        
        // Default expiry is 7 days if not provided
        if (empty($data['expires_at'])) {
            $data['expires_at'] = now()->addDays(7);
        }

        return EvaluationLink::create($data);
    }

    /**
     * Find a valid, unused evaluation link by token.
     */
    public function findValidLinkByToken(string $token): ?EvaluationLink
    {
        return EvaluationLink::where('token', $token)
            ->whereNull('used_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Submit an evaluation using a public link token.
     */
    public function submitViaLink(EvaluationLink $link, array $data): Evaluation
    {
        // 1. Create the evaluation
        $evaluation = Evaluation::create([
            'tenant_id' => $link->tenant_id,
            'client_id' => $link->client_id,
            'assigned_user_id' => $link->assigned_user_id,
            'ticket_id' => $link->ticket_id,
            'rating' => $data['rating'],
            'notes' => $data['notes'] ?? null,
            'type' => $link->type ?? 'general',
            'channel' => 'link',
            'metadata' => $data['metadata'] ?? null,
        ]);

        // 2. Mark link as used
        $link->update([
            'used_at' => now(),
            'evaluation_id' => $evaluation->id,
        ]);
        
        // 3. Add to Client Timeline (Comments) if note exists
        if (!empty($data['notes']) && $link->client_id) {
            \App\Models\Comment::create([
                'client_id' => $link->client_id,
                'user_id' => $link->assigned_user_id ?? $link->created_by ?? 1, // Fallback to assigned user or admin
                'type_id' => 1, // Default comment type or we can create a special one
                'subject' => 'تقييم خدمة - ' . $data['rating'] . ' نجوم',
                'content' => $data['notes'],
            ]);
        }

        return $evaluation;
    }
}
