# توثيق وحدة المصادقة والعملاء (المرجع الشامل والنهائي)

هذا المستند هو الدليل التقني الكامل لمطوري تطبيقات الموبايل. يحتوي على كافة الـ APIs الخاصة بالمصادقة والعملاء مع أمثلة دقيقة جداً لكل جسم طلب (Request) واستجابة راجعة (Response) بدون أي اختصار.

---

## 1. وحدة المصادقة (Authentication Module)

### 1.1 تسجيل الدخول (Login)
- **المسار:** `POST /api/v1/auth/login`
- **Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "أدمن النظام",
      "email": "admin@example.com",
      "role": { "id": 1, "name": "Admin" },
      "team": { "id": 1, "name": "Sales Team" }
    },
    "access_token": "1|access_token_string_here",
    "refresh_token": "2|refresh_token_string_here"
  }
}
```

### 1.2 تجديد التوكن (Refresh Token)
- **المسار:** `POST /api/v1/auth/refresh`
- **Header:** `Authorization: Bearer {refresh_token}`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "3|new_access_token_string"
  }
}
```

### 1.3 تسجيل الخروج (Logout)
- **المسار:** `POST /api/v1/auth/logout`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

### 1.4 بياناتي (Me)
- **المسار:** `GET /api/v1/auth/me`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "أدمن النظام",
    "email": "admin@example.com",
    "role": {
      "id": 1,
      "name": "Admin",
      "permissions": [
        { "id": 1, "name": "create-client", "display_name": "إضافة عميل" },
        { "id": 2, "name": "edit-client", "display_name": "تعديل عميل" }
      ]
    },
    "team": { "id": 1, "name": "Sales Team" }
  }
}
```

---

## 2. وحدة العملاء (Clients Module)

### 2.1 قائمة العملاء (Index)
- **المسار:** `GET /api/v1/clients`
- **Query Params (الفلاتر):**
  - `page`: رقم الصفحة (افتراضي 1)
  - `per_page`: عدد النتائج (افتراضي 15)
  - `search`: بحث بالاسم، الهاتف، الإيميل، أو الشركة
  - `status_id`: فلتر بحالة العميل (ID)
  - `priority`: فلتر بالأولوية (high, medium, low)
  - `lead_rating`: فلتر بالتقييم (hot, warm, cold)
  - `source_id`: فلتر بالمصدر (ID)
  - `city_id`: فلتر بالمدينة (ID)
  - `region_id`: فلتر بالمنطقة (ID)
  - `assigned_to`: فلتر بالموظف المسؤول (User ID)
  - `tags[]`: فلتر بالوسوم (مصفوفة IDs)
  - `sort_by`: الترتيب حسب حقل (مثل created_at)
  - `sort_dir`: اتجاه الترتيب (desc أو asc)
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم جلب العملاء بنجاح",
  "data": [
    {
      "id": 1,
      "name": "أحمد علي",
      "phone": "0912345678",
      "email": "ahmed@example.com",
      "status": { "id": 1, "name": "جديد", "color": "#FF0000" },
      "priority": "high",
      "lead_rating": "hot",
      "assigned_to": { "id": 5, "name": "موظف 1" },
      "tags": [{ "id": 10, "name": "VIP", "color": "#FFD700" }],
      "created_at": "2024-01-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### 2.2 إضافة عميل (Store)
- **المسار:** `POST /api/v1/clients`
- **Request Body:**
```json
{
  "name": "محمد حسن",
  "phone": "0987654321",
  "email": "mohamed@test.com",
  "status_id": 1,
  "priority": "medium",
  "lead_rating": "warm", // Optional (hot, warm, cold)
  "source_id": 1,        // Optional
  "source_status": "valid", // Optional (valid, invalid)
  "behavior_id": 2,      // Optional
  "invalid_reason_id": null, // Optional
  "region_id": 1,
  "city_id": 2,
  "assigned_to": 5,
  "tags": [1, 2]
}
```
- **Success Response (201):**
```json
{
  "success": true,
  "message": "تم إنشاء العميل بنجاح",
  "data": {
    "id": 15,
    "name": "محمد حسن",
    "phone": "0987654321",
    "status": { "id": 1, "name": "جديد" }
    // ... باقي التفاصيل الأساسية كما في الـ Index
  }
}
```

### 2.3 تفاصيل عميل (Show)
- **المسار:** `GET /api/v1/clients/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "أحمد علي",
    "address": "دمشق - المزة",
    "source": { "id": 1, "name": "فيسبوك" },
    "behavior": { "id": 2, "name": "مهتم جدأ" },
    "invalid_reason": null,
    "exclusion_reason": null,
    "assigned_to": {
        "id": 5, 
        "name": "موظف المبيعات",
        "avatar": null,
        "email": "sales@example.com"
    },
    "comments_count": 5,
    "files_count": 2,
    "invoices_count": 1,
    "appointments_count": 3,
    "first_contact_at": "2024-01-01 10:00",
    "converted_at": null
    // ... تفاصيل الحقول كاملة
  }
}
```

### 2.4 تعديل عميل (Update)
- **المسار:** `PUT /api/v1/clients/{id}`
- **Success Response (200):** يعيد نفس هيكلية العميل في (Show) مع الرسالة: `"تم تحديث العميل بنجاح"`.

### 2.5 حذف عميل (Delete)
- **المسار:** `DELETE /api/v1/clients/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم حذف العميل بنجاح"
}
```

### 2.6 استعادة عميل (Restore)
- **المسار:** `POST /api/v1/clients/{id}/restore`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم استعادة العميل بنجاح"
}
```

