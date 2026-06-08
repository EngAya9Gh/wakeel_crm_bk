# دليل رفع وتشغيل مشروع Wakeel CRM على سيرفر Hostinger VPS

يحتوي هذا الملف على الخطوات والتعليمات البرمجية التفصيلية لرفع المشروع وتفعيله على سيرفرك الخاص ليعمل تحت النطاق الفرعي `app.wakeel.cc`.

---

## 1. الاتصال بالسيرفر وتثبيت المتطلبات الأساسية

أولاً، افتح سطر الأوامر (Terminal) على جهازك وقم بالاتصال بالسيرفر عبر بروتوكول SSH:

```bash
ssh wakeel-app@app.wakeel.cc
```
*(أدخل كلمة المرور `f0AGZosaQk8bf8mJT7nY` عند طلبها)*

### التحقق من إصدار PHP والإضافات المطلوبة
يتطلب المشروع إصدار **PHP 8.2** أو أحدث. قم بالتحقق من الإصدار الحالي:
```bash
php -v
```

إذا لم تكن الإضافات المطلوبة مثبتة، قم بتثبيتها (مثال لنظام Ubuntu/Debian):
```bash
sudo apt update
sudo apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl
```

---

## 2. تحميل المشروع من GitHub

توجه إلى المجلد المخصص للموقع (على سبيل المثال `/var/www/app.wakeel.cc` أو المجلد الخاص بالمستخدم `wakeel-app`):

```bash
cd /var/www/app.wakeel.cc
```

إذا كان المجلد فارغاً، قم بجلب المشروع من GitHub:
```bash
git clone https://github.com/EngAya9Gh/wakeel_crm_bk.git .
```

---

## 3. إعداد ملف البيئة (.env)

قم بإنشاء ملف الـ `.env` من النسخة الافتراضية:
```bash
cp .env.example .env
```

افتح الملف للتعديل باستخدام محرر النصوص `nano`:
```bash
nano .env
```

قم بتعديل القيم التالية لتطابق إعدادات السيرفر وقاعدة البيانات:

```ini
APP_NAME="Wakeel CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.wakeel.cc

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wakeel_bk_db
DB_USERNAME=wakeel-app
DB_PASSWORD=f0AGZosaQk8bf8mJT7nY

# إعدادات الكاش والجلسات والمهام الخلفية (الافتراضية للمشروع)
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

*(اضغط `Ctrl + O` ثم `Enter` لحفظ الملف، وثم `Ctrl + X` للخروج)*

---

## 4. تثبيت الملحقات وتشغيل المشروع (Deployment)

نفذ الأوامر التالية بالترتيب لتجهيز المشروع للإنتاج:

### أ. تثبيت حزم الملحقات لـ Composer بدون حزم التطوير:
```bash
composer install --no-dev --optimize-autoloader
```

### ب. توليد مفتاح تشفير التطبيق (App Key):
```bash
php artisan key:generate
```

### ج. تشغيل التهجيرات (Migrations) لتجهيز قاعدة البيانات:
```bash
php artisan migrate --force
```

### د. إنشاء رابط التخزين العام (Storage Link):
```bash
php artisan storage:link
```

### هـ. تثبيت حزم الـ Node وعمل Build للملفات (إذا لزم الأمر):
```bash
npm install
npm run build
```

### و. تحسين أداء Laravel (تخزين الإعدادات والمسارات مؤقتاً):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. ضبط صلاحيات المجلدات

تحتاج ملفات Laravel إلى صلاحيات قراءة وكتابة لمجلدات التخزين والكاش لكي يعمل الموقع بدون مشاكل (خطأ 500):

```bash
# ضبط المالك للمجلد ليكون مستخدم السيرفر والموقع
sudo chown -R wakeel-app:www-data .

# إعطاء صلاحيات الكتابة لمجلدات الـ storage والـ cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 6. إعداد خادم الويب (Nginx)

يجب توجيه خادم Nginx إلى مجلد `public` الخاص بالمشروع وليس المجلد الرئيسي.

قم بإنشاء أو تعديل ملف إعدادات Nginx الخاص بالموقع:
```bash
sudo nano /etc/nginx/sites-available/app.wakeel.cc
```

تأكد من مطابقة ملف الإعداد للنموذج التالي (مع تعديل المسارات لتناسب سيرفرك):

```nginx
server {
    listen 80;
    server_name app.wakeel.cc;
    root /var/www/app.wakeel.cc/public; # تأكد من الإشارة لمجلد public

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # تأكد من إصدار php-fpm المثبت
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

قم بإنشاء رابط رمزي لتفعيل الموقع (إذا لم يكن مفعلاً):
```bash
sudo ln -s /etc/nginx/sites-available/app.wakeel.cc /etc/nginx/sites-enabled/
```

اختبر إعدادات Nginx للتأكد من خلوها من الأخطاء:
```bash
sudo nginx -t
```

أعد تشغيل خادم Nginx لتطبيق التغييرات:
```bash
sudo systemctl restart nginx
```

---

## 7. تأمين الموقع بشهادة SSL (Let's Encrypt)

قم بتثبيت وتفعيل شهادة الحماية المجانية SSL للتشفير ولتجنب مشاكل الاتصال:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d app.wakeel.cc
```
اتبع التعليمات على الشاشة لإتمام تفعيل الـ SSL (سيسألك عما إذا كنت تريد تحويل كل الزيارات إلى HTTPS تلقائياً، اختر نعم Redirect).

---

## 8. تشغيل المهام الخلفية (Queue Worker)

يعتمد التطبيق على تنفيذ مهام في الخلفية مثل التكامل مع الـ ERP. لضمان تشغيل هذه المهام تلقائياً، يُفضل إعداد **Supervisor**.

قم بتثبيت Supervisor:
```bash
sudo apt install supervisor
```

قم بإنشاء ملف إعداد جديد للمشروع:
```bash
sudo nano /etc/supervisor/conf.d/wakeel-worker.conf
```

أضف الإعدادات التالية (تأكد من تعديل المسار واسم المستخدم):

```ini
[program:wakeel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app.wakeel.cc/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=wakeel-app
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/app.wakeel.cc/storage/logs/worker.log
stopwaitsecs=3600
```

قم بتحديث وتفعيل الإعدادات الجديدة:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wakeel-worker:*
```

---

## 9. إعداد الجدولة الزمنية (Cron Job Scheduler)

يقوم نظام Laravel بتشغيل بعض المهام المجدولة تلقائياً كل دقيقة. لتفعيل ذلك:

افتح قائمة الـ Cron للمستخدم الحالي:
```bash
crontab -e
```

أضف السطر التالي في نهاية الملف (تأكد من تعديل المسار لمشروعك):
```cron
* * * * * cd /var/www/app.wakeel.cc && php artisan schedule:run >> /dev/null 2>&1
```

احفظ الملف واخرج، وسيتولى السيرفر تشغيل المهام المجدولة دورياً كل دقيقة.
