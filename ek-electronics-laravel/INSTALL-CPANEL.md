# EK Electronics Laravel — cPanel install

English storefront + Filament staff panel (orders, invoices, recovery jobs, WhatsApp desk).

## Host layout used on Afrihost

- App root: `/home/ekeledlx/ek-laravel`
- Web root (`public_html`) contains Laravel `public/` files with `index.php` pointing at `../ek-laravel`

## Requirements

- PHP 8.2+ (`ea-php84` on this account)
- MySQL database + user (this project uses `ekeledlx_site` / `ekeledlx_siteuser`)
- Writable `storage/` and `bootstrap/cache/`

## Install

1. Upload and extract the package under `/home/ekeledlx/ek-laravel`
2. Point `public_html` at the app public files (deploy script does this)
3. Open `https://ekelectronics.co.za/install`
4. Enter DB credentials → receive admin email/password → `/admin`

## Staff panel

- Catalogue (categories / products)
- Orders with WhatsApp actions
- Invoices / VAT amounts
- Recovery jobs
- WhatsApp message log
- Site settings (phone, WhatsApp number, address)
