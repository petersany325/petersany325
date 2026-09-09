# سرزمین هارد — مدیریت تعمیرکاران

سیستم حسابداری و مدیریت تعمیرگاه با Laravel برای پذیرش قبض، مشتریان، قطعات، تعمیرکاران و گزارش‌ها.

## امکانات

- قبض پذیرش ورودی/خروجی مشتری
- ثبت سریع مشتری (نام، تلفن، آدرس، شغل، نحوه آشنایی، کد ملی)
- ثبت کالا، مدل، سریال، نوع خرابی، بیعانه
- ثبت قطعات مصرفی با تاریخ و کسر انبار
- مدیریت تعمیرکاران و کاربران سیستم
- گزارش حسابداری، عملکرد تعمیرکاران، کاربران، کالای خرج‌شده
- چاپ قبض

## ورود دمو

- ایمیل: `admin@saramin-hard.ir`
- رمز: `password`

## اجرای محلی

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## نصب روی cPanel

1. در cPanel یک ساب‌دامین یا دامنه بسازید و Document Root را روی پوشه `public` پروژه بگذارید.
2. دیتابیس MySQL بسازید.
3. فایل‌های پروژه را آپلود کنید (بدون `node_modules`).
4. در `.env` تنظیم کنید:

```env
APP_NAME="سرزمین هارد"
APP_URL=https://your-domain.com
APP_LOCALE=fa
APP_FALLBACK_LOCALE=fa
APP_TIMEZONE=Asia/Tehran

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

5. از Terminal هاست یا SSH:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
```

6. دسترسی پوشه‌های `storage` و `bootstrap/cache` را قابل نوشتن کنید (`775`).

اگر Composer روی هاست نیست، `vendor` را از محیط لوکال با PHP 8.3 بسازید و آپلود کنید.
