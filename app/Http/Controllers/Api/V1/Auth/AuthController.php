<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    use \App\Traits\ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Access Token (Expires in 60 mins)
        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(60));
        
        // Refresh Token (Long lived, e.g. 30 days)
        $refreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addDays(30));

        return $this->successResponse([
            'user' => $user->load(['team', 'role']),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function refresh(Request $request)
    {
        $user = $request->user();

        if ($user->currentAccessToken()->name !== 'refresh_token') {
             return $this->forbiddenResponse('يجب استخدام Refresh Token صالح لتجديد الجلسة');
        }

        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(60));

        return $this->successResponse([
            'access_token' => $accessToken->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'تم تسجيل الخروج بنجاح');
    }

    public function me(Request $request)
    {
        return $this->successResponse($request->user()->load(['team', 'role.permissions']));
    }
}
