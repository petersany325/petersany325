# حساب HDD — نرم‌افزار نصبی ویندوز

نرم‌افزار **مجزا** تحت ویندوز (نه داخل مرورگر): نصب می‌شود، به SQL Server محلی وصل می‌شود، آفلاین کار می‌کند و با اینترنت با سایت همگام می‌شود.

## دانلود فایل نصب (پیشنهادی)

بعد از هر push روی شاخه، GitHub Actions فایل نصب را می‌سازد:

1. بروید به:  
   https://github.com/petersany325/petersany325/actions/workflows/hesab-windows-installer.yml  
2. آخرین اجرای سبز را باز کنید.  
3. از بخش **Artifacts** دانلود کنید: `Hesab-HDD-Windows-1.0.0`  
4. داخلش دو فایل است:
   - **`Hesab-HDD-Setup-1.0.0.exe`** ← نصب‌کننده (همین را اجرا کنید)
   - `Hesab-HDD-Portable-1.0.0-win-x64.zip` ← نسخه قابل‌حمل بدون نصب

### نصب روی ویندوز شما

1. SQL Server 2022 باید نصب باشد (انجام شده ✓).
2. `Hesab-HDD-Setup-1.0.0.exe` را اجرا کنید → Next → Install.
3. بعد از نصب، **راهنمای نصب دیتابیس** باز می‌شود:
   - سرور: `localhost`
   - Windows Authentication را بگذارید
   - **ساخت دیتابیس** → سپس **ورود به برنامه**
4. میانبر «حساب HDD» در منوی Start ساخته می‌شود.

نیازی به نصب .NET جداگانه نیست (برنامه self-contained است).

## ساخت نصب‌کننده روی همان ویندوز (اختیاری)

اگر Visual Studio / .NET 8 SDK دارید:

```powershell
cd hesab\winclient
powershell -ExecutionPolicy Bypass -File .\build-installer.ps1
```

خروجی در `hesab\winclient\artifacts\`:
- `Hesab-HDD-Setup-1.0.0.exe`
- `Hesab-HDD-Portable-1.0.0-win-x64.zip`

برای Setup.exe باید [Inno Setup 6](https://jrsoftware.org/isdl.php) نصب باشد؛ در غیر این صورت فقط zip قابل‌حمل ساخته می‌شود.

## قابلیت‌ها

| بخش | وضعیت |
|-----|--------|
| نصب‌گر Setup.exe + میانبر Start/Desktop | ✓ |
| جادوگر ساخت دیتابیس Hesab در اولین اجرا | ✓ |
| کار آفلاین روی SQL Server محلی | ✓ |
| طرف‌حساب محلی | ✓ |
| همگام‌سازی با سایت (`/api/sync`) | اسکلت + طرف‌حساب |

## اتصال پیش‌فرض

```
Server=localhost;Database=Hesab;Trusted_Connection=True;TrustServerCertificate=True;
```

از منوی **پرونده → تنظیمات اتصال / نصب دیتابیس** قابل تغییر است.
