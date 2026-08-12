# وضعیت استقرار امن روی hdd-land.ir

تاریخ: 2026-08-12

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

## ممنوع در آپدیت‌های بعدی
- پاک/Overwrite کردن کل `public_html`
- دست زدن به `public_html/tmr`
- تغییر Document Root ساب‌دامین support
