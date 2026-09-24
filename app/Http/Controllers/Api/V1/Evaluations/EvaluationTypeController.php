<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Evaluations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationTypeRequest;
use App\Http\Requests\Evaluations\UpdateEvaluationTypeRequest;
use App\Models\EvaluationType;
use Illuminate\Http\JsonResponse;

class EvaluationTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = EvaluationType::all();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(StoreEvaluationTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;

        $type = EvaluationType::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء نوع التقييم بنجاح',
            'data' => $type
        ], 201);
    }

    public function show(EvaluationType $evaluationType): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $evaluationType
        ]);
    }

    public function update(UpdateEvaluationTypeRequest $request, EvaluationType $evaluationType): JsonResponse
    {
        $evaluationType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث نوع التقييم بنجاح',
            'data' => $evaluationType->fresh()
        ]);
    }

    public function destroy(EvaluationType $evaluationType): JsonResponse
    {
        if ($evaluationType->evaluations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف نوع التقييم لارتباطه بتقييمات موجودة'
            ], 400);
        }

        $evaluationType->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم الحذف بنجاح'
        ]);
    }
}
