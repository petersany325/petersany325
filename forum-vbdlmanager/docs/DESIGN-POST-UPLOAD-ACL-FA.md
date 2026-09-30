# طراحی منوی تخصصی آپلود/دانلود + مدیریت دسترسی ادمین

## هدف
در ویرایشگر پست یک منوی تخصصی آپلود/دانلود داشته باشیم که کاربر **فقط طبق دسترسی خودش** ببیند/آپلود/دانلود کند.

مثال الزامی:
- کاربر عضو گروه **VIP SeDiv** است
- دسته **VIP** وجود دارد
- این کاربر **نباید** از دسته VIP دانلود کند مگر اینکه ادمین در پنل **Access Grants** به او (یا گروه مشخص) دسترسی بدهد

## مدل دسترسی (ACL)

### حالت دسته (`access_mode`)
| حالت | معنی |
|------|------|
| `free_open` | ماتریس گروه + قوانین قدیمی؛ مناسب فایل‌های عمومی/رایگان |
| `grant_required` | **حتی VIP بودن کافی نیست**؛ فقط Grant صریح ادمین |

### قوانین Paid/VIP فایل
- اگر `acl_paid_needs_grant=1` یا `acl_vip_needs_grant=1` باشد، فایل Paid هم بدون Grant دانلود نمی‌شود.
- عضویت در `vip_usergroupids` به‌تنهایی قفل VIP را باز نمی‌کند.

### جدول Grant
`vbdl_access_grant`
- هدف: `category` یا `file`
- گیرنده: `user` یا `usergroup`
- پرچم‌ها: `can_view`, `can_download`, `can_upload`
- ادمین می‌تواند فقط Download بدهد بدون Upload، یا برعکس

### ترتیب تصمیم دانلود
1. Admin bypass
2. اگر دسته/فایل grant_required یا paid-needs-grant → فقط Grant روی فایل یا دسته
3. در غیر این صورت ماتریس گروه (legacy)

## منوی تخصصی در پست
- روی صفحات create/edit content تزریق می‌شود (`vbdlportal` hook + `post-upload.js/css`)
- لیست دسته‌ها فقط آن‌هایی که کاربر `can_upload` دارد
- آپلود از `vbdlmanager/upload_api.php`
- خروجی: BBCode `[download=ID]title[/download]` داخل متن پست
- دانلودکننده نهایی همان ACL را رعایت می‌کند

## پنل ادمین
1. **Categories → Access mode = grant_required** برای VIP
2. **Access Grants**
   - کاربر با username یا usergroup
   - هدف category/file
   - View / Download / Upload
3. **VIP Users** همچنان برای نشان VIP/عضویت گروهی است، ولی **جایگزین Grant نیست**

## سناریوی VIP SeDiv
1. دسته `VIP` را `grant_required` کنید
2. کاربر VIP SeDiv را در گروه VIP نگه دارید (برای نشان/سایر امکانات)
3. تا وقتی Grant نساخته‌اید: دانلود/آپلود VIP برایش بسته است
4. برای دسترسی: Access Grants → همان کاربر یا گروه خاص → Category VIP → Download (و در صورت نیاز Upload)

## فایل‌های کلیدی این بسته
- `db/mysql/upgrade_grants_2026.sql`
- `library/Acl.php` (منطق Grant)
- `library/Repository.php` (CRUD Grant)
- `admincp/.../pages/grants.php`
- `admincp/.../pages/categories.php` (access_mode)
- `vbdlmanager/upload_api.php`
- `vbdlmanager/assets/post-upload.js|css`
- `packages/vbdlportal/hooks.php` (تزریق منو)

## استقرار
1. SQL آپگرید را روی دیتابیس فروم اجرا کنید
2. فایل‌های `live/` را روی درخت فروم کپی کنید
3. دسته VIP را `grant_required` کنید
4. برای کاربران مجاز Grant بدهید
5. یک پست تست با منوی آپلود و یک دانلود با/بدون Grant

> توجه: از این محیط Cloud به هاست فروم (`forum.hdd-land.com`) دسترسی دیپلوی مستقیم نبود؛ بسته برای نصب روی سرور فروم آماده است.
