آنلاک فوری support.hddsoftware.ir
=================================

مشکل الان روی سایت مشتری:
1) صفحه اصلی قفل لایسنس می‌دهد با پیام:
   The route license/verify could not be found.
2) لینک /install.php صفحه 404 می‌دهد (عکسی که فرستادید).

علت:
- سرور فروشنده (معمولاً support.hdd-land.ir) مسیر /license/verify را Deploy نکرده.
- نسخه فعلی مشتری هر خطای سرور را قفل سخت می‌کند.
- فایل install.php بعد از نصب حذف شده؛ برای همین 404 می‌بینید.

-------------------------
روش ۱ — سریع‌ترین (۱ فایل)
-------------------------
1) فایل _license_unlock.php را از این ZIP بردارید.
2) در File Manager هاست، داخل Document Root سایت
   (همان پوشه‌ای که index.php لاراول/public است) آپلود کنید.
3) در مرورگر یک‌بار باز کنید:
   https://support.hddsoftware.ir/_license_unlock.php
4) باید در خروجی ببینید: DONE_UNLOCK_OK
5) همان فایل را حذف کنید.
6) https://support.hddsoftware.ir را Hard Refresh کنید.

-------------------------
روش ۲ — کپی دستی فایل‌ها
-------------------------
محتویات پوشه paths/ را روی ریشه پروژه Laravel مشتری Merge کنید:
- app/Http/Middleware/EnsureLicensed.php
- resources/views/errors/license.blade.php
- public/install.php
بعد:
  php artisan optimize:clear

-------------------------
کار فروشنده (بعدی)
-------------------------
روی support.hdd-land.ir مسیرهای لایسنس را Deploy کنید
(/license/verify و آپدیت‌ها) تا چک واقعی لایسنس کار کند.
تا آن موقع مشتری با soft-fail باز می‌ماند و دیگر به‌خاطر 404 سرور قفل نمی‌شود.
