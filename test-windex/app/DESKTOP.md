# Windex WD — Windows Desktop

این برنامه باید مثل نرم‌افزار ویندوز باز شود، نه تب مرورگر.

## روش ۱ — سریع (دانلود تست)

1. زیپ را باز کنید  
2. `START-WINDEX-WD.bat` را اجرا کنید  
3. با **Edge/Chrome App Mode** به‌صورت پنجرهٔ نرم‌افزار باز می‌شود  
4. **License Settings** را از منوی File باز کنید (یا در گیت: Demo unlock)

مسیر پروژه پیش‌فرض: `D:\test windex`

## روش ۲ — فایل EXE واقعی (Electron)

روی همان PC ویندوز که Node.js دارد:

```bat
cd wd-factory-desk
BUILD-WINDOWS.bat
```

خروجی:

```
dist-win\Windex WD-win32-x64\Windex WD.exe
```

در بیلد Electron:
- منوی ویندوز: **File → License Settings…** (Ctrl+L)
- لایسنس در `license.dat` داخل userData ویندوز ذخیره می‌شود
- تنظیم مسیر Pack / Backup از تب Project Paths

## License Settings

| کار | کجا |
|-----|-----|
| فعال‌سازی سریال | License Settings → License |
| Demo 14 روز | دکمه Demo unlock |
| غیرفعال‌سازی | Deactivate |
| مسیر پروژه / FW / Backup | تب Project Paths |

## Dev

```bat
npm install
npm start
```
