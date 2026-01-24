# توثيق الإعدادات، المستخدمين، ولوحة التحكم (المرجع الشامل)

هذا المستند مخصص لتوثيق نقاط نهاية (API Endpoints) الخاصة **بالإعدادات، المستخدمين، ولوحة التحكم (Dashboard)**. تم إعداده بدقة ليطابق الكود المصدري في `SettingsController`، `UserController`، و `DashboardController`.

---

## 1. وحدة المستخدمين (Users Module)

### 1.1 قائمة المستخدمين (Index)
- **المسار:** `GET /api/v1/users`
- **Params:** `search`, `team_id`, `role_id`, `is_active` (0 or 1).
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم جلب المستخدمين بنجاح",
  "data": [
    {
      "id": 1,
      "name": "مدير النظام",
      "email": "admin@example.com",
      "phone": "0500000000",
      "avatar": "https://domain.com/storage/avatars/1.jpg",
      "is_active": true,
      "team": {
        "id": 1,
        "name": "الإدارة العليا",
        "category": "management"
      },
      "role": { "id": 1, "name": "Admin" },
      "created_at": "2024-01-01 10:00"
    }
  ],
  "meta": { "total": 10, "current_page": 1, "per_page": 15, "last_page": 1 }
}
```

### 1.2 إضافة مستخدم (Store)
- **المسار:** `POST /api/v1/users`
- **Request Body:**
```json
{
  "name": "أحمد موظف",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "team_id": 2,
  "role_id": 3,
  "phone": "0555555555",
  "is_active": true
}
```
- **Success Response (201):**
```json
{
  "success": true,
  "message": "تم إنشاء المستخدم بنجاح",
  "data": {
    "id": 15,
    "name": "أحمد موظف",
    "email": "ahmed@example.com",
    "avatar": null,
    "is_active": true,
    "team": { "id": 2, "name": "المبيعات", "category": "sales" },
    "role": { "id": 3, "name": "Sales Agent" },
    "created_at": "2024-01-14T10:00:00Z"
  }
}
```

### 1.3 تفاصيل مستخدم (Show)
- **المسار:** `GET /api/v1/users/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "مدير النظام",
    "email": "admin@example.com",
    "phone": "0912345678",
    "avatar": "https://...",
    "is_active": true,
    "team": { "id": 1, "name": "الإدارة" },
    "role": { "id": 1, "name": "Admin" },
    "permissions": ["create_user", "delete_user", "view_reports"],
    "created_at": "2024-01-01 10:00"
  }
}
```

### 1.4 تعديل مستخدم (Update)
- **المسار:** `PUT /api/v1/users/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم تحديث بيانات المستخدم بنجاح",
  "data": {
    "id": 1, 
    "name": "الاسم الجديد",
    // ... باقي بيانات المستخدم المحدثة
  }
}
```

---

## 3. وحدة الإعدادات (Settings Module)

هذا الموديول يتيح إدارة كافة القيم المرجعية (Lookups). جميع الأقسام أدناه تدعم العمليات الأربعة (CRUD) بشكل كامل.

### 3.1 الحالات (Client Statuses)
- **GET /settings/statuses**
  - Output: `[{ "id": 1, "name": "جديد", "color": "#F00", "order": 1, "weight": 0, "is_default": true }]`
- **POST /settings/statuses**
  - Body: `{ "name": "...", "color": "#...", "order": 1, "weight": 50, "is_default": false }`
- **PUT /settings/statuses/{id}**
  - Body: `{ "name": "تعديل الاسم", "weight": 60 }` (الحقول اختيارية)
- **DELETE /settings/statuses/{id}**
  - Response: `{ "success": true, "message": "تم حذف الحالة بنجاح" }`

### 3.2 المصادر (Sources)
- **GET /settings/sources**
  - Output: `[{ "id": 1, "name": "Facebook", "is_active": true }]`
- **POST /settings/sources**
  - Body: `{ "name": "Instagram", "is_active": true }`
- **PUT /settings/sources/{id}**
  - Body: `{ "name": "New Name" }`
- **DELETE /settings/sources/{id}**
  - Response: `{ "success": true }`

### 3.3 السلوكيات (Behaviors)
- **GET /settings/behaviors**
  - Output: `[{ "id": 1, "name": "مهتم", "color": "#0F0" }]`
- **POST /settings/behaviors**
  - Body: `{ "name": "...", "color": "..." }`
- **PUT /settings/behaviors/{id}**
  - Body: `{ "color": "#000" }`
- **DELETE /settings/behaviors/{id}**

### 3.4 أسباب الاستبعاد (Invalid Reasons)
- **GET /settings/invalid-reasons**
  - Output: `[{ "id": 1, "name": "الرقم خطأ" }]`
- **POST /settings/invalid-reasons**
  - Body: `{ "name": "..." }`
- **PUT /settings/invalid-reasons/{id}**
- **DELETE /settings/invalid-reasons/{id}**

### 3.5 المناطق (Regions)
- **GET /settings/regions**
  - Output: `[{ "id": 1, "name": "الرياض" }]`
- **POST /settings/regions**
  - Body: `{ "name": "..." }`
- **PUT /settings/regions/{id}**
- **DELETE /settings/regions/{id}**

### 3.6 المدن (Cities)
- **GET /settings/cities?region_id=1**
  - Output: `[{ "id": 5, "name": "العليا", "region_id": 1 }]`
- **POST /settings/cities**
  - Body: `{ "name": "...", "region_id": 1 }`
- **PUT /settings/cities/{id}**
  - Body: `{ "name": "...", "region_id": 1 }`
- **DELETE /settings/cities/{id}**

### 3.7 تاجز العملاء (Tags)
- **GET /settings/tags**
  - Output: `[{ "id": 1, "name": "VIP", "color": "#GOLD" }]`
- **POST /settings/tags**
  - Body: `{ "name": "...", "color": "..." }`
- **PUT /settings/tags/{id}**
- **DELETE /settings/tags/{id}**

### 3.8 المنتجات (Products)
- **GET /settings/products**
  - Output: `[{ "id": 1, "name": "استشارة", "price": 500, "is_active": true }]`
- **POST /settings/products**
  - Body: `{ "name": "...", "price": 500, "is_active": true }`
- **PUT /settings/products/{id}**
  - Body: `{ "price": 600 }`
- **DELETE /settings/products/{id}**

### 3.9 تاجز الفواتير (Invoice Tags)
- **GET /settings/invoice-tags**
  - Output: `[{ "id": 1, "name": "مدفوع", "color": "#GREEN" }]`
- **POST /settings/invoice-tags**
  - Body: `{ "name": "...", "color": "..." }`
- **PUT /settings/invoice-tags/{id}**
- **DELETE /settings/invoice-tags/{id}**

### 3.10 أنواع التعليقات (Comment Types)
- **GET /settings/comment-types**
  - Output: `[{ "id": 1, "name": "Call", "icon": "phone", "color": "#blue" }]`
- **POST /settings/comment-types**
  - Body: `{ "name": "...", "icon": "...", "color": "..." }`
- **PUT /settings/comment-types/{id}**
- **DELETE /settings/comment-types/{id}**

### 3.11 الفرق (Teams)
- **GET /settings/teams**
  - Output: `[{ "id": 1, "name": "المبيعات", "roles": [...] }]`
- **POST /settings/teams**
  - Body: `{ "name": "...", "description": "...", "is_active": true }`
- **PUT /settings/teams/{id}**
- **DELETE /settings/teams/{id}**

### 3.12 الأدوار (Roles)
- **GET /settings/roles?team_id=1**
  - Output: `[{ "id": 1, "name": "مدير", "permissions": [...] }]`
- **POST /settings/roles**
  - Body: `{ "name": "...", "team_id": 1, "permissions": [1, 2] }`
- **PUT /settings/roles/{id}**
  - Body: `{ "name": "...", "permissions": [1, 5] }` (لتحديث الصلاحيات، أرسل المصفوفة الجديدة كاملة)
- **DELETE /settings/roles/{id}**

### 3.13 قائمة الصلاحيات (Permissions List)
هذا API يستخدم لجلب قائمة الصلاحيات المتاحة في النظام لعرضها عند إنشاء أو تعديل دور (Role).
- **المسار:** `GET /api/v1/settings/permissions`
- **Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "create_client",
      "display_name": "إضافة عميل",
      "category": "clients"
    },
    {
      "id": 2,
      "name": "delete_invoice",
      "display_name": "حذف فاتورة",
      "category": "invoices"
    }
  ]
}
```

