<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Modules (Features)
    |--------------------------------------------------------------------------
    |
    | Define all the available modules in the system.
    |
    */

    'available_modules' => [
        'clients' => [
            'key' => 'clients',
            'name_ar' => 'إدارة العملاء',
            'name_en' => 'Clients Management',
            'is_core' => true, // Core features are always enabled regardless of plan
        ],
        'users' => [
            'key' => 'users',
            'name_ar' => 'إدارة المستخدمين',
            'name_en' => 'Users Management',
            'is_core' => true,
        ],
        'settings' => [
            'key' => 'settings',
            'name_ar' => 'إعدادات النظام',
            'name_en' => 'System Settings',
            'is_core' => true,
        ],
        'appointments' => [
            'key' => 'appointments',
            'name_ar' => 'إدارة المواعيد',
            'name_en' => 'Appointments',
            'is_core' => false,
        ],
        'invoices' => [
            'key' => 'invoices',
            'name_ar' => 'إدارة الفواتير',
            'name_en' => 'Invoices',
            'is_core' => false,
        ],
        'whatsapp' => [
            'key' => 'whatsapp',
            'name_ar' => 'نظام الواتساب',
            'name_en' => 'WhatsApp Integration',
            'is_core' => false,
        ],
        'products' => [
            'key' => 'products',
            'name_ar' => 'الخدمات والمنتجات',
            'name_en' => 'Products & Services',
            'is_core' => false,
        ],
        'inventory' => [
            'key' => 'inventory',
            'name_ar' => 'إدارة المخزون',
            'name_en' => 'Inventory Management',
            'is_core' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define the features included in each subscription plan.
    |
    */

    'plans' => [
        'basic' => [
            'name' => 'الباقة الأساسية',
            'modules' => [], // Core features only
        ],
        'pro' => [
            'name' => 'الباقة الاحترافية',
            'modules' => ['appointments', 'invoices', 'products'], 
        ],
        'ultimate' => [
            'name' => 'الباقة المتكاملة',
            'modules' => ['appointments', 'invoices', 'whatsapp', 'products', 'inventory'],
        ],
    ],
];
