# Windex WD — آنلاین + اجرا

## آنلاین (تست UI)

**کار می‌کند (بعد از push):**

https://htmlpreview.github.io/?https://raw.githubusercontent.com/petersany325/petersany325/cursor/wd-factory-desk-ui-proto-a3a0/test-windex/WindexWD-Prototype-Test/resources/app/index.html

**GitHub Pages** (اگر در تنظیمات repo فعال شود):

https://petersany325.github.io/petersany325/

**کار نمی‌کند برای نمایش UI:**

- jsDelivr / raw.githubusercontent — `Content-Type: text/plain` → مرورگر سورس HTML نشان می‌دهد، نه اپ

Demo unlock → License Settings

## مسیر ویندوز — اگر باز نمی‌شود

```
D:\test windex\WindexWD-Prototype-Test\
```

### روش ۱ — فوری (توصیه)

1. **CHECK.bat** — فایل‌ها را چک کنید  
2. **RUN.bat** — سرور محلی `http://127.0.0.1:8765/` + Edge app mode  
   - **index.html را مستقیم باز نکنید** (`file://` → لایسنس کار نمی‌کند)

### روش ۲ — ساخت EXE

1. **BUILD-EXE.bat** — یک‌بار (باگ قبلی رفع شد)  
2. **WindexWD.exe** یا **RUN.bat**

### دانلود آپدیت

https://github.com/petersany325/petersany325/raw/cursor/wd-factory-desk-ui-proto-a3a0/wd-factory-desk/dist/WindexWD-Prototype-Test.zip

آنزیپ در `D:\test windex\` → پوشه `WindexWD-Prototype-Test`

### علت رایج «باز نمی‌شود»

- پوشه را دوبار کلیک می‌کنید (باید **RUN.bat** بزنید)  
- `index.html` مستقیم → Demo unlock جواب نمی‌دهد  
- `BUILD-EXE.bat` قدیمی `resources\app` را پاک می‌کرد — zip جدید بگیرید  
- `WindexWD.exe` ساخته نشده → **RUN.bat** کافی است
