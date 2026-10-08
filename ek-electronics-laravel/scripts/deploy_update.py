#!/usr/bin/env python3
"""Upload code + assets to Afrihost without reinstalling; run migrate/seed once."""

from __future__ import annotations

import json
import os
import ssl
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))
from deploy_cpanel import (  # noqa: E402
    BASE,
    CTX,
    REMOTE_APP,
    REMOTE_PUBLIC,
    SITE,
    USER,
    api2,
    build_zip,
    login,
    save_file,
    upload,
)

MIGRATE_PHP = r"""<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';
if (!hash_equals(getenv('EK_MIGRATE_KEY') ?: 'ek-migrate-2026', $key)) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}
require __DIR__ . '/../ek-laravel/vendor/autoload.php';
$app = require __DIR__ . '/../ek-laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "migrate:\n" . Illuminate\Support\Facades\Artisan::output();
    Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\EkStoreSeeder',
        '--force' => true,
    ]);
    echo "seed:\n" . Illuminate\Support\Facades\Artisan::output();
    // Promote existing admin users
    Illuminate\Support\Facades\DB::table('users')
        ->where('is_admin', 1)
        ->update(['role' => 'admin', 'is_active' => 1]);
    echo "admin roles updated\n";
    echo "OK\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERR: " . $e->getMessage() . "\n";
}
@unlink(__FILE__);
"""


def main() -> int:
    zip_path = Path("/tmp/ek-laravel-cpanel.zip")
    build_zip(zip_path)

    opener, token = login()
    print("logged in")

    home = os.environ.get("REMOTE_HOME") or str(Path(REMOTE_APP).parent)
    for attempt in range(1, 5):
        try:
            opener, token = login()
            res = upload(opener, token, zip_path, home)
            print("upload", res.get("status"), res.get("errors"))
            if res.get("status") == 1:
                break
        except Exception as e:
            print("upload attempt", attempt, type(e).__name__, e)
            if attempt == 4:
                return 1
            time.sleep(3 * attempt)
    else:
        return 1

    opener, token = login()
    extracted = api2(
        opener,
        token,
        "Fileman",
        "fileop",
        op="extract",
        sourcefiles="ek-laravel-cpanel.zip",
        destfiles=home,
        dir=home,
    )
    print("extract", json.dumps(extracted)[:400])

    # public assets
    assets_zip = Path("/tmp/ek-public-assets.zip")
    import zipfile

    with zipfile.ZipFile(assets_zip, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        public = ROOT / "public"
        for path in public.rglob("*"):
            if path.is_file() and path.name not in {"index.php", ".htaccess"}:
                zf.write(path, path.relative_to(public).as_posix())
    opener, token = login()
    print("upload assets", upload(opener, token, assets_zip, REMOTE_PUBLIC).get("status"))
    opener, token = login()
    print(
        "extract assets",
        api2(
            opener,
            token,
            "Fileman",
            "fileop",
            op="extract",
            sourcefiles="ek-public-assets.zip",
            destfiles=REMOTE_PUBLIC,
            dir=REMOTE_PUBLIC,
        ),
    )

    opener, token = login()
    print("save migrate", save_file(opener, token, REMOTE_PUBLIC, "_migrate_once.php", MIGRATE_PHP))

    url = SITE + "/_migrate_once.php?key=ek-migrate-2026"
    print("hitting", url)
    req = urllib.request.Request(url)
    with urllib.request.urlopen(req, context=CTX, timeout=300) as r:
        body = r.read().decode("utf-8", "ignore")
        print(body[-2000:])
        if "OK" not in body:
            return 2

    with urllib.request.urlopen(SITE + "/login", context=CTX, timeout=60) as r:
        html = r.read().decode("utf-8", "ignore")
        print("login page", r.status, "Customer" in html, "Staff" in html)
    with urllib.request.urlopen(SITE + "/admin/menus", context=CTX, timeout=60) as r:
        print("admin menus status", r.status, "len", len(r.read()))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except urllib.error.HTTPError as e:
        print("HTTPError", e.code, e.read()[:800])
        raise
