<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;

// =====================================================================
// PUBLIC API ROUTES (No User Authentication - API Key Only)
// =====================================================================
Route::prefix('public/v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    // Lead Submission from Website Forms
    // Rate Limit: 60 requests per minute per API key
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});

// =====================================================================
// AUTHENTICATED API ROUTES (Sanctum Authentication Required)
// =====================================================================
Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']); // User must provide Refresh Token
        Route::get('auth/me', [AuthController::class, 'me']);
        
        // Clients Module
        Route::prefix('clients')->group(function () {
            // Stats & KPIs
            Route::get('stats', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'stats']);
            Route::get('kpis', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'kpis']);

            // Filters
            Route::get('filters', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getFilters']);
            Route::post('filters', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'storeFilter']);
            Route::delete('filters/{id}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'deleteFilter']);

            // Export & PDF
            Route::get('export', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'export']);
            Route::get('{id}/pdf', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'downloadPdf']);
            
            // Bulk Operations
            Route::post('bulk/status', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'bulkStatus']);
            Route::post('bulk/assign', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'bulkAssign']);
            Route::delete('bulk', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'bulkDelete']);
            
            // CRUD
            Route::get('/', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'store']);
            Route::get('{id}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'show']);
            Route::put('{id}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'update']);
            Route::delete('{id}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'destroy']);
            Route::post('{id}/restore', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'restore']);
            
            // Status & Assignment
            Route::patch('{id}/status', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'changeStatus']);
            Route::patch('{id}/assign', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'assign']);
            
            // Comments
            Route::get('{id}/comments', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getComments']);
            Route::post('{id}/comments', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'addComment']);
            
            // Files
            Route::get('{id}/files', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getFiles']);
            Route::post('{id}/files', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'uploadFile']);
            
            // Timeline
            Route::get('{id}/timeline', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getTimeline']);

            // Invoices & Appointments
            Route::get('{id}/invoices', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getInvoices']);
            Route::get('{id}/appointments', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getAppointments']);

            // Procedures (Tasks)
            Route::get('{id}/procedures', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'getProcedures']);
            Route::post('{id}/procedures', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'addProcedure']);
            Route::put('{clientId}/procedures/{procedureId}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'updateProcedure']);
            Route::delete('{clientId}/procedures/{procedureId}', [\App\Http\Controllers\Api\V1\Clients\ClientController::class, 'deleteProcedure']);
        });

        // Users Module
        Route::apiResource('users', \App\Http\Controllers\Api\V1\Users\UserController::class);
        
        // Invoices Module
        Route::prefix('invoices')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'store']);
            Route::get('{invoice}', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'show']);
            Route::put('{invoice}', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'update']);
            Route::delete('{invoice}', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'destroy']);
            Route::patch('{invoice}/status', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'changeStatus']);
            Route::get('{invoice}/pdf', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'downloadPdf']);
            Route::post('{invoice}/send', [\App\Http\Controllers\Api\V1\Invoices\InvoiceController::class, 'sendToClient']);
        });
        
        // Appointments Module
        Route::prefix('appointments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'store']);
            Route::get('{appointment}', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'show']);
            Route::put('{appointment}', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'update']);
            Route::delete('{appointment}', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'destroy']);
            Route::patch('{appointment}/status', [\App\Http\Controllers\Api\V1\Appointments\AppointmentController::class, 'changeStatus']);
        });
        
        // Settings Module
        Route::prefix('settings')->group(function () {
            Route::apiResource('statuses', \App\Http\Controllers\Api\V1\Settings\SettingsController::class . '@statuses'); // wait, apiResource is better if I use a controller for each, but I'll stick to the current naming
            
            // Manual routes for SettingsController
            $s = [\App\Http\Controllers\Api\V1\Settings\SettingsController::class];
            
            Route::get('statuses', [$s[0], 'getStatuses']);
            Route::post('statuses', [$s[0], 'storeStatus']);
            Route::put('statuses/{id}', [$s[0], 'updateStatus']);
            Route::delete('statuses/{id}', [$s[0], 'deleteStatus']);
            
            Route::get('sources', [$s[0], 'getSources']);
            Route::post('sources', [$s[0], 'storeSource']);
            Route::put('sources/{id}', [$s[0], 'updateSource']);
            Route::delete('sources/{id}', [$s[0], 'deleteSource']);

            Route::get('behaviors', [$s[0], 'getBehaviors']);
            Route::post('behaviors', [$s[0], 'storeBehavior']);
            Route::put('behaviors/{id}', [$s[0], 'updateBehavior']);
            Route::delete('behaviors/{id}', [$s[0], 'deleteBehavior']);

            Route::get('invalid-reasons', [$s[0], 'getInvalidReasons']);
            Route::post('invalid-reasons', [$s[0], 'storeInvalidReason']);
            Route::put('invalid-reasons/{id}', [$s[0], 'updateInvalidReason']);
            Route::delete('invalid-reasons/{id}', [$s[0], 'deleteInvalidReason']);

            Route::get('regions', [$s[0], 'getRegions']);
            Route::post('regions', [$s[0], 'storeRegion']);
            Route::put('regions/{id}', [$s[0], 'updateRegion']);
            Route::delete('regions/{id}', [$s[0], 'deleteRegion']);

            Route::get('cities', [$s[0], 'getCities']);
            Route::post('cities', [$s[0], 'storeCity']);
            Route::put('cities/{id}', [$s[0], 'updateCity']);
            Route::delete('cities/{id}', [$s[0], 'deleteCity']);

            Route::get('tags', [$s[0], 'getTags']);
            Route::post('tags', [$s[0], 'storeTag']);
            Route::put('tags/{id}', [$s[0], 'updateTag']);
            Route::delete('tags/{id}', [$s[0], 'deleteTag']);

            Route::get('products', [$s[0], 'getProducts']);
            Route::post('products', [$s[0], 'storeProduct']);
            Route::put('products/{id}', [$s[0], 'updateProduct']);
            Route::delete('products/{id}', [$s[0], 'deleteProduct']);

            Route::get('invoice-tags', [$s[0], 'getInvoiceTags']);
            Route::post('invoice-tags', [$s[0], 'storeInvoiceTag']);
            Route::put('invoice-tags/{id}', [$s[0], 'updateInvoiceTag']);
            Route::delete('invoice-tags/{id}', [$s[0], 'deleteInvoiceTag']);

            Route::get('comment-types', [$s[0], 'getCommentTypes']);
            Route::post('comment-types', [$s[0], 'storeCommentType']);
            Route::put('comment-types/{id}', [$s[0], 'updateCommentType']);
            Route::delete('comment-types/{id}', [$s[0], 'deleteCommentType']);

            Route::get('teams', [$s[0], 'getTeams']);
            Route::post('teams', [$s[0], 'storeTeam']);
            Route::put('teams/{id}', [$s[0], 'updateTeam']);
            Route::delete('teams/{id}', [$s[0], 'deleteTeam']);
            Route::get('roles', [$s[0], 'getRoles']);
            Route::post('roles', [$s[0], 'storeRole']);
            Route::put('roles/{id}', [$s[0], 'updateRole']);
            Route::delete('roles/{id}', [$s[0], 'deleteRole']);

            Route::get('permissions', [$s[0], 'getPermissions']);
        });
        
        // Dashboard Module
        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [\App\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'summary']);
            Route::get('charts', [\App\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'charts']);
            Route::get('recent-activities', [\App\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'recentActivities']);
        });
    });
});
