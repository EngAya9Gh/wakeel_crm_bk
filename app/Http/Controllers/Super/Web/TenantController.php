<?php

declare(strict_types=1);

namespace App\Http\Controllers\Super\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index()
    {
        $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
        return Inertia::render('Tenants/Index', [
            'plans' => $plans
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'plan' => 'nullable|in:basic,pro,enterprise',
        ]);

        try {
            DB::beginTransaction();

            // Snapshot the plan features
            $plan = \App\Models\Plan::where('slug', $validated['plan'] ?? 'basic')->first();
            $features = $plan ? $plan->modules : [];

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'plan' => $validated['plan'] ?? 'basic',
                'features' => $features,
                'is_active' => true,
            ]);

            DB::commit();

            return redirect()->route('super.tenants.index')->with('success', 'تم إنشاء المستأجر بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'حدث خطأ أثناء الإنشاء: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        return Inertia::render('Tenants/Show', [
            'id' => (string) $id,
            'tenantData' => $tenant,
            'availableModules' => config('features.available_modules'),
            'plans' => \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get()->keyBy('slug'),
            'enabledFeatures' => $tenant->enabledFeatures(),
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'whatsapp_base_url' => 'nullable|url|max:255',
            'whatsapp_phone' => 'nullable|string|max:50',
            'whatsapp_webhook_secret' => 'nullable|string|max:255',
            'whatsapp_api_key' => 'nullable|string|max:255',
        ]);

        $settings = $tenant->settings ?? [];
        
        $settings['whatsapp'] = [
            'base_url' => $validated['whatsapp_base_url'],
            'phone' => $validated['whatsapp_phone'],
            'webhook_secret' => $validated['whatsapp_webhook_secret'],
            'api_key' => $validated['whatsapp_api_key'],
        ];

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    public function updateFeatures(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan' => 'required|in:basic,pro,ultimate',
            'features' => 'nullable|array',
            'features.*' => 'string',
        ]);

        $tenant->update([
            'plan' => $validated['plan'],
            'features' => $validated['features'] ?? []
        ]);

        return back()->with('success', 'تم تحديث ميزات المستأجر بنجاح');
    }

    public function generateApiKey(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tenant->apiKeys()->create([
            'name' => $validated['name'],
            'key' => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        return back()->with('success', 'تم إنشاء المفتاح بنجاح');
    }

    public function revokeApiKey(Tenant $tenant, ApiKey $apiKey)
    {
        if ($apiKey->tenant_id !== $tenant->id) {
            abort(403);
        }

        $apiKey->delete();

        return back()->with('success', 'تم تعطيل المفتاح وحذفه بنجاح');
    }
}
