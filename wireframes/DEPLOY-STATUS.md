# وضعیت استقرار امن روی hdd-land.ir

تاریخ: 2026-08-13 (به‌روز)

## محافظت از سیستم قبض
- پوشه `public_html/tmr` لمس نشد
- دیتابیس `*_Ghabz` لمس نشد
- `support.hdd-land.ir` پس از استقرار پاسخ 200 می‌دهد

## انجام‌شده
1. پیش‌نمایش طرح: `https://hdd-land.ir/corporate-preview/home.html`
2. صفحات شرکتی: `/services` `/warranty` `/training` `/blog` `/services/about-recovery`
3. صفحه اصلی زنده با لایه‌ی `layouts.hdd-land`
4. منوی کامل: خانه · خدمات · درباره ما · آموزش · بلاگ آموزشی · فروشگاه · گارانتی · تماس
5. **ادمین — تنظیمات بنر / صفحه اول / فوتر:**
   - مسیر: `/admin/corporate-home`
   - منو: تنظیمات قالب → «بنر / صفحه اول / فوتر شرکتی»
   - تب‌ها: بنر و هدر · صفحه اول · فوتر
   - ذخیره در `settings.corporate_home_settings`

## ممنوع در آپدیت‌های بعدی
- پاک/Overwrite کردن کل `public_html`
- دست زدن به `public_html/tmr`
- تغییر Document Root ساب‌دامین support
