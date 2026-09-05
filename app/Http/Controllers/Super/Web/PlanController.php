<?php

namespace App\Http\Controllers\Super\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return Inertia::render('Plans/Index', [
            'plans' => $plans
        ]);
    }

    public function create()
    {
        $availableModules = config('features.available_modules');
        return Inertia::render('Plans/Create', [
            'availableModules' => $availableModules
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'modules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        Plan::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'price' => $request->price ?? 0,
            'description' => $request->description,
            'modules' => $request->modules ?? [],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('super.plans.index')->with('success', 'تم إنشاء الباقة بنجاح.');
    }

    public function edit(Plan $plan)
    {
        $availableModules = config('features.available_modules');
        return Inertia::render('Plans/Edit', [
            'plan' => $plan,
            'availableModules' => $availableModules
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'modules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $plan->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'price' => $request->price ?? 0,
            'description' => $request->description,
            'modules' => $request->modules ?? [],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('super.plans.index')->with('success', 'تم تحديث الباقة بنجاح.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('super.plans.index')->with('success', 'تم حذف الباقة بنجاح.');
    }
}
