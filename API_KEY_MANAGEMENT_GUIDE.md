# 🔐 دليل إدارة API Keys - توضيح شامل

## ❓ الأسئلة الشائعة

### 1️⃣ كيف سيتم توليد API Key؟

**الجواب:** **أنتم** (فريق CRM) تقومون بتوليد الـ API Key.

#### طرق التوليد:

**الطريقة 1: باستخدام السكريبت (الأسهل)**
```bash
cd /Users/ayaghoury/Documents/crm_wakeel_bk
./scripts/generate-api-key.sh
```

**الطريقة 2: باستخدام Laravel Tinker**
```bash
php artisan tinker
>>> Str::random(40)
=> "xJ8kL2mN9pQ4rS6tU7vW8xY0zA1bC3dE5fG7hI9j"
>>> exit
```

**الطريقة 3: باستخدام OpenSSL**
```bash
openssl rand -base64 30 | tr -d "=+/" | cut -c1-40
```

---

### 2️⃣ من يضيفه في .env؟

**الجواب:** **أنتم** تضيفونه في ملف `.env` الخاص **بسيرفر CRM** (عندكم).

#### الموقع:
```
/Users/ayaghoury/Documents/crm_wakeel_bk/.env
```

#### الإضافة:
```bash
PUBLIC_API_KEYS=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ
```

#### إذا كان لديكم عدة مواقع:
```bash
PUBLIC_API_KEYS=key_for_website1,key_for_website2,key_for_website3
```

---

## 📋 العملية الكاملة خطوة بخطوة

### المرحلة 1: التحضير (عندكم)

#### الخطوة 1: توليد API Key
```bash
./scripts/generate-api-key.sh
```

**النتيجة:**
```
✅ Generated API Key:
    JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ
```

#### الخطوة 2: إضافة الـ Key في `.env` (عندكم)
```bash
# افتح ملف .env
nano .env

# أضف في النهاية:
PUBLIC_API_KEYS=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ

# احفظ الملف
```

#### الخطوة 3: إعادة تحميل الـ Config (مهم!)
```bash
php artisan config:clear
php artisan config:cache
```

---

### المرحلة 2: إرسال المعلومات للمبرمج الخارجي

#### ما ترسلونه:

**1. الملف:**
```
RESPONSE_TO_DEVELOPER.md
```

**2. API Key (بشكل آمن):**
```
JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ
```

**3. الرابط:**
```
https://your-domain.com/api/public/v1/leads
```

#### طريقة الإرسال الآمنة:
- ✅ عبر البريد الإلكتروني المشفر
- ✅ عبر رسالة WhatsApp خاصة
- ✅ عبر منصة آمنة (مثل 1Password, LastPass)
- ❌ **لا ترسلوه عبر:** Slack عام، GitHub، أي مكان عام

---

### المرحلة 3: المبرمج الخارجي يستخدم الـ Key

#### في كود الموقع (عندهم):

**JavaScript:**
```javascript
// في ملف .env الخاص بهم (Server-Side)
NEXT_PUBLIC_CRM_API_KEY=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ

// في الكود
fetch('https://your-domain.com/api/public/v1/leads', {
  headers: {
    'X-API-Key': process.env.CRM_API_KEY  // من .env عندهم
  }
})
```

**PHP:**
```php
// في ملف .env الخاص بهم
CRM_API_KEY=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ

// في الكود
$apiKey = getenv('CRM_API_KEY');  // من .env عندهم
```

---

## 🔄 السيناريوهات المختلفة

### السيناريو 1: موقع واحد فقط

**عندكم (.env):**
```bash
PUBLIC_API_KEYS=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ
```

**عندهم (كود الموقع):**
```javascript
headers: {
  'X-API-Key': 'JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ'
}
```

---

### السيناريو 2: عدة مواقع

**عندكم (.env):**
```bash
PUBLIC_API_KEYS=key_website1_abc123,key_website2_def456,key_website3_ghi789
```

**الموقع 1 (عندهم):**
```javascript
headers: {
  'X-API-Key': 'key_website1_abc123'
}
```

