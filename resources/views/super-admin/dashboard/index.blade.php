@extends('layouts.super-admin')

@section('content')
<div class="w-full">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <a href="{{ route('super.tenants.index') }}" class="bg-[#ff6600]/80 hover:bg-[#ff6600] text-white px-6 py-3 rounded-xl font-bold text-sm transition-colors shadow-sm">
            + مستأجر جديد
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">لوحة الإدارة العليا</h1>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-right">
        <!-- Total Tenants -->
        <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ \App\Models\Tenant::count() }}</div>
                <div class="text-sm font-medium text-gray-500">إجمالي المستأجرين</div>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ \App\Models\Tenant::where('is_active', true)->count() }}</div>
                <div class="text-sm font-medium text-gray-500">المستأجرين النشطين</div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ \App\Models\User::count() }}</div>
                <div class="text-sm font-medium text-gray-500">إجمالي المستخدمين</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-8 shadow-sm text-right">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">روابط سريعة</h3>
        <div class="flex flex-wrap gap-4 justify-end">
            <a href="{{ route('super.tenants.index') }}" class="bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 px-6 py-4 rounded-2xl flex flex-col items-center justify-center gap-2 transition-colors w-40">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span class="font-bold text-gray-900 dark:text-white text-sm">إدارة المستأجرين</span>
            </a>
            
            <a href="#" class="bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 px-6 py-4 rounded-2xl flex flex-col items-center justify-center gap-2 transition-colors w-40">
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="font-bold text-gray-900 dark:text-white text-sm">الإعدادات</span>
            </a>
        </div>
    </div>
</div>
@endsection