### 2.7 تغيير الحالة (Change Status)
- **المسار:** `PATCH /api/v1/clients/{id}/status`
- **Body:** `{ "status_id": 3 }`
- **Success Response (200):** يعيد بيانات العميل المحدثة.

### 2.8 إسناد عميل لموظف (Assign)
- **المسار:** `PATCH /api/v1/clients/{id}/assign`
- **Body:** `{ "user_id": 10 }`
- **Success Response (200):** يعيد بيانات العميل مع تحديث حقل `assigned_to`.

### 2.9 تغيير الحالة الجماعي (Bulk Status)
- **المسار:** `POST /api/v1/clients/bulk/status`
- **Body:** `{ "client_ids": [1, 5, 8], "status_id": 2 }`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم تحديث حالة 3 عميل بنجاح"
}
```

### 2.10 الإسناد الجماعي (Bulk Assign)
- **المسار:** `POST /api/v1/clients/bulk/assign`
- **Body:** `{ "client_ids": [1, 5, 8], "user_id": 2 }`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم إسناد 3 عميل بنجاح"
}
```

### 2.11 الحذف الجماعي (Bulk Delete)
- **المسار:** `DELETE /api/v1/clients/bulk`
- **Body:** `{ "client_ids": [1, 5, 8] }`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم حذف 3 عميل بنجاح"
}
```

### 2.12 جلب التعليقات (Get Comments)
- **المسار:** `GET /api/v1/clients/{id}/comments`
- **Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 50,
      "content": "تم الاتصال بالعميل...",
      "outcome": "positive",
      "type": { "name": "Call", "icon": "phone", "color": "#333" },
      "attachments": [ { "id": 1, "url": "..." } ],
      "mentions": [ { "id": 2, "name": "سارة" } ],
      "created_at": "منذ ساعة"
    }
  ],
  "meta": { "total": 20, "current_page": 1 }
}
```

### 2.13 إضافة تعليق غني (Add Comment)
- **المسار:** `POST /api/v1/clients/{id}/comments`
- **Request (Multipart):** `type_id`, `content`, `outcome` (Optional), `mentions[]`, `attachments[]`.
- **Success Response (201):** يعيد كائن التعليق المنشأ كما في هيكلية (Get Comments).

