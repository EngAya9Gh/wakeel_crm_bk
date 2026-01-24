# توثيق وحدة الفواتير والمواعيد (المرجع الشامل)

هذا المستند هو الدليل التقني الكامل لمطوري تطبيقات الموبايل. يحتوي على كافة الـ APIs الخاصة بالفواتير والمواعيد مع أمثلة دقيقة جداً لكل جسم طلب (Request) واستجابة راجعة (Response) بدون أي اختصار، ومطابق تماماً للكود المصدري الحالي.

---

## 1. وحدة الفواتير (Invoices Module)

### 1.1 قائمة الفواتير (Index)
- **المسار:** `GET /api/v1/invoices`
- **Params (Query String):**
  - `page`: رقم الصفحة.
  - `status`: sent, paid, overdue, draft, cancelled.
  - `client_id`: فلترة بعميل محدد.
  - `user_id`: فلترة بمنشئ الفاتورة.
  - `search`: بحث برقم الفاتورة.
  - `due_date_from`: تاريخ الاستحقاق من.
  - `due_date_to`: تاريخ الاستحقاق إلى.
  
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم جلب الفواتير بنجاح",
  "data": [
    {
      "id": 10,
      "invoice_number": "INV-2024-001",
      "status": "paid",
      "client": {
        "id": 5,
        "name": "شركة الأفق",
        "email": "info@alofuq.com",
        "phone": "0500000000"
      },
      "subtotal": "1000.00",
      "tax_rate": 15,
      "tax_amount": "150.00",
      "discount": "0.00",
      "total": "1150.00",
      "due_date": "2024-02-01",
      "paid_at": "2024-01-20 15:30",
      "created_at": "2024-01-15 10:00"
    }
  ],
  "meta": { "total": 50, "current_page": 1, "per_page": 15, "last_page": 4 }
}
```

### 1.2 إنشاء فاتورة (Store)
- **المسار:** `POST /api/v1/invoices`
- **Request Body:**
```json
{
  "client_id": 5,
  "city_id": 1, // Optional (مكان إصدار الفاتورة)
  "status": "draft", // Optional (draft, sent, paid...)
  "due_date": "2024-02-01", // Optional
  "tax_rate": 15, // Optional (Percentage)
  "discount": 0, // Optional (Amount)
  "notes": "يرجى التحويل إلى الحساب البنكي...",
  "tags": [1, 3], // Optional (Invoice Tags IDs)
  
  "items": [
    {
      "product_id": 10, // Optional (إذا كان منتجاً معرفاً مسبقاً)
      "description": "تصميم موقع إلكتروني", // Required
      "quantity": 1, // Required
      "unit_price": 5000, // Required
      "discount": 0 // Optional per item
    },
    {
      "description": "استضافة لمدة سنة",
      "quantity": 1,
      "unit_price": 500
    }
  ]
}
```
- **Success Response (201):**
```json
{
  "success": true,
  "message": "تم إنشاء الفاتورة بنجاح",
  "data": {
    "id": 12,
    "invoice_number": "INV-2024-002",
    "status": "draft",
    "total": "5500.00",
    "items_count": 2,
    // ... باقي تفاصيل الفاتورة
  }
}
```

### 1.3 تفاصيل فاتورة (Show)
- **المسار:** `GET /api/v1/invoices/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 12,
    "invoice_number": "INV-2024-002",
    "status": "draft",
    "client": { "id": 5, "name": "..." },
    "user": { "id": 1, "name": "Admin" },
    "city": { "id": 1, "name": "الرياض" },
    
    // Financials (مفرمتة string بـ 2 decimal)
    "subtotal": "5500.00",
    "tax_rate": 0,
    "tax_amount": "0.00",
    "discount": "0.00",
    "total": "5500.00",
    
    "due_date": "2024-02-01",
    "paid_at": null,
    "notes": "...",
    
    "items": [
      {
        "id": 55,
        "product_id": 10,
        "product_name": "تصميم موقع",
        "description": "تصميم موقع إلكتروني",
        "quantity": 1,
        "unit_price": "5000.00",
        "discount": "0.00",
        "total": "5000.00"
      }
    ],
    "tags": [
      { "id": 1, "name": "هام", "color": "#FF0000" }
    ],
    "created_at": "2024-01-15 12:00"
  }
}
```

### 1.4 تعديل فاتورة (Update)
- **المسار:** `PUT /api/v1/invoices/{id}`
- **Request Body:** نفس كائن الإضافة (Store). يجب إرسال المصفوفة `items` كاملة حيث سيقوم النظام بمزامنتها (حذف القديم وإضافة الجديد).
- **Success Response (200):** يعيد الفاتورة المحدثة.

### 1.5 حذف فاتورة (Delete)
- **المسار:** `DELETE /api/v1/invoices/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم حذف الفاتورة بنجاح"
}
```

### 1.6 تغيير حالة الفاتورة (Change Status)
- **المسار:** `PATCH /api/v1/invoices/{id}/status`
- **Request Body:** `{ "status": "paid" }`
- **Success Response (200):** يعيد الفاتورة مع الحالة الجديدة.

### 1.7 إرسال الفاتورة (Send to Client)
- **المسار:** `POST /api/v1/invoices/{id}/send`
- **Request Body:** `{ "channels": ["whatsapp", "sms"] }`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم إرسال الفاتورة للعميل بنجاح"
}
```

