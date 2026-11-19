#!/bin/sh
# Warm the public design pages after a deploy.
# When PHP is available on the server, also refresh Laravel's caches:
#   php artisan config:cache
#   php artisan route:cache
#   php artisan view:cache
# OPcache should stay enabled in the cPanel PHP settings.
set -eu
base="${1:-https://hdd-land.ir}"
for path in /design /design/corporate /design/shop /design/laravel /design/ui-ux /design/seo /design/support; do
  curl -fsS -o /dev/null -H 'Accept-Encoding: identity' "$base$path" || true
done
