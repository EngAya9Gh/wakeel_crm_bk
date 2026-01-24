# 📊 دليل المراقبة الاحترافية للـ Public API

## ✅ ما تم تنفيذه

تم إنشاء نظام مراقبة احترافي كامل يتضمن:

1. ✅ **جدول قاعدة بيانات** (`api_logs`) - لحفظ كل الطلبات
2. ✅ **Middleware** (`LogApiRequests`) - للتسجيل التلقائي
3. ✅ **Model** (`ApiLog`) - للاستعلام والتحليل
4. ✅ **أمان** - إخفاء البيانات الحساسة

---

## 🗄️ 1. قاعدة البيانات

### جدول `api_logs`

يحفظ المعلومات التالية لكل طلب:

| الحقل | الوصف |
|------|------|
| `api_key` | آخر 8 أحرف من الـ API Key (مخفي) |
| `endpoint` | المسار (مثل: `public/v1/leads`) |
| `method` | POST, GET, etc. |
| `ip_address` | عنوان IP للطلب |
| `request_data` | بيانات الطلب (JSON) |
| `status_code` | 201, 422, 429, etc. |
| `response_data` | بيانات الاستجابة (JSON) |
| `success` | true/false |
| `error_type` | نوع الخطأ |
| `error_message` | رسالة الخطأ |
| `validation_errors` | أخطاء التحقق (JSON) |
| `response_time_ms` | وقت الاستجابة (ميلي ثانية) |
| `user_agent` | المتصفح/التطبيق |
| `source` | contact_form, landing_page, etc. |
| `created_at` | وقت الطلب |

---

## 📈 2. الاستعلامات المفيدة

### عرض آخر 10 طلبات

```sql
SELECT 
    id,
    api_key,
    endpoint,
    status_code,
    success,
    error_type,
    response_time_ms,
    created_at
FROM api_logs
ORDER BY created_at DESC
LIMIT 10;
```

### عرض الأخطاء فقط

```sql
SELECT 
    id,
    api_key,
    status_code,
    error_type,
    error_message,
    created_at
FROM api_logs
WHERE success = false
ORDER BY created_at DESC;
```

### إحصائيات اليوم

```sql
SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN success = false THEN 1 ELSE 0 END) as failed,
    AVG(response_time_ms) as avg_response_time,
    MAX(response_time_ms) as max_response_time
FROM api_logs
WHERE DATE(created_at) = CURDATE();
```

### الأخطاء حسب النوع

```sql
SELECT 
    error_type,
    COUNT(*) as count,
    MAX(created_at) as last_occurrence
FROM api_logs
WHERE success = false
GROUP BY error_type
ORDER BY count DESC;
```

### أكثر API Keys استخداماً

```sql
SELECT 
    api_key,
    COUNT(*) as total_requests,
    SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN success = false THEN 1 ELSE 0 END) as failed
FROM api_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY api_key
ORDER BY total_requests DESC;
```

### Rate Limit Violations

```sql
SELECT 
    api_key,
    ip_address,
    COUNT(*) as violations,
    MAX(created_at) as last_violation
FROM api_logs
WHERE status_code = 429
AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY api_key, ip_address
ORDER BY violations DESC;
```

---

## 💻 3. استخدام Laravel Eloquent

### في Tinker أو Controller:

```php
use App\Models\ApiLog;

// آخر 10 طلبات
$recent = ApiLog::latest()->limit(10)->get();

// الأخطاء فقط
$errors = ApiLog::failed()->latest()->get();

// الطلبات الناجحة
$successful = ApiLog::successful()->latest()->get();

// طلبات API Key معين
$keyLogs = ApiLog::byApiKey('***sqxJ')->get();

// أخطاء Rate Limit
$rateLimitErrors = ApiLog::rateLimitErrors()->latest()->get();

// أخطاء Validation
$validationErrors = ApiLog::validationErrors()->latest()->get();

// إحصائيات اليوم
$stats = [
    'total' => ApiLog::whereDate('created_at', today())->count(),
    'successful' => ApiLog::whereDate('created_at', today())->where('success', true)->count(),
    'failed' => ApiLog::whereDate('created_at', today())->where('success', false)->count(),
    'avg_response_time' => ApiLog::whereDate('created_at', today())->avg('response_time_ms'),
];
```

---

## 📊 4. Dashboard (اختياري - يمكن إنشاؤه لاحقاً)

### مثال على Controller للإحصائيات:

```php
<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class ApiMonitoringController extends Controller
{
    public function stats(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month
        
        $query = ApiLog::query();
        
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->subWeek(), now()]);
                break;
            case 'month':
                $query->whereBetween('created_at', [now()->subMonth(), now()]);
                break;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $query->count(),
                'successful' => (clone $query)->where('success', true)->count(),
                'failed' => (clone $query)->where('success', false)->count(),
                'avg_response_time' => round($query->avg('response_time_ms'), 2),
                'max_response_time' => $query->max('response_time_ms'),
                'errors_by_type' => (clone $query)
                    ->where('success', false)
                    ->selectRaw('error_type, COUNT(*) as count')
                    ->groupBy('error_type')
                    ->get(),
                'requests_by_source' => (clone $query)
                    ->selectRaw('source, COUNT(*) as count')
                    ->groupBy('source')
                    ->get(),
            ]
        ]);
    }
    
    public function recentErrors(Request $request)
    {
        $errors = ApiLog::failed()
            ->latest()
            ->limit($request->input('limit', 20))
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $errors
        ]);
    }
}
```

