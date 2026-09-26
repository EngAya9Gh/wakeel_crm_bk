<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Models\ClientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalContactController extends Controller
{
    use \App\Traits\ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ClientContact::with('client:id,name,phone')
            ->latest();

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        if ($request->has('is_primary')) {
            $query->where('is_primary', filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate($request->integer('per_page', 15));

        return $this->successResponse($contacts);
    }
}
