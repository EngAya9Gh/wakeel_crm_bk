<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\PublicSubmitEvaluationRequest;
use App\Services\Evaluations\EvaluationService;
use Illuminate\Http\JsonResponse;

class EvaluationPublicController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluationService) {}

    public function show(string $token): JsonResponse
    {
        $link = $this->evaluationService->findValidLinkByToken($token);

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرابط غير صالح أو منتهي الصلاحية.'
            ], 404);
        }

        // Return safe public data
        return response()->json([
            'success' => true,
            'data' => [
                'tenant_name' => $link->tenant->name ?? 'الشركة',
                'assigned_user_name' => $link->assignedUser->name ?? null,
                'client_name' => $link->client->name ?? null,
                'note_for_client' => $link->note_for_client
            ]
        ]);
    }

    public function submit(PublicSubmitEvaluationRequest $request, string $token): JsonResponse
    {
        $link = $this->evaluationService->findValidLinkByToken($token);

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرابط غير صالح أو منتهي الصلاحية.'
            ], 404);
        }

        $data = $request->validated();
        $data['metadata'] = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        $evaluation = $this->evaluationService->submitViaLink($link, $data);

        return response()->json([
            'success' => true,
            'message' => 'شكراً لك! تم إرسال تقييمك بنجاح.',
            'data' => $evaluation
        ]);
    }
}
