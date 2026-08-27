# ریپوی پروژه‌های جدا — بدون Windex / WinFOF / SASDEX

این ریپو **شامل نرم‌افزار Windex، WinFOF، SASDEX یا ابزار فکتوری هارد نیست.**

## پروژه‌های داخل ریپو

### ۱) سایت مؤسسه حقوقی آریان (`aryan-law-*`)
- `aryan-law-html/` — طرح HTML اولیه
- `aryan-law-php/` — نسخه PHP ساده بدون دیتابیس
- `aryan-law-laravel/` — لاراول ۱۳ با نصب‌کننده وب + پنل ادمین
- `aryan-law-php-host.zip` — پکیج PHP برای هاست
- `aryan-law-laravel-cpanel-install.zip` — پکیج لاراول برای cPanel (همراه vendor)

نصب لاراول روی هاست: [`aryan-law-laravel/INSTALL-CPANEL.md`](aryan-law-laravel/INSTALL-CPANEL.md)

1. ZIP لاراول را آپلود و Extract کنید  
2. Document Root را روی `public` بگذارید  
3. PHP **8.3** با mbstring/fileinfo/pdo_mysql  
4. بروید به `/install` و مشخصات دیتابیس را بزنید  
5. ایمیل و رمز ادمین نمایش داده می‌شود → `/admin`

### ۲) ربات تلگرام همگپ (`hamgap-bot*`)
- `hamgap-bot/` — کد ربات
- `hamgap-bot-design/` — دارایی‌های طراحی

## نام‌هایی که عمداً اینجا نیستند
- **Windex / Windex WD** — حذف شده از این ریپو  
- **WinFOF** — اینجا نیست  
- **SASDEX** — اینجا نیست  
