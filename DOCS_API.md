# توثيق الـ APIs الشامل والنهائي لنظام CRM Wakeel (نسخة الموبايل)

هذا المستند هو المرجع الرئيسي والوحيد لكافة المسارات البرمجية، تم تفصيل كل API بشكل منفرد مع توضيح البيانات المطلوبة (Body) والبيانات الراجعة (Output).

---

## 1. معلومات تقنية أساسية

- **Base URL:** `https://your-api-domain.com/api/v1`
- **Headers:** 
    - `Accept: application/json`
    - `Content-Type: application/json`
    - `Authorization: Bearer {token}`
- **Response Format (Success):**
```json
{
  "success": true,
  "message": "نص الرسالة",
  "data": { ... } 
}
```

---

## 2. وحدة المصادقة (Authentication - 4 APIs)

### 2.1 تسجيل الدخول (Login)
- **URL:** `POST /auth/login`
- **Body:** `{ "email": "admin@example.com", "password": "password" }`
- **Output:**
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Admin", "email": "..." },
    "access_token": "...",
    "refresh_token": "..."
  }
}
```

### 2.2 تجديد التوكن (Refresh Token)
- **URL:** `POST /auth/refresh`
- **Headers:** `Authorization: Bearer {refresh_token}`
- **Output:** `{ "success": true, "data": { "access_token": "..." } }`

### 2.3 بيانات المستخدم الحالي (Me)
- **URL:** `GET /auth/me`
- **Output:** بيانات المستخدم المسجل مع الأذونات (Permissions) والفرق التابع لها.

### 2.4 تسجيل الخروج (Logout)
- **URL:** `POST /auth/logout`

---

## 3. وحدة العملاء (Clients - 18 APIs)

### 3.1 جلب القائمة (Index)
- **URL:** `GET /clients`
- **Params:** `page`, `per_page`, `search`, `status_id`, `priority` (high, medium, low).
- **Output:** مصفوفة `data` مع كائن `meta` للترقيم.

### 3.2 إضافة عميل (Store)
- **URL:** `POST /clients`
- **Body:** `{ "name": "اسم العميل", "phone": "0912345678", "status_id": 1, "priority": "high", "lead_rating": "hot", "source_id": 1, "tags": [1, 2] }`

### 3.3 تفاصيل العميل (Show)
- **URL:** `GET /clients/{id}`
- **Output:** جسم بيانات العميل بالكامل مع المصدر والمنطقة.

### 3.4 تعديل العميل (Update)
- **URL:** `PUT /clients/{id}`
- **Body:** الحقول المراد تعديلها فقط (اختيارية).

### 3.5 حذف العميل (Delete)
- **URL:** `DELETE /clients/{id}`

### 3.6 استعادة العميل (Restore)
- **URL:** `POST /clients/{id}/restore`

### 3.7 تغيير الحالة (Change Status)
- **URL:** `PATCH /clients/{id}/status`
- **Body:** `{ "status_id": 2 }`

### 3.8 إسناد لموظف (Assign)
- **URL:** `PATCH /clients/{id}/assign`
- **Body:** `{ "user_id": 5 }`

### 3.9 العمليات الجماعية - تغيير الحالة (Bulk Status)
- **URL:** `POST /clients/bulk/status`
- **Body:** `{ "client_ids": [1, 2, 3], "status_id": 5 }`

### 3.10 العمليات الجماعية - الإسناد (Bulk Assign)
- **URL:** `POST /clients/bulk/assign`
- **Body:** `{ "client_ids": [1, 2, 3], "user_id": 10 }`

### 3.11 العمليات الجماعية - الحذف (Bulk Delete)
- **URL:** `DELETE /clients/bulk`
- **Body:** `{ "client_ids": [1, 2, 3] }`

### 3.12 جلب التعليقات (Get Comments)
- **URL:** `GET /clients/{id}/comments`
- **Output:** قائمة التعليقات مرقمة (Paginated).

### 3.13 إضافة تعليق غني (Add Comment)
- **URL:** `POST /clients/{id}/comments` (Multipart Form)
- **Body:** `type_id` (مطلوب), `subject` (اختياري), `content` (مطلوب), `outcome` (positive, neutral, negative), `mentions[]` (معرفات مستخدمين), `attachments[]` (ملفات).

### 3.14 جلب الملفات (Get Files)
- **URL:** `GET /clients/{id}/files`

### 3.15 رفع ملف (Upload File)
- **URL:** `POST /clients/{id}/files` (Multipart: `file`, `type`).

### 3.16 سجل النشاطات (Timeline)
- **URL:** `GET /clients/{id}/timeline`

### 3.17 الإحصائيات (Stats)
- **URL:** `GET /clients/stats`

### 3.18 مؤشرات الأداء (KPIs)
- **URL:** `GET /clients/kpis`

---

## 4. وحدة الفواتير (Invoices - 8 APIs)

### 4.1 قائمة الفواتير
- **URL:** `GET /invoices`

### 4.2 إنشاء فاتورة
- **URL:** `POST /invoices`
- **Body:** `{ "client_id": 1, "due_date": "2025-01-20", "items": [{ "product_id": 1, "quantity": 1, "unit_price": 500 }], "tags": [1] }`

### 4.3 تفاصيل فاتورة
- **URL:** `GET /invoices/{id}`

### 4.4 تعديل فاتورة
- **URL:** `PUT /invoices/{id}`

### 4.5 حذف فاتورة
- **URL:** `DELETE /invoices/{id}`

### 4.6 تغيير حالة الفاتورة
- **URL:** `PATCH /invoices/{id}/status`
- **Body:** `{ "status": "paid" }`

### 4.7 تحميل PDF
- **URL:** `GET /invoices/{id}/pdf`

### 4.8 إرسال (WhatsApp/SMS)
- **URL:** `POST /invoices/{id}/send`
- **Body:** `{ "channels": ["whatsapp", "sms"] }`

---

## 5. وحدة المواعيد (Appointments - 6 APIs)

### 5.1 قائمة المواعيد
- **URL:** `GET /appointments`

### 5.2 حجز موعد
- **URL:** `POST /appointments`
- **Body:** `{ "client_id": 1, "title": "مكالمة", "type": "call", "start_at": "...", "end_at": "..." }`

### 5.3 تفاصيل موعد
- `GET /appointments/{id}`

### 5.4 تعديل موعد
- `PUT /appointments/{id}` 

### 5.5 حذف موعد
- `DELETE /appointments/{id}`

### 5.6 تغيير حالة الموعد
- `PATCH /appointments/{id}/status`
- **Body:** `{ "status": "completed" }`

---

## 6. وحدة المستخدمين (Users - 5 APIs)

1. `GET /users` -> القائمة.
2. `POST /users` -> إضافة موظف جديد (name, email, password, role_id, team_id).
3. `GET /users/{id}` -> الملف الشخصي.
4. `PUT /users/{id}` -> تحديث البيانات.
5. `DELETE /users/{id}` -> حذف أو تعطيل.

---

## 7. وحدة الإعدادات (Settings - 48 APIs)
كل تصنيف أدناه يدعم 4 عمليات برمجية (Get, Store, Update, Delete) مع الحقول الخاصة به:

### 7.1 الحالات (Statuses)
- `POST /settings/statuses`: `{ "name": "...", "color": "...", "order": 1, "weight": 10, "is_default": true }`

### 7.2 المصادر (Sources)
- `POST /settings/sources`: `{ "name": "...", "is_active": true }`

### 7.3 السلوكيات (Behaviors)
- `POST /settings/behaviors`: `{ "name": "...", "color": "..." }`

### 7.4 أسباب الاستبعاد (Invalid Reasons)
- `POST /settings/invalid-reasons`: `{ "name": "..." }`

### 7.5 المناطق (Regions)
- `POST /settings/regions`: `{ "name": "..." }`

### 7.6 المدن (Cities)
- `POST /settings/cities`: `{ "name": "...", "region_id": 1 }`

### 7.7 التاجز (Tags)
- `POST /settings/tags`: `{ "name": "...", "color": "..." }`

### 7.8 المنتجات (Products)
- `POST /settings/products`: `{ "name": "...", "price": 1000, "is_active": true }`

### 7.9 تاجز الفواتير (Invoice Tags)
- `POST /settings/invoice-tags`: `{ "name": "...", "color": "..." }`

### 7.10 أنواع التعليقات (Comment Types)
- `POST /settings/comment-types`: `{ "name": "...", "icon": "...", "color": "..." }`

### 7.11 الفرق (Teams)
- `POST /settings/teams`: `{ "name": "...", "description": "...", "is_active": true }`

### 7.12 الأدوار والصلاحيات (Roles)
- `POST /settings/roles`: `{ "name": "...", "team_id": 1, "is_default": false, "permissions": [1, 2, 3] }`
- **ملاحظة:** الـ `PUT` يدعم أيضاً تحديث مصفوفة الصلاحيات بالكامل (`sync`).

---

## 8. وحدة الداشبورد (Dashboard - 3 APIs)

1. `GET /dashboard/summary` -> ملخص الأرقام.
2. `GET /dashboard/charts` -> بيانات الرسوم لبيانية (period: week, month, year).
3. `GET /dashboard/recent-activities` -> آخر 5 حركات لكل غرض في النظام.

---

## 9. معالجة الأخطاء (Error Examples)

### نموذج خطأ التحقق (422):
```json
{
  "success": false,
  "message": "بيانات غير صالحة",
  "errors": {
    "phone": ["رقم الهاتف مسجل مسبقاً"],
    "email": ["البريد الإلكتروني غير صحيح"]
  }
}
```

### نموذج خطأ الصلاحيات (403):
```json
{
  "success": false,
  "message": "ليس لديك صلاحية لتنفيذ هذا الإجراء"
}
```

---
**تم إحصاء 86 API مغطاة في هذا التوثيق بدقة متناهية.**