---

## 8. وحدة الداشبورد (Dashboard)

توفر هذه الوحدة نظرة شاملة على أداء النظام، بما في ذلك ملخصات سريعة، رسوم بيانية تحليلية، وسجل لآخر النشاطات.

### 8.1 ملخص الأرقام (Summary)
- **المسار:** `GET /dashboard/summary` / `api/v1/dashboard/summary` - يرجى استخدام الإصدار V1
- **الوصف:** يعيد إجماليات سريعة للعملاء، الفواتير، والمواعيد لعرضها في أعلى لوحة التحكم (Cards).
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "clients": {
      "total": 150,
      "this_month": 12,
      "by_status": [
        { "status": "جديد", "color": "#FF0000", "count": 45 },
        { "status": "تم التواصل", "color": "#00FF00", "count": 30 }
      ]
    },
    "invoices": {
      "total": 50,
      "total_revenue": 150000.00,
      "pending": 25000.00,
      "this_month": 5000.00
    },
    "appointments": {
      "total": 200,
      "upcoming": 5,
      "today": 1
    }
  }
}
```

### 8.2 الرسوم البيانية (Charts)
- **المسار:** `GET /dashboard/charts`
- **المعاملات (Query Params):**
  - `period`: الفترة الزمنية (اختياري). القيم المسموحة: `week`, `month` (Default), `year`.
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "clients_trend": [
      { "label": "01", "count": 5 },
      { "label": "02", "count": 8 }
    ],
    "revenue_trend": [
      { "label": "Jan", "total": 50000 },
      { "label": "Feb", "total": 65000 }
    ],
    "source_distribution": [
      { "source": "Google Ads", "count": 50 },
      { "source": "Referral", "count": 20 }
    ]
  }
}
```

