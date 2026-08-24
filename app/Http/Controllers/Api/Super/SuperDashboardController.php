<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Super;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

/**
 * Super Admin Dashboard Controller
 * Provides global statistics across all tenants.
 */
class SuperDashboardController extends Controller
{
    use \App\Traits\ApiResponse;

    public function stats(): JsonResponse
    {
        $stats = [
            'tenants' => [
                'total'  => Tenant::count(),
                'active' => Tenant::where('is_active', true)->count(),
            ],
            'users' => [
                'total'  => User::withoutGlobalScope('tenant')->count(),
                'active' => User::withoutGlobalScope('tenant')->where('is_active', true)->count(),
            ],
            'clients' => [
                'total' => Client::withoutGlobalScope('tenant')->count(),
            ],
            'invoices' => [
                'total' => Invoice::withoutGlobalScope('tenant')->count(),
            ],
            'tenants_by_plan' => Tenant::selectRaw('plan, count(*) as count')
                ->groupBy('plan')
                ->pluck('count', 'plan'),
        ];

        return $this->successResponse($stats, 'إحصائيات النظام');
    }
}
