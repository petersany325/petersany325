# حساب HDD — کلاینت ویندوز (SQL Server)

نسخه آفلاین دسکتاپ که به SQL Server محلی وصل می‌شود و وقتی اینترنت باشد با سایت همگام می‌شود.

## پیش‌نیاز روی ویندوز شما

1. SQL Server 2022 (نصب شده ✓)
2. SSMS (نصب شده ✓)
3. [.NET 8 SDK](https://dotnet.microsoft.com/download/dotnet/8.0) یا Visual Studio 2022 با workload **.NET Desktop Development**

## مرحله ۱ — ساخت دیتابیس در SSMS

1. SSMS را باز کنید و به `localhost` وصل شوید (همان اتصال موفق قبلی).
2. **File → Open → File** و این فایل را انتخاب کنید:

   `hesab/sql/sqlserver_hesab.sql`

   (اگر ریپو را کلون کرده‌اید؛ یا فایل را از GitHub دانلود کنید.)
3. کلید **F5** (Execute).
4. در Object Explorer → Databases باید **Hesab** دیده شود.
5. جداول نمونه: `parties`, `accounts_moein`, `vouchers`, `sync_state`.

## مرحله ۲ — اجرای کلاینت

در PowerShell یا Developer Command Prompt:

```powershell
cd path\to\repo\hesab\winclient
dotnet restore
dotnet run --project HesabWin
```

یا فایل `HesabWin.sln` را در Visual Studio باز کنید و **F5**.

اتصال پیش‌فرض (`appsettings.json`):

```
Server=localhost;Database=Hesab;Trusted_Connection=True;TrustServerCertificate=True;
```

اگر با کاربر `sa` وصل می‌شوید، ConnectionString را عوض کنید:

```
Server=localhost;Database=Hesab;User Id=sa;Password=YOUR_PASSWORD;TrustServerCertificate=True;
```

## قابلیت‌های فعلی (اسکلت)

| بخش | وضعیت |
|-----|--------|
| اتصال به SQL Server محلی | ✓ |
| کدینگ پایه (seed در اسکریپت SQL) | ✓ |
| CRUD طرف‌حساب محلی | ✓ |
| کار آفلاین روی DB محلی | ✓ |
| دکمه همگام‌سازی + صف push/pull | اسکلت (API سایت در مرحله بعد) |
| نصب‌گر NSIS / MSI | مرحله بعد |

## معماری همگام‌سازی

- هر رکورد `sync_id` (GUID)، `updated_at`، `sync_version`، `is_deleted` دارد.
- سیاست تعارض پیش‌فرض: **Last Write Wins** روی `sync_version`.
- جداول `sync_state` و `sync_log` وضعیت دستگاه را نگه می‌دارند.
- وقتی API وب (`/api/sync`) آماده شود، همان دکمه «همگام‌سازی با سایت» داده را دوطرفه رد و بدل می‌کند.

## مرحله بعد

1. API همگام‌سازی روی `hdd-land.ir/hesab`
2. صفحات سند و فاکتور در کلاینت ویندوز
3. بسته‌بندی نصب (`dotnet publish` + installer)