### 8.3 آخر النشاطات (Recent Activities)
- **المسار:** `GET /dashboard/recent-activities`
- **الوصف:** يعيد آخر 5 حركات لكل كائن رئيسي في النظام (عملاء، فواتير، مواعيد، تعليقات).
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "recent_clients": [
      {
        "type": "client_created",
        "message": "عميل جديد: شركة الأفق",
        "link_id": 101,
        "status": "active",
        "color": "#28a745",
        "created_at": "منذ 2 ساعة"
      }
    ],
    "recent_invoices": [
      {
        "type": "invoice_created",
        "message": "فاتورة INV-001 - شركة الأفق",
        "link_id": 55,
        "total": "1,500.00",
        "status": "paid",
        "created_at": "منذ 3 ساعات"
      }
    ],
    "upcoming_appointments": [
      {
        "type": "upcoming_appointment",
        "message": "اجتماع عرض فني - شركة الأفق",
        "link_id": 12,
        "start_at": "2024-02-20 10:00",
        "time_until": "بعد يومين"
      }
    ],
    "recent_comments": [
      {
        "type": "comment_added",
        "message": "أحمد قام بالاتصال بـ شركة الأفق",
        "link_id": 101,
        "content": "تم الاتفاق على...",
        "comment_type": "Call",
        "color": "#blue",
        "created_at": "منذ 15 دقيقة"
      }
    ]
  }
}
```