**الموقع 2 (عندهم):**
```javascript
headers: {
  'X-API-Key': 'key_website2_def456'
}
```

---

### السيناريو 3: بيئة اختبار + إنتاج

**عندكم (.env للاختبار):**
```bash
PUBLIC_API_KEYS=test_key_staging_123
```

**عندكم (.env للإنتاج):**
```bash
PUBLIC_API_KEYS=prod_key_live_456
```

**عندهم (كود الموقع):**
```javascript
const apiKey = process.env.NODE_ENV === 'production' 
  ? 'prod_key_live_456'      // للإنتاج
  : 'test_key_staging_123';  // للاختبار
```

---

## 🔐 الأمان

### ✅ ما يجب فعله:

1. **توليد Keys قوية:** 40 حرف على الأقل
2. **تخزين آمن:** في `.env` فقط (عندكم وعندهم)
3. **عدم المشاركة:** لا تضعوه في Git, Slack عام، إلخ
4. **تدوير دوري:** غيروا الـ Keys كل 6 أشهر
5. **Keys منفصلة:** كل موقع له Key خاص

### ❌ ما يجب تجنبه:

1. **لا تضعوه في Frontend Code** (JavaScript المرئي)
2. **لا تشاركوه علناً** (GitHub, Slack عام)
3. **لا تستخدموا نفس الـ Key** لكل المواقع
4. **لا ترسلوه عبر HTTP** (استخدموا HTTPS فقط)

---

## 🧪 الاختبار

### اختبار الـ API Key:

```bash
# اختبار صحيح (يجب أن ينجح)
curl -X POST http://localhost:8000/api/public/v1/leads \
  -H "Content-Type: application/json" \
  -H "X-API-Key: JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ" \
  -d '{"name":"test","phone":"0501234567","source":"contact_form"}'

# النتيجة المتوقعة:
# {"success":true,"message":"تم تسجيل العميل بنجاح في النظام",...}
```

```bash
# اختبار خاطئ (يجب أن يفشل)
curl -X POST http://localhost:8000/api/public/v1/leads \
  -H "Content-Type: application/json" \
  -H "X-API-Key: wrong_key_123" \
  -d '{"name":"test","phone":"0501234567","source":"contact_form"}'

# النتيجة المتوقعة:
# {"success":false,"message":"API Key غير صحيح",...}
```

---

## 🔄 تغيير أو إضافة Keys

### إضافة Key جديد:

```bash
# 1. ولد Key جديد
./scripts/generate-api-key.sh

# 2. أضفه في .env (مع الفاصلة)
PUBLIC_API_KEYS=old_key_123,new_key_456

# 3. أعد تحميل Config
php artisan config:clear
php artisan config:cache
```

### إلغاء Key قديم:

```bash
# 1. احذفه من .env
PUBLIC_API_KEYS=new_key_456  # حذفنا old_key_123

# 2. أعد تحميل Config
php artisan config:clear
php artisan config:cache

# 3. أبلغ المبرمج الخارجي بالتوقف عن استخدام الـ Key القديم
```

---

## 📊 ملخص سريع

| الخطوة | من يقوم بها | أين |
|--------|-------------|-----|
| **توليد API Key** | أنتم (CRM) | سيرفر CRM |
| **إضافة في .env** | أنتم (CRM) | `/path/to/crm/.env` |
| **إرسال الـ Key** | أنتم (CRM) | بريد/WhatsApp آمن |
| **استخدام الـ Key** | المبرمج الخارجي | كود الموقع |
| **تخزين الـ Key** | المبرمج الخارجي | `.env` عندهم |

---

## ✅ الخلاصة

### عندكم (CRM):
```bash
# في .env
PUBLIC_API_KEYS=JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ
```

### عندهم (الموقع):
```javascript
// في الكود
headers: {
  'X-API-Key': 'JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ'
}
```

### النتيجة:
```
✅ الموقع يرسل البيانات → CRM يتحقق من الـ Key → يسجل العميل
```

---

**تم التوضيح بواسطة:** Antigravity AI  
**التاريخ:** 2026-01-24
