@extends('layouts.super-admin')

@section('content')
<div x-data="{ activeTab: 'general' }" class="w-full">
    
    <!-- Breadcrumb -->
    <div class="flex justify-end mb-6 text-sm text-gray-500 font-medium">
        <span>المستأجرون</span>
        <span class="mx-2">/</span>
        <span class="text-gray-900 dark:text-gray-200">{{ $tenant->name }}</span>
    </div>

    <!-- Tenant Summary Card -->
    <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-200 dark:border-gray-800 p-6 mb-8 flex justify-between items-center shadow-sm">
        
        <!-- Left Stats -->
        <div class="flex gap-8 text-center">
            <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</div>
                <div class="text-xs text-gray-400 font-medium mt-1">المفاتيح</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</div>
                <div class="text-xs text-gray-400 font-medium mt-1">العملاء</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">1</div>
                <div class="text-xs text-gray-400 font-medium mt-1">المستخدمون</div>
            </div>
        </div>

        <!-- Right Info -->
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="flex items-center gap-3 justify-end mb-1">
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded text-xs font-bold">نشط</span>
                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded text-xs font-bold">{{ $tenant->plan === 'enterprise' ? 'مؤسسي' : ($tenant->plan === 'pro' ? 'احترافي' : 'أساسي') }}</span>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $tenant->name }}</h2>
                </div>
                <div class="text-sm text-gray-400 font-medium">
                    {{ $tenant->slug }} • admin@wakeel.crm
                </div>
            </div>
            <!-- Tenant Icon -->
            <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-white dark:bg-[#1a1d27] rounded-xl border border-gray-100 dark:border-gray-800 p-1.5 mb-8 flex text-center font-bold text-sm shadow-sm">
        <button 
            @click="activeTab = 'users'"
            :class="activeTab === 'users' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            class="flex-1 py-3 rounded-lg transition-all">
            المستخدمون
        </button>
        <button 
            @click="activeTab = 'api'"
            :class="activeTab === 'api' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            class="flex-1 py-3 rounded-lg transition-all">
            مفاتيح API
        </button>
        <button 
            @click="activeTab = 'general'"
            :class="activeTab === 'general' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            class="flex-1 py-3 rounded-lg transition-all">
            الإعدادات العامة
        </button>
    </div>

    <!-- Tab Contents -->
    
    <!-- General Tab -->
    <div x-show="activeTab === 'general'" x-cloak>
        <div class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-8 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 text-right">المعلومات الأساسية</h3>
            <div class="space-y-6 text-right">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">اسم المستأجر</label>
                    <input type="text" value="{{ $tenant->name }}" readonly class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-right text-gray-900 dark:text-gray-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">النطاق الفرعي (Slug)</label>
                    <input type="text" value="{{ $tenant->slug }}" readonly class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-right text-gray-900 dark:text-gray-100 outline-none">
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Tab -->
    <div x-show="activeTab === 'api'" x-cloak>
        <!-- Info Banner -->
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900/50 rounded-2xl p-6 mb-6 flex justify-between items-start text-right">
            <div>
                <h4 class="text-green-800 dark:text-green-400 font-bold mb-2 flex items-center gap-2 justify-end">
                    مفاتيح الربط — خدمة اشتراك واتساب للرسائل
                </h4>
                <p class="text-green-700 dark:text-green-500 text-sm leading-relaxed">
                    تُستخدم هذه المفاتيح لربط مواقع المستأجر بنظام CRM لاستقبال العملاء المحتملين (Leads) تلقائياً. كل مفتاح يعرّف المستأجر عند وصول الطلب، مما يتيح له الاشتراك في خدمة رسائل الواتساب وإرسال الإشعارات الفورية للعملاء الجدد.
                </p>
                <div class="mt-4 bg-green-200/50 dark:bg-green-900/50 text-green-800 dark:text-green-300 font-mono text-sm px-4 py-2 rounded-lg inline-block">
                    X-API-Key: wkl_xxxxxxxxxxxxxxxx
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm text-green-500 ml-4 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
        </div>

        <!-- Add New Key -->
        <div class="bg-white dark:bg-[#1a1d27] rounded-2xl border border-gray-100 dark:border-gray-800 p-4 mb-6 flex gap-4 shadow-sm items-center">
            <button class="bg-[#ff6600]/80 hover:bg-[#ff6600] text-white px-6 py-3 rounded-xl font-bold text-sm transition-colors shrink-0">
                + مفتاح جديد
            </button>
            <input type="text" placeholder="اسم المفتاح، مثل: &quot;الموقع الرئيسي&quot; أو &quot;صفحة الهبوط&quot;" class="flex-1 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-right text-gray-900 dark:text-gray-100 outline-none text-sm">
        </div>

        <!-- Key Item -->
        <div class="bg-white dark:bg-[#1a1d27] rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm flex items-center justify-between">
            <div class="flex gap-2">
                <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button class="px-4 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 text-sm font-bold hover:bg-red-100 transition-colors">
                    تعطيل
                </button>
                <span class="text-sm text-gray-400 flex items-center px-2">تم يستخدم بعد</span>
            </div>
            
            <div class="text-right flex items-center gap-4">
                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-2 rounded-lg font-mono text-sm text-gray-600 dark:text-gray-300 flex items-center gap-3">
                    <button class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                    <span>...JlQlZqUN1HFfeenMO5Iz8eJYM</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 justify-end mb-1">
                        <span class="font-bold text-gray-900 dark:text-white">مفتاح الموقع القديم (من env.)</span>
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users Tab -->
    <div x-show="activeTab === 'users'" x-cloak class="bg-white dark:bg-[#1a1d27] rounded-3xl border border-gray-100 dark:border-gray-800 p-8 shadow-sm text-center text-gray-500">
        قريباً...
    </div>
</div>
@endsection
