<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketCategoryRequest;
use App\Http\Requests\Tickets\UpdateTicketCategoryRequest;
use App\Models\TicketCategory;
use Illuminate\Http\JsonResponse;

class TicketCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TicketCategory::all();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function store(StoreTicketCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;

        $category = TicketCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة تصنيف التذكرة بنجاح',
            'data' => $category
        ], 201);
    }

    public function show(TicketCategory $ticketCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $ticketCategory
        ]);
    }

    public function update(UpdateTicketCategoryRequest $request, TicketCategory $ticketCategory): JsonResponse
    {
        $ticketCategory->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث تصنيف التذكرة بنجاح',
            'data' => $ticketCategory->fresh()
        ]);
    }

    public function destroy(TicketCategory $ticketCategory): JsonResponse
    {
        // Don't delete if it has tickets
        if ($ticketCategory->tickets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذا التصنيف لارتباطه بتذاكر موجودة'
            ], 400);
        }

        $ticketCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف تصنيف التذكرة بنجاح'
        ]);
    }
}
