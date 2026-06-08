<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user() || !$request->user()->hasPermission($permission)) {
            // Check if user is Super Admin (you might want to add a specific check for ID 1 or a specific role)
            // For now, strictly check permission
            
            // You can return a JSON response directly
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء'
            ], 403);
        }

        return $next($request);
    }
}
