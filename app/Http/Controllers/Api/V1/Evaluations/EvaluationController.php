<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Evaluations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Models\Evaluation;
use App\Services\Evaluations\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluationService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Evaluation::with(['client:id,name,phone', 'user:id,name', 'assignedUser:id,name', 'ticket:id,ticket_number'])
            ->latest();

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        if ($request->has('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $evaluations = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $evaluations
        ]);
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1; // Or through TenantContext
        $data['user_id'] = auth()->id();
        
        if (empty($data['channel'])) {
            $data['channel'] = 'manual';
        }

        $evaluation = $this->evaluationService->createEvaluation($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التقييم بنجاح',
            'data' => $evaluation
        ], 201);
    }

    public function stats(Request $request): JsonResponse
    {
        $query = Evaluation::query();

        if ($request->has('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        $average = (float) ($query->avg('rating') ?? 0);
        $total = $query->count();
        $distribution = $query->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        return response()->json([
            'success' => true,
            'data' => [
                'average' => round($average, 1),
                'total' => $total,
                'distribution' => [
                    1 => $distribution[1] ?? 0,
                    2 => $distribution[2] ?? 0,
                    3 => $distribution[3] ?? 0,
                    4 => $distribution[4] ?? 0,
                    5 => $distribution[5] ?? 0,
                ]
            ]
        ]);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        $evaluation->load(['client:id,name,phone', 'user:id,name', 'assignedUser:id,name', 'ticket:id,ticket_number']);
        
        return response()->json([
            'success' => true,
            'data' => $evaluation
        ]);
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        $evaluation->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح'
        ]);
    }
}
