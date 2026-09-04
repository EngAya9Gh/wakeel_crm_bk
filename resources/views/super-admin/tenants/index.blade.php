@extends('layouts.super-admin')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-50">المستأجرون</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $tenants->total() }} مستأجر مسجل</p>
    </div>
    <button @click="$dispatch('open-modal', 'create-tenant')"
            class="flex items-center gap-2 px-5 py-2.5 bg-wakeel-primary hover:bg-wakeel-primary-dark
                   text-white text-sm font-bold rounded-xl shadow-md shadow-orange-200 dark:shadow-orange-900/30
                   transition-all duration-200 active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        مستأجر جديد
    </button>
</div>

{{-- Tenants Table --}}
<div class="bg-white dark:bg-wakeel-dark-card rounded-2xl border border-gray-100 dark:border-wakeel-dark-border shadow-sm overflow-hidden">
    @if($tenants->isEmpty())
        <div class="p-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-50 dark:bg-white/5 text-gray-300 dark:text-gray-700 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-1">لا يوجد مستأجرون</h3>
            <p class="text-sm text-gray-400 dark:text-gray-600">ابدأ بإضافة أول مستأجر للنظام</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/[0.03] text-xs uppercase tracking-wider text-gray-500 dark:text-gray-500 border-b border-gray-100 dark:border-wakeel-dark-border">
                        <th class="px-6 py-4 font-semibold">المستأجر</th>
                        <th class="px-6 py-4 font-semibold text-center">الباقة</th>
                        <th class="px-6 py-4 font-semibold text-center">المستخدمون</th>
                        <th class="px-6 py-4 font-semibold text-center">العملاء</th>
                        <th class="px-6 py-4 font-semibold text-center">المفاتيح</th>
                        <th class="px-6 py-4 font-semibold text-center">الحالة</th>
                        <th class="px-6 py-4 font-semibold text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-wakeel-dark-border">
                    @foreach($tenants as $tenant)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $tenant->name }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-600 font-mono mt-0.5 dir-ltr text-right">{{ $tenant->slug }}.wakeel.cc</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($tenant->plan === 'pro')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 dark:bg-orange-500/15 text-wakeel-primary border border-orange-200 dark:border-orange-500/30">احترافي</span>
                            @elseif($tenant->plan === 'enterprise')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30">مؤسسي</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/10">أساسي</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ $tenant->users_count }}</td>
                        <td class="px-6 py-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ $tenant->clients_count }}</td>
                        <td class="px-6 py-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ $tenant->api_keys_count }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($tenant->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-500/15 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-500 border border-gray-200 dark:border-white/10">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    غير نشط
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center">
                                <a href="{{ route('super.tenants.show', $tenant) }}"
                                   class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all duration-200
                                          bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400
                                          hover:bg-wakeel-primary hover:text-white dark:hover:bg-wakeel-primary dark:hover:text-white
                                          border border-gray-200 dark:border-wakeel-dark-border">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    إدارة
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-wakeel-dark-border">
            {{ $tenants->links() }}
        </div>
        @endif
    @endif
</div>

{{-- ===== CREATE TENANT MODAL ===== --}}
<div
    x-data="{ open: false, loading: false, form: { name: '', slug: '', plan: 'basic' } }"
    @open-modal.window="if ($event.detail === 'create-tenant') open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         @click="open = false"></div>

    {{-- Modal --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative z-10 w-full max-w-lg bg-white dark:bg-wakeel-dark-card rounded-2xl shadow-2xl border border-gray-100 dark:border-wakeel-dark-border">

        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-wakeel-dark-border">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-50">إضافة مستأجر جديد</h3>
            <button @click="open = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('super.tenants.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    اسم المؤسسة <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required
                       x-model="form.name"
                       @input="form.slug = form.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')"
                       placeholder="شركة المستقبل للتقنية"
                       class="w-full px-4 py-3 text-sm bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-wakeel-dark-border rounded-xl
                              text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-600
                              focus:outline-none focus:ring-2 focus:ring-wakeel-primary/40 focus:border-wakeel-primary transition-all duration-200">
            </div>

            <div>
                <label for="slug" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    النطاق الفرعي (Slug) <span class="text-red-500">*</span>
                </label>
                <div class="flex items-stretch rounded-xl overflow-hidden border border-gray-200 dark:border-wakeel-dark-border focus-within:ring-2 focus-within:ring-wakeel-primary/40 focus-within:border-wakeel-primary transition-all duration-200">
                    <input type="text" id="slug" name="slug" required
                           x-model="form.slug"
                           placeholder="company-name"
                           class="flex-1 px-4 py-3 text-sm bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-gray-100 outline-none dir-ltr">
                    <span class="px-4 py-3 bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-500 text-sm font-mono whitespace-nowrap border-r border-gray-200 dark:border-wakeel-dark-border">.wakeel.cc</span>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">سيكون عنوان المستأجر على الرابط الفرعي.</p>
            </div>

            <div>
                <label for="plan" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    الباقة
                </label>
                <select id="plan" name="plan"
                        class="w-full px-4 py-3 text-sm bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-wakeel-dark-border rounded-xl
                               text-gray-900 dark:text-gray-100
                               focus:outline-none focus:ring-2 focus:ring-wakeel-primary/40 focus:border-wakeel-primary transition-all duration-200">
                    <option value="basic">أساسي</option>
                    <option value="pro">احترافي</option>
                    <option value="enterprise">مؤسسي</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="loading"
                        class="flex-1 py-3 bg-wakeel-primary hover:bg-wakeel-primary-dark text-white text-sm font-bold
                               rounded-xl shadow-md shadow-orange-200 dark:shadow-orange-900/30
                               transition-all duration-200 active:scale-95 disabled:opacity-60">
                    إنشاء المستأجر
                </button>
                <button type="button" @click="open = false"
                        class="flex-1 py-3 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10
                               text-gray-700 dark:text-gray-300 text-sm font-bold rounded-xl transition-all duration-200">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
