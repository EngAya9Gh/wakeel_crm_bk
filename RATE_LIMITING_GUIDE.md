# 🚦 دليل Rate Limiting - الشرح الكامل

## 📍 أين يتم تحديد Rate Limiting؟

### الموقع الرئيسي: `routes/api.php`

```php
// السطر 10 في routes/api.php
Route::prefix('public/v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

---

## 🔍 شرح الكود

### `throttle:60,1`

```
throttle:60,1
         │  │
         │  └─── الفترة الزمنية (بالدقائق)
         │
         └────── عدد الطلبات المسموحة
```

**المعنى:**
- ✅ **60 طلب** في **دقيقة واحدة**
- ✅ لكل **API Key** على حدة
- ✅ بعد 60 طلب: يحصل على `429 Too Many Requests`
- ✅ بعد دقيقة: يتم إعادة تعيين العداد

---

## ⚙️ تغيير الإعدادات

### السيناريو 1: زيادة عدد الطلبات

**من 60 إلى 120 طلب في الدقيقة:**

```php
// في routes/api.php - السطر 10
Route::prefix('public/v1')->middleware(['api.key', 'throttle:120,1'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

### السيناريو 2: تغيير الفترة الزمنية

**60 طلب كل 5 دقائق:**

```php
Route::prefix('public/v1')->middleware(['api.key', 'throttle:60,5'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

### السيناريو 3: حد أعلى

**1000 طلب في الساعة:**

```php
Route::prefix('public/v1')->middleware(['api.key', 'throttle:1000,60'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

### السيناريو 4: إزالة Rate Limiting (غير موصى به)

```php
// إزالة throttle من middleware
Route::prefix('public/v1')->middleware(['api.key'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

---

## 🎯 Rate Limiting لكل API Key

### كيف يعمل؟

Laravel يستخدم **API Key** كمعرّف فريد لتتبع الطلبات:

```
API Key 1: 60 طلب/دقيقة ✅
API Key 2: 60 طلب/دقيقة ✅
API Key 3: 60 طلب/دقيقة ✅
```

**كل API Key له عداد منفصل!**

---

## 📊 أمثلة عملية

### مثال 1: موقع واحد (60 طلب/دقيقة)

```
الوقت: 10:00:00
الطلب 1-60: ✅ 201 Created
الطلب 61: ❌ 429 Too Many Requests

الوقت: 10:01:00
العداد يعود للصفر ✅
الطلب 1-60: ✅ 201 Created
```

### مثال 2: موقعان (كل واحد 60 طلب/دقيقة)

```
الموقع 1 (API Key 1):
  الطلب 1-60: ✅ 201 Created
  الطلب 61: ❌ 429

الموقع 2 (API Key 2):
  الطلب 1-60: ✅ 201 Created  (عداد منفصل!)
  الطلب 61: ❌ 429
```

---

## 🔧 الإعدادات المتقدمة

### استخدام Named Rate Limiter

#### الخطوة 1: تعريف في `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('public-api', function (Request $request) {
        return Limit::perMinute(60)
            ->by($request->header('X-API-Key'));
    });
}
```

#### الخطوة 2: استخدام في `routes/api.php`

```php
Route::prefix('public/v1')->middleware(['api.key', 'throttle:public-api'])->group(function () {
    Route::post('leads', [\App\Http\Controllers\Api\Public\LeadController::class, 'store']);
});
```

---

## 📈 Rate Limiting حسب الوقت

### مثال: حدود مختلفة حسب الوقت

```php
RateLimiter::for('public-api', function (Request $request) {
    $hour = now()->hour;
    
    // ساعات الذروة (9 صباحاً - 5 مساءً): 120 طلب/دقيقة
    if ($hour >= 9 && $hour < 17) {
        return Limit::perMinute(120)
            ->by($request->header('X-API-Key'));
    }
    
    // خارج ساعات الذروة: 60 طلب/دقيقة
    return Limit::perMinute(60)
        ->by($request->header('X-API-Key'));
});
```

---

## 🎨 Rate Limiting حسب API Key

### مثال: حدود مختلفة لكل API Key

```php
RateLimiter::for('public-api', function (Request $request) {
    $apiKey = $request->header('X-API-Key');
    
    // API Keys مميزة (Premium)
    $premiumKeys = [
        'premium_key_1',
        'premium_key_2',
    ];
    
    if (in_array($apiKey, $premiumKeys)) {
        return Limit::perMinute(300); // 300 طلب/دقيقة
    }
    
    // API Keys عادية
    return Limit::perMinute(60); // 60 طلب/دقيقة
});
```

---

## 🧪 اختبار Rate Limiting

### اختبار يدوي:

```bash
# إرسال 61 طلب سريعاً
for i in {1..61}; do
  echo "Request $i:"
  curl -X POST http://localhost:8000/api/public/v1/leads \
    -H "Content-Type: application/json" \
    -H "X-API-Key: JlQlzqUN1HFfeenMO5Iz8eJYMtOMxPnE772sqxJ" \
    -d '{"name":"Test","phone":"0501234567","source":"contact_form"}'
  echo ""
done
```

**النتيجة المتوقعة:**
```
Request 1-60: {"success":true,...}
Request 61: {"message":"Too Many Attempts.",...}
```

---

## 📊 مراقبة Rate Limiting

### في Laravel Logs:

```bash
# عرض الطلبات المرفوضة
tail -f storage/logs/laravel.log | grep "429"
```

### إنشاء Middleware مخصص للتتبع:

```php
// app/Http/Middleware/LogRateLimitHits.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    if ($response->status() === 429) {
        Log::warning('Rate limit exceeded', [
            'api_key' => $request->header('X-API-Key'),
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
            'time' => now(),
        ]);
    }
    
    return $response;
}
```

---

## 🔄 Headers في الاستجابة

Laravel يضيف Headers تلقائياً:

```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
Retry-After: 15
```

**المعنى:**
- `X-RateLimit-Limit: 60` - الحد الأقصى: 60 طلب
- `X-RateLimit-Remaining: 45` - المتبقي: 45 طلب
- `Retry-After: 15` - أعد المحاولة بعد: 15 ثانية

---

## ⚠️ استجابة 429 Too Many Requests

```json
{
  "message": "Too Many Attempts.",
  "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

---

## 📝 التوصيات

### للإنتاج:

| السيناريو | الإعداد الموصى به |
|-----------|-------------------|
| **موقع صغير** | `throttle:60,1` (60/دقيقة) |
| **موقع متوسط** | `throttle:120,1` (120/دقيقة) |
| **موقع كبير** | `throttle:300,1` (300/دقيقة) |
| **Enterprise** | Custom Named Limiter |

### للاختبار:

```php
// بيئة الاختبار: حد أعلى
if (app()->environment('local', 'testing')) {
    return Limit::perMinute(1000);
}

// بيئة الإنتاج: حد عادي
return Limit::perMinute(60);
```

---

## 🔧 الملخص السريع

### الموقع:
```
routes/api.php - السطر 10
```

### الكود الحالي:
```php
->middleware(['api.key', 'throttle:60,1'])
```

### لتغيير الحد:
```php
// 120 طلب/دقيقة
->middleware(['api.key', 'throttle:120,1'])

// 1000 طلب/ساعة
->middleware(['api.key', 'throttle:1000,60'])

// إزالة الحد (غير موصى به)
->middleware(['api.key'])
```

### بعد التغيير:
```bash
php artisan config:clear
php artisan route:clear
```

---

**تم التوضيح بواسطة:** Antigravity AI  
**التاريخ:** 2026-01-24
