# وضعیت استقرار امن روی hdd-land.ir

تاریخ: 2026-08-12 (به‌روز)

## محافظت از سیستم قبض
- پوشه `public_html/tmr` لمس نشد
- دیتابیس `*_Ghabz` لمس نشد
- `support.hdd-land.ir` پس از استقرار پاسخ 200 می‌دهد

## انجام‌شده
1. پیش‌نمایش طرح: `https://hdd-land.ir/corporate-preview/home.html`
2. صفحات شرکتی افزودنی در فروشگاه Laravel:
   - `/services`
   - `/warranty`
   - `/training`
   - `/blog`
   - `/services/about-recovery`
3. **صفحه اصلی زنده** (`/`) با لایه‌ی `layouts.hdd-land` و طرح نمونه (مگامنو / آبشاری / بنر دو‌مسیره) جایگزین شد
4. منوی کامل: خانه · خدمات · درباره ما · آموزش · بلاگ آموزشی · فروشگاه · گارانتی · تماس
5. فایل‌های استاتیک: `public/css/hdd-corporate.css` و `public/js/hdd-corporate-nav.js`
6. بکاپ خانه قبلی: `resources/views/storefront/home.blade.php.bak-*`

## ممنوع در آپدیت‌های بعدی
- پاک/Overwrite کردن کل `public_html`
- دست زدن به `public_html/tmr`
- تغییر Document Root ساب‌دامین support