### 2.14 جلب ملفات العميل (Get Files)
- **المسار:** `GET /api/v1/clients/{id}/files`
- **Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "contract.pdf",
      "url": "https://...",
      "type": "document",
      "size": "1.2 MB",
      "user": { "id": 1, "name": "سارة" },
      "created_at": "2024-01-10"
    }
  ]
}
```

### 2.15 رفع ملف مستقل (Upload File)
- **المسار:** `POST /api/v1/clients/{id}/files`
- **Request (Multipart):** `file`, `type` (image, document, audio).
- **Success Response (201):** يعيد كائن الملف المرفوع بنفس هيكلية (Get Files).

### 2.16 تصدير ملف العميل (Download PDF)
- **المسار:** `GET /api/v1/clients/{id}/pdf`
- **Response:** يتم تحميل ملف PDF مباشرة (Content-Type: application/pdf).

### 2.17 تصدير القائمة (Export CSV)
- **المسار:** `GET /api/v1/clients/export`
- **Params:** نفس فلاتر الـ Index (`status_id`, `...`) باستثناء Pagination.
- **Response:** يتم تحميل ملف CSV مباشرة.

### 2.18 إدارة الفلاتر (Saved Filters)

#### جلب الفلاتر المحفوظة
- **المسار:** `GET /api/v1/clients/filters`
- **Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "name": "عملاء الرياض الجدد",
      "criteria": { "city_id": 1, "status_id": 1 }
    }
  ]
}
```

#### حفظ فلتر جديد
- **المسار:** `POST /api/v1/clients/filters`
- **Body:** `{ "name": "...", "criteria": { "key": "value" } }`
- **Success Response (201):** يعيد كائن الفلتر المنشأ.

#### حذف فلتر
- **المسار:** `DELETE /api/v1/clients/filters/{id}`
- **Response:** `{ "success": true, "message": "تم حذف الفلتر بنجاح" }`

### 2.16 الخط الزمني (Timeline)
- **المسار:** `GET /api/v1/clients/{id}/timeline`
- **Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 120,
      "event_type": "status_change",
      "description": "تغيير الحالة إلى مشترك",
      "user": { "name": "أدمن" },
      "created_at": "2024-01-10 14:00"
    }
  ]
}
```

### 2.17 الإحصائيات (Stats)
- **المسار:** `GET /api/v1/clients/stats`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_clients": 1500,
    "by_status": [
      { "status_id": 1, "status_name": "جديد", "color": "#FF0000", "count": 400 },
      { "status_id": 2, "status_name": "مشترك", "color": "#00FF00", "count": 600 }
    ],
    "by_priority": [
      { "priority": "high", "count": 100 }
    ],
    "by_source": [
      { "source_id": 1, "source_name": "Facebook", "count": 50 }
    ],
    "invalid_registrations": [
      { "reason_id": 1, "reason_name": "الرقم غير صحيح", "count": 10 }
    ],
    "employees_performance": [
      { 
        "user_id": 5, 
        "user_name": "أحمد", 
        "total_assigned": 50, 
        "converted_count": 10, 
        "conversion_rate": 20.0 
      }
    ]
  }
  }
}
```

### 2.18 مؤشرات الأداء (KPIs)
- **المسار:** `GET /api/v1/clients/kpis`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_clients": 500,
    "converted_clients": 150,
    "conversion_rate": 30.0,
    "hot_leads": 45,
    "avg_conversion_days": 12.5,
    "avg_response_time": 2.5, // بالساعات
    "loss_rate": 5.2 // نسبة مئوية
  }
  }
}
```

---
**أخطاء التحقق (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "بيانات غير صالحة",
  "errors": {
    "phone": ["رقم الهاتف محجوز مسبقاً"],
    "name": ["اسم العميل مطلوب"]
  }
}
```
