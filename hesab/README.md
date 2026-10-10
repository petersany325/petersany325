# سامانه حسابداری تحت وب — hesab.hdd-land.ir

وب‌اپ حسابداری به سبک نرم‌افزار (RTL) با هسته سند دوطرفه.

## ماژول‌های MVP
- کدینگ حساب (seed از فایل اکسل پیشنهادی)
- اسناد حسابداری (پیش‌نویس / قطعی)
- دفتر حساب و تراز آزمایشی
- طرف‌حساب و فاکتور فروش (+ سند خودکار)

## استقرار روی cPanel
1. ساب‌دامین `hesab.hdd-land.ir` با Document Root: `/home/USER/hesab`
2. دیتابیس MySQL بسازید و کاربر را با ALL PRIVILEGES وصل کنید
3. محتوای این پوشه را داخل Document Root آپلود کنید
4. باز کنید: `https://hdd-land.ir/hesab/install`

## ورود پیش‌فرض بعد از نصب
- ایمیل: مقدار واردشده در نصب (پیشنهادی: `admin@hesab.local`)
- رمز: مقدار واردشده در نصب

فایل `config.sample.php` نمونه تنظیمات است؛ بعد از نصب `config.php` ساخته می‌شود.

## کلاینت ویندوز (آفلاین + SQL Server)

1. اسکریپت SSMS: `sql/sqlserver_hesab.sql`
2. پروژه .NET 8: `winclient/` — راهنما در `winclient/README.md`
3. API همگام‌سازی وب: `GET/POST /api/sync` (طرف‌حساب کامل؛ بقیه در صف)