---

## 🔔 5. الإشعارات التلقائية (اختياري)

### إنشاء Command للمراقبة:

```php
<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MonitorApiErrors extends Command
{
    protected $signature = 'api:monitor-errors';
    protected $description = 'Monitor API errors and send alerts';

    public function handle()
    {
        // Check for high error rate in last hour
        $totalRequests = ApiLog::where('created_at', '>=', now()->subHour())->count();
        $failedRequests = ApiLog::where('created_at', '>=', now()->subHour())
            ->where('success', false)
            ->count();
        
        if ($totalRequests > 0) {
            $errorRate = ($failedRequests / $totalRequests) * 100;
            
            // Alert if error rate > 10%
            if ($errorRate > 10) {
                $this->sendAlert($errorRate, $failedRequests, $totalRequests);
            }
        }
        
        $this->info('Monitoring complete');
    }
    
    protected function sendAlert($errorRate, $failed, $total)
    {
        // Send email, Slack notification, etc.
        \Log::warning('High API error rate detected', [
            'error_rate' => round($errorRate, 2) . '%',
            'failed_requests' => $failed,
            'total_requests' => $total,
        ]);
        
        // يمكنك إضافة إرسال بريد إلكتروني هنا
    }
}
```

### جدولة المراقبة في `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run every 15 minutes
    $schedule->command('api:monitor-errors')->everyFifteenMinutes();
}
```

---

## 🧹 6. تنظيف السجلات القديمة

### Command للحذف التلقائي:

```php
<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class CleanOldApiLogs extends Command
{
    protected $signature = 'api:clean-logs {--days=30}';
    protected $description = 'Delete API logs older than specified days';

    public function handle()
    {
        $days = $this->option('days');
        
        $deleted = ApiLog::where('created_at', '<', now()->subDays($days))
            ->delete();
        
        $this->info("Deleted {$deleted} old API logs");
    }
}
```

### جدولة التنظيف:

```php
// في app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Clean logs older than 30 days, run daily at 2 AM
    $schedule->command('api:clean-logs --days=30')->dailyAt('02:00');
}
```

---

## 📱 7. أدوات المراقبة الخارجية (اختياري)

### الأدوات الموصى بها:

| الأداة | الوصف | السعر |
|-------|------|------|
| **Laravel Telescope** | مراقبة مدمجة في Laravel | مجاني |
| **Sentry** | تتبع الأخطاء في الوقت الفعلي | مجاني (حد معين) |
| **New Relic** | مراقبة الأداء الشاملة | مدفوع |
| **Datadog** | مراقبة البنية التحتية | مدفوع |
| **Grafana + Prometheus** | مراقبة مفتوحة المصدر | مجاني |

---

## 🔍 8. أمثلة على الاستعلامات المتقدمة

### الطلبات البطيئة (أكثر من ثانية)

```php
$slowRequests = ApiLog::where('response_time_ms', '>', 1000)
    ->latest()
    ->get();
```

### الطلبات من IP معين

```php
$ipRequests = ApiLog::where('ip_address', '192.168.1.1')
    ->latest()
    ->get();
```

### معدل النجاح لكل مصدر

```php
$successRateBySource = ApiLog::selectRaw('
    source,
    COUNT(*) as total,
    SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN success = true THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
')
->groupBy('source')
->get();
```

---

## 📊 9. تقرير يومي تلقائي

### Command لإرسال تقرير يومي:

```php
<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class SendDailyApiReport extends Command
{
    protected $signature = 'api:daily-report';
    protected $description = 'Send daily API usage report';

    public function handle()
    {
        $yesterday = today()->subDay();
        
        $stats = [
            'total' => ApiLog::whereDate('created_at', $yesterday)->count(),
            'successful' => ApiLog::whereDate('created_at', $yesterday)->where('success', true)->count(),
            'failed' => ApiLog::whereDate('created_at', $yesterday)->where('success', false)->count(),
            'avg_response_time' => round(ApiLog::whereDate('created_at', $yesterday)->avg('response_time_ms'), 2),
            'errors_by_type' => ApiLog::whereDate('created_at', $yesterday)
                ->where('success', false)
                ->selectRaw('error_type, COUNT(*) as count')
                ->groupBy('error_type')
                ->get(),
        ];
        
        // إرسال التقرير عبر البريد الإلكتروني
        \Log::info('Daily API Report', $stats);
        
        $this->info('Daily report sent');
    }
}
```

---

## ✅ الخلاصة

### ما تم تنفيذه:

✅ **جدول `api_logs`** - يحفظ كل الطلبات تلقائياً  
✅ **Middleware** - يسجل كل طلب بدون تأثير على الأداء  
✅ **Model** - استعلامات جاهزة للتحليل  
✅ **أمان** - إخفاء البيانات الحساسة (API Keys, Phone Numbers)  

### الخطوات التالية (اختيارية):

- [ ] إنشاء Dashboard للإحصائيات
- [ ] إضافة إشعارات تلقائية
- [ ] جدولة تنظيف السجلات القديمة
- [ ] دمج مع أدوات خارجية (Sentry, etc.)

---

**تم الإنشاء بواسطة:** Antigravity AI  
**التاريخ:** 2026-01-24
