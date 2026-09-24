<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Evaluations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\CreateEvaluationLinkRequest;
use App\Models\EvaluationLink;
use App\Services\Evaluations\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvaluationLinkController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluationService) {}

    public function index(Request $request): JsonResponse
    {
        $links = EvaluationLink::with(['client:id,name,phone', 'assignedUser:id,name', 'ticket:id,ticket_number'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $links
        ]);
    }

    public function create(CreateEvaluationLinkRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;

        $link = $this->evaluationService->generateLink($data);

        // TODO: Fire an event or call Notification/WhatsApp service to dispatch the link to the client
        // Example: event(new EvaluationLinkCreated($link));

        $publicUrl = config('app.url') . '/rate/' . $link->token;

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء رابط التقييم بنجاح',
            'data' => [
                'link' => $link,
                'public_url' => $publicUrl
            ]
        ], 201);
    }
}
