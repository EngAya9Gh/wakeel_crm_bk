# 🔗 Public API للنماذج الخارجية - دليل سريع

## ✅ تم الإنجاز

تم إنشاء **Public API Endpoint** كامل لاستقبال بيانات العملاء من نماذج الموقع الإلكتروني.

---

## 📁 الملفات المُنشأة

```
✅ app/Http/Controllers/Api/Public/LeadController.php
✅ app/Http/Requests/Api/Public/StoreLeadRequest.php
✅ app/Http/Middleware/ValidateApiKey.php
✅ database/seeders/WebsiteFormsSourceSeeder.php
✅ DOCS_PUBLIC_API_INTEGRATION.md (للمبرمج الخارجي)
✅ DOCS_PUBLIC_API_INTERNAL.md (للفريق التقني)
✅ crm_wakeel_postman_collection.json (تم التحديث)
```

---

## 🚀 خطوات التفعيل

### 1. إضافة API Key

في ملف `.env`:

```bash
PUBLIC_API_KEYS=your_secure_api_key_here
```

**توليد API Key آمن:**
```bash
php artisan tinker
>>> Str::random(40)
```

### 2. تشغيل الـ Seeder (تم ✅)

```bash
php artisan db:seed --class=WebsiteFormsSourceSeeder
```

### 3. اختبار الـ API

```bash
curl -X POST http://localhost:8000/api/public/v1/leads \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -d '{
    "name": "عميل تجريبي",
    "phone": "0501234567",
    "email": "test@example.com",
    "subject": "اختبار",
    "message": "رسالة تجريبية",
    "source": "contact_form"
  }'
```

---

## 📖 التوثيق

### للمبرمج الخارجي
اقرأ: **`DOCS_PUBLIC_API_INTEGRATION.md`**
- يحتوي على كل التفاصيل اللازمة للربط
- أمثلة بـ JavaScript, PHP, cURL
- شرح كامل للأخطاء والحلول

### للفريق التقني
اقرأ: **`DOCS_PUBLIC_API_INTERNAL.md`**
- البنية التقنية الكاملة
- Flow Diagram
- استكشاف الأخطاء
- المراقبة والتتبع

---

## 🔐 الأمان

- ✅ API Key Authentication
- ✅ Validation للبيانات
- ✅ Phone Number Normalization
- ✅ Unique Phone Constraint
- ✅ Error Handling

---

## 📊 الحقول المطلوبة

| الحقل | مطلوب | الوصف |
|------|------|------|
| `name` | ✅ | الاسم الكامل (3-255 حرف) |
| `phone` | ✅ | رقم الجوال السعودي |
| `source` | ✅ | `contact_form`, `landing_page`, `website_form` |
| `email` | ❌ | البريد الإلكتروني |
| `company` | ❌ | اسم الشركة |
| `address` | ❌ | العنوان |
| `subject` | ❌ | موضوع الرسالة |
| `message` | ❌ | نص الرسالة |

---

## 🧪 Postman Collection

تم إضافة 3 أمثلة في **Postman Collection**:
1. Submit Lead (Contact Form)
2. Submit Lead (Landing Page)
3. Submit Lead (Minimal Data)

**المتغيرات المطلوبة:**
- `base_url`: `http://localhost:8000/api/v1`
- `public_api_key`: API Key الخاص بك

---

## 📞 ما يجب إرساله للمبرمج الخارجي

1. **الملف:** `DOCS_PUBLIC_API_INTEGRATION.md`
2. **API Key:** (قم بتوليده وإرساله بشكل آمن)
3. **Base URL:** رابط الـ Production أو Staging
4. **Postman Collection:** (اختياري)

---

## ✨ الميزات

- ✅ تسجيل تلقائي للعملاء
- ✅ تتبع المصدر (Contact Form vs Landing Page)
- ✅ حفظ الرسائل كـ Comments
- ✅ تنسيق أرقام الجوال تلقائياً
- ✅ منع التسجيل المكرر
- ✅ استجابات واضحة بالعربية

---

## 🔄 التحديثات المستقبلية المقترحة

- [ ] Rate Limiting (60 طلب/دقيقة)
- [ ] Webhook Notifications
- [ ] Auto-Assignment للموظفين
- [ ] Lead Scoring
- [ ] CORS Configuration

---

**تم الإنجاز بواسطة:** Antigravity AI  
**التاريخ:** 2026-01-24
