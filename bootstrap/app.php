<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ], append: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);
        
        $middleware->alias([
            'api.key'      => \App\Http\Middleware\ValidateApiKey::class,
            'permission'   => \App\Http\Middleware\CheckPermission::class,
            'set.tenant'   => \App\Http\Middleware\SetTenantContext::class,
            'super.admin'  => \App\Http\Middleware\IsSuperAdmin::class,
        ]);

        // Properly handle redirects for unauthenticated and authenticated users
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('api/*')) return null;
            if ($request->is('super/*')) return route('super.login');
            return null; // For tenants or other web routes, adjust as needed
        });

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->user() && $request->user()->isSuperAdmin()) {
                return route('super.dashboard');
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
