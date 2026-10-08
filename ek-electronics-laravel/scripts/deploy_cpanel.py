#!/usr/bin/env python3
"""Build zip, upload to Afrihost cPanel, extract, wire public_html, run web installer."""

from __future__ import annotations

import http.cookiejar
import json
import os
import ssl
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get("CPANEL_HOST", "https://ekelectronics.co.za:2083").rstrip("/")
USER = os.environ["CPANEL_USER"]
PASS = os.environ["CPANEL_PASSWORD"]
REMOTE_APP = os.environ.get("REMOTE_APP", "/home/ekeledlx/ek-laravel")
REMOTE_PUBLIC = os.environ.get("REMOTE_PUBLIC", "/home/ekeledlx/public_html")
SITE = os.environ.get("SITE_URL", "https://ekelectronics.co.za").rstrip("/")
DB_HOST = os.environ.get("DB_HOST", "localhost")
DB_PORT = os.environ.get("DB_PORT", "3306")
DB_NAME = os.environ["DB_DATABASE"]
DB_USER = os.environ["DB_USERNAME"]
DB_PASS = os.environ["DB_PASSWORD"]

CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE

EXCLUDE_DIRS = {".git", "node_modules", "tests", ".cursor", "storage/logs"}
EXCLUDE_FILES = {"database/database.sqlite", ".env", "scripts/deploy_cpanel.py"}


def login():
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(
        urllib.request.HTTPSHandler(context=CTX),
        urllib.request.HTTPCookieProcessor(cj),
    )
    data = urllib.parse.urlencode({"user": USER, "pass": PASS}).encode()
    req = urllib.request.Request(BASE + "/login/?login_only=1", data=data, method="POST")
    req.add_header("Content-Type", "application/x-www-form-urlencoded")
    with opener.open(req, timeout=60) as r:
        body = json.loads(r.read().decode())
    if body.get("status") != 1 or not body.get("security_token"):
        raise RuntimeError(f"login failed: {body}")
    return opener, body["security_token"]


def api2(opener, token: str, module: str, func: str, **kwargs):
    q = {
        "cpanel_jsonapi_user": USER,
        "cpanel_jsonapi_apiversion": "2",
        "cpanel_jsonapi_module": module,
        "cpanel_jsonapi_func": func,
    }
    q.update(kwargs)
    url = BASE + token + "/json-api/cpanel?" + urllib.parse.urlencode(q)
    with opener.open(url, timeout=300) as r:
        return json.loads(r.read().decode())


def uapi(opener, token: str, path: str, data: bytes | None = None, headers: dict | None = None):
    req = urllib.request.Request(BASE + token + path, data=data, method="POST" if data else "GET")
    for k, v in (headers or {}).items():
        req.add_header(k, v)
    with opener.open(req, timeout=600) as r:
        return json.loads(r.read().decode())


