<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'تمت العملية بنجاح',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Created response (201)
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'تم الإنشاء بنجاح'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Error response
     */
    protected function errorResponse(
        string $message = 'حدث خطأ',
        int $code = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Not found response (404)
     */
    protected function notFoundResponse(string $message = 'العنصر غير موجود'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized response (401)
     */
    protected function unauthorizedResponse(string $message = 'غير مصرح'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response (403)
     */
    protected function forbiddenResponse(string $message = 'غير مسموح'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Validation error response (422)
     */
    protected function validationErrorResponse(array $errors, string $message = 'خطأ في البيانات'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'تم جلب البيانات بنجاح'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Delete success response
     */
    protected function deletedResponse(string $message = 'تم الحذف بنجاح'): JsonResponse
    {
        return $this->successResponse(null, $message);
    }
}
