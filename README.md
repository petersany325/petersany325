# سایت وکالت آریان

## پکیج‌ها
- `sample-site/` — طرح HTML اولیه
- `php-host/` — نسخه PHP ساده بدون دیتابیس
- `law-laravel/` — **لاراول ۱۳** با نصب‌کننده وب + پنل ادمین
- `law-laravel-cpanel-install.zip` — فایل نصبی آماده هاست (همراه vendor)

## نصب روی هاست
راهنما: [`law-laravel/INSTALL-CPANEL.md`](law-laravel/INSTALL-CPANEL.md)

1. ZIP را آپلود و Extract کنید
2. Document Root را روی `public` بگذارید (یا طبق راهنما به `public_html` وصل کنید)
3. PHP **8.3** با mbstring/fileinfo/pdo_mysql
4. بروید به `/install` و فقط مشخصات دیتابیس را بزنید
5. ایمیل و رمز ادمین نمایش داده می‌شود → `/admin`
