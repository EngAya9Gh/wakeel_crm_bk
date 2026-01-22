<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\Users\UserCollectionResource;
use App\Http\Resources\Api\V1\Users\UserResource;
use App\Services\Users\UserService;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'team_id', 'role_id', 'is_active']);
        $users = $this->userService->getUsers($filters);

        // We can use paginatedResponse manually or simply return the resource
        // Since UserCollectionResource might format structure differently, let's keep it but wrap in successResponse if needed.
        // But the trait paginatedResponse expects LengthAwarePaginator.
        // Let's stick to the current resource approach but if we want strictly unified responses we should wrap it.
        // For consistency with other controllers, let's assume UserCollectionResource already outputs the desired structure OR we wrap it.
        // Actually, the request was "Unified API Response". Resource Collections in Laravel output "data", "links", "meta".
        // Our ApiResponse trait's paginatedResponse puts "data" and "meta" inside a success envelope.
        // To be strictly consistent, we should use the trait.
        
        return $this->paginatedResponse($users, 'تم جلب المستخدمين بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return $this->createdResponse(new UserResource($user), 'تم إنشاء المستخدم بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->successResponse(new UserResource($user->load(['team', 'role'])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return $this->successResponse(new UserResource($updatedUser), 'تم تحديث بيانات المستخدم بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return $this->errorResponse('لا يمكن حذف الحساب الحالي');
        }

        $this->userService->deleteUser($user);

        return $this->deletedResponse('تم حذف المستخدم بنجاح');
    }
}