### 1.8 تحميل PDF (Download PDF)
- **المسار:** `GET /api/v1/invoices/{id}/pdf`
- **Status:** يقوم بتحميل ملف الفاتورة بصيغة PDF مباشرة (Content-Type: application/pdf).

---

## 2. وحدة المواعيد (Appointments Module)

### 2.1 قائمة المواعيد (Index)
- **المسار:** `GET /api/v1/appointments`
- **Params:** `status`, `type` (meeting, call, visit), `client_id`, `date_from`, `date_to`.
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم جلب المواعيد بنجاح",
  "data": [
    {
      "id": 5,
      "title": "اجتماع أولي",
      "type": "meeting",
      "status": "scheduled",
      "start_at": "2024-02-01 10:00",
      "end_at": "2024-02-01 11:00",
      "client": { "id": 1, "name": "..." }
    }
  ],
  "meta": { "total": 10, "current_page": 1 }
}
```

### 2.2 إنشاء موعد (Store)
- **المسار:** `POST /api/v1/appointments`
- **Request Body:**
```json
{
  "client_id": 1,
  "title": "مكالمة متابعة",
  "type": "call", // meeting, call, visit
  "start_at": "2024-02-02 14:00",
  "end_at": "2024-02-02 14:30",
  "status": "scheduled", // Optional (scheduled, completed, cancelled, no_show)
  "description": "مناقشة العرض المالي", // Optional
  "location": "Zoom", // Optional
  "reminder_at": "2024-02-02 13:50" // Optional
}
```
- **Success Response (201):** يعيد كائن الموعد المنشأ.

### 2.3 تفاصيل موعد (Show)
- **المسار:** `GET /api/v1/appointments/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "مكالمة متابعة",
    "description": "...",
    "type": "call",
    "status": "scheduled",
    "location": "Zoom",
    "start_at": "2024-02-02 14:00",
    "end_at": "2024-02-02 14:30",
    "reminder_at": "2024-02-02 13:50",
    "client": { "id": 1, "name": "...", "phone": "..." },
    "user": { "id": 1, "name": "..." },
    "created_at": "..."
  }
}
```

### 2.4 تحديث موعد (Update)
- **المسار:** `PUT /api/v1/appointments/{id}`
- **Request Body:** نفس حقول الإضافة ولكنها اختيارية للتعديل.
- **Success Response (200):** يعيد الموعد المحدث.

### 2.5 حذف موعد (Delete)
- **المسار:** `DELETE /api/v1/appointments/{id}`
- **Success Response (200):**
```json
{
  "success": true,
  "message": "تم حذف الموعد بنجاح"
}
```

### 2.6 تغيير حالة الموعد (Change Status)
- **المسار:** `PATCH /api/v1/appointments/{id}/status`
- **Request Body:** `{ "status": "completed" }`
- **Success Response (200):** يعيد الموعد بعد تحديث حالته.