def build_zip(out: Path) -> Path:
    if out.exists():
        out.unlink()
    with zipfile.ZipFile(out, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for path in ROOT.rglob("*"):
            if not path.is_file():
                continue
            rel = path.relative_to(ROOT).as_posix()
            if any(part in EXCLUDE_DIRS for part in Path(rel).parts):
                continue
            if rel in EXCLUDE_FILES or rel.endswith(".sqlite"):
                continue
            if rel.startswith("storage/framework/") and path.name != ".gitignore":
                # keep structure via gitignore files only; recreate empty dirs later
                if path.suffix == "" and path.name.startswith("."):
                    pass
            zf.write(path, f"ek-laravel/{rel}")
        # ensure writable storage skeleton
        for d in [
            "storage/app/public",
            "storage/framework/cache/data",
            "storage/framework/sessions",
            "storage/framework/views",
            "storage/logs",
            "bootstrap/cache",
        ]:
            zf.writestr(f"ek-laravel/{d}/.gitkeep", "")
    print("zip", out, "size_mb", round(out.stat().st_size / 1024 / 1024, 1))
    return out


def upload(opener, token: str, local: Path, dest_dir: str):
    boundary = f"----Bound{int(time.time() * 1000)}"
    raw = local.read_bytes()
    body = b""
    for k, v in {"dir": dest_dir, "overwrite": "1"}.items():
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    body += (
        f'--{boundary}\r\nContent-Disposition: form-data; name="file-1"; filename="{local.name}"\r\n'
        f"Content-Type: application/zip\r\n\r\n"
    ).encode() + raw + b"\r\n"
    body += f"--{boundary}--\r\n".encode()
    return uapi(
        opener,
        token,
        "/execute/Fileman/upload_files",
        data=body,
        headers={"Content-Type": f"multipart/form-data; boundary={boundary}"},
    )


def save_file(opener, token: str, directory: str, filename: str, content: str):
    return api2(
        opener,
        token,
        "Fileman",
        "savefile",
        dir=directory,
        filename=filename,
        content=content,
        charset="utf-8",
    )


def main() -> int:
    zip_path = Path("/tmp/ek-laravel-cpanel.zip")
    build_zip(zip_path)

    opener, token = login()
    print("logged in", token)

    # Ensure app dir exists
    api2(opener, token, "Fileman", "mkdir", path="/home/ekeledlx", name="ek-laravel", permissions="0755")

    print("uploading…")
    for attempt in range(1, 5):
        try:
            opener, token = login()
            res = upload(opener, token, zip_path, "/home/ekeledlx")
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

    print("extracting…")
    opener, token = login()
    extracted = api2(
        opener,
        token,
        "Fileman",
        "fileop",
        op="extract",
        sourcefiles="ek-laravel-cpanel.zip",
        destfiles="/home/ekeledlx",
        dir="/home/ekeledlx",
    )
    print("extract", json.dumps(extracted)[:500])

    # Wire public_html to Laravel public
    index_php = f"""<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../ek-laravel/storage/framework/maintenance.php')) {{
    require $maintenance;
}}

require __DIR__.'/../ek-laravel/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../ek-laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
"""
    htaccess = Path(ROOT / "public" / ".htaccess").read_text()

    opener, token = login()
    print("save index", save_file(opener, token, REMOTE_PUBLIC, "index.php", index_php))
    print("save htaccess", save_file(opener, token, REMOTE_PUBLIC, ".htaccess", htaccess))

    # Copy public assets into public_html (css/js/img/filament)
    # Upload a small assets zip extracted into public_html
    assets_zip = Path("/tmp/ek-public-assets.zip")
    with zipfile.ZipFile(assets_zip, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        public = ROOT / "public"
        for path in public.rglob("*"):
            if path.is_file() and path.name not in {"index.php", ".htaccess"}:
                zf.write(path, path.relative_to(public).as_posix())
    print("assets zip mb", round(assets_zip.stat().st_size / 1024 / 1024, 1))
    opener, token = login()
    print("upload assets", upload(opener, token, assets_zip, REMOTE_PUBLIC))
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

    # Permissions
    for path in [
        f"{REMOTE_APP}/storage",
        f"{REMOTE_APP}/bootstrap/cache",
    ]:
        try:
            opener, token = login()
            print(
                "chmod",
                path,
                api2(opener, token, "Fileman", "fileop", op="chmod", metadata="0755", sourcefiles=path, dir="/home/ekeledlx"),
            )
        except Exception as e:
            print("chmod skip", path, e)

    # Hit installer
    print("running web installer…")
    cj = http.cookiejar.CookieJar()
    http_opener = urllib.request.build_opener(
        urllib.request.HTTPSHandler(context=CTX),
        urllib.request.HTTPCookieProcessor(cj),
    )
    # GET install for CSRF cookie/session
    with http_opener.open(SITE + "/install", timeout=120) as r:
        html = r.read().decode("utf-8", "ignore")
    print("install page status ok, len", len(html))
    # Extract CSRF
    import re

    m = re.search(r'name="_token" value="([^"]+)"', html)
    if not m:
        print("No CSRF token — site may already be installed or blocked")
        print(html[:800])
        return 2
    token_csrf = m.group(1)
    data = urllib.parse.urlencode(
        {
            "_token": token_csrf,
            "db_host": DB_HOST,
            "db_port": DB_PORT,
            "db_database": DB_NAME,
            "db_username": DB_USER,
            "db_password": DB_PASS,
        }
    ).encode()
    req = urllib.request.Request(SITE + "/install", data=data, method="POST")
    req.add_header("Content-Type", "application/x-www-form-urlencoded")
    req.add_header("Referer", SITE + "/install")
    with http_opener.open(req, timeout=300) as r:
        done = r.read().decode("utf-8", "ignore")
        print("install response len", len(done), "url", r.geturl())
    # Capture credentials if present
    email = re.search(r"Admin email:</strong>\s*([^<]+)", done)
    password = re.search(r"Admin password:</strong>\s*([^<]+)", done)
    if email and password:
        Path("/tmp/ek-admin-credentials.txt").write_text(
            f"admin_url={SITE}/admin\nemail={email.group(1).strip()}\npassword={password.group(1).strip()}\n"
        )
        print("ADMIN_EMAIL", email.group(1).strip())
        print("ADMIN_PASSWORD", password.group(1).strip())
    else:
        print(done[:1500])
        if "Installation complete" not in done and "already" not in done.lower():
            return 3

    # Verify homepage
    with urllib.request.urlopen(SITE + "/", context=CTX, timeout=60) as r:
        home = r.read().decode("utf-8", "ignore")
        print("home status", r.status, "has EK", "EK Electronics" in home)
    return 0 if "EK Electronics" in home else 4


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except urllib.error.HTTPError as e:
        print("HTTPError", e.code, e.read()[:500])
        raise
