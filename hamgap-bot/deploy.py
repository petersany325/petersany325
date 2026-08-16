#!/usr/bin/env python3
"""Deploy HamGap bot to cPanel chatrom via FTP + UAPI helpers."""
from __future__ import annotations

import base64
import json
import os
import ssl
import tarfile
import tempfile
import urllib.parse
from ftplib import FTP_TLS, FTP
from pathlib import Path
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parent
PUBLIC = ROOT / "public"
SRC = ROOT / "src"
SQL = ROOT / "sql"

CPANEL_HOST = os.environ.get("CPANEL_HOST", "https://hdd-land.com:2083").rstrip("/")
CPANEL_USER = os.environ.get("CPANEL_USERNAME", "hddrecov")
CPANEL_PASS = os.environ["CPANEL_PASSWORD"]
BOT_TOKEN = os.environ["BOT_TOKEN"]
DB_PASS = os.environ["DB_PASSWORD"]
WEBHOOK_SECRET = os.environ.get("WEBHOOK_SECRET") or base64.urlsafe_b64encode(os.urandom(18)).decode().rstrip("=")
REMOTE_DIR = "/home/hddrecov/public_html/chatrom"
DOMAIN = os.environ.get("DOMAIN_OR_SUBDOMAIN", "https://dev.hdd-land.com").rstrip("/")

CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE


def uapi(module: str, func: str, **params):
    q = urllib.parse.urlencode(params)
    url = f"{CPANEL_HOST}/execute/{module}/{func}" + (f"?{q}" if q else "")
    req = Request(url)
    token = base64.b64encode(f"{CPANEL_USER}:{CPANEL_PASS}".encode()).decode()
    req.add_header("Authorization", f"Basic {token}")
    with urlopen(req, context=CTX, timeout=60) as resp:
        return json.loads(resp.read().decode())


def uapi_post(module: str, func: str, fields: dict):
    url = f"{CPANEL_HOST}/execute/{module}/{func}"
    data = urllib.parse.urlencode(fields).encode()
    req = Request(url, data=data, method="POST")
    token = base64.b64encode(f"{CPANEL_USER}:{CPANEL_PASS}".encode()).decode()
    req.add_header("Authorization", f"Basic {token}")
    req.add_header("Content-Type", "application/x-www-form-urlencoded")
    with urlopen(req, context=CTX, timeout=120) as resp:
        return json.loads(resp.read().decode())


def save_text(remote_dir: str, filename: str, content: str):
    r = uapi_post(
        "Fileman",
        "save_file_content",
        {"dir": remote_dir, "file": filename, "content": content},
    )
    if r.get("status") != 1:
        raise RuntimeError(f"save failed {remote_dir}/{filename}: {r}")


def ensure_dirs():
    # create nested dirs by writing placeholder then deleting is hard; use FTP mkdir
    pass


def ftp_connect():
    # Try FTPS then FTP
    for cls, kw in ((FTP_TLS, {"timeout": 60}), (FTP, {"timeout": 60})):
        try:
            ftp = cls(**kw)
            ftp.connect("hdd-land.com", 21, timeout=60)
            ftp.login(CPANEL_USER, CPANEL_PASS)
            if isinstance(ftp, FTP_TLS):
                try:
                    ftp.prot_p()
                except Exception:
                    pass
            return ftp
        except Exception as e:
            last = e
    raise RuntimeError(f"FTP failed: {last}")


def ftp_makedirs(ftp, path: str):
    parts = path.strip("/").split("/")
    cur = ""
    for p in parts:
        cur += "/" + p
        try:
            ftp.mkd(cur)
        except Exception:
            pass


def ftp_upload_tree(local: Path, remote: str):
    ftp = ftp_connect()
    try:
        ftp_makedirs(ftp, remote)
        for path in local.rglob("*"):
            rel = path.relative_to(local).as_posix()
            rpath = f"{remote.rstrip('/')}/{rel}"
            if path.is_dir():
                ftp_makedirs(ftp, rpath)
                continue
            parent = str(Path(rpath).parent)
            ftp_makedirs(ftp, parent)
            with path.open("rb") as f:
                ftp.storbinary(f"STOR {rpath}", f)
            print("uploaded", rpath)
    finally:
        try:
            ftp.quit()
        except Exception:
            ftp.close()


def build_config() -> str:
    # PHP config array
    secret = WEBHOOK_SECRET
    return f"""<?php
declare(strict_types=1);
return [
    'bot_token' => {json.dumps(BOT_TOKEN)},
    'bot_username' => 'HamGapXBot',
    'bot_name' => 'هم‌گپ',
    'admin_ids' => [],
    'db' => [
        'host' => 'localhost',
        'name' => 'hddrecov_chat',
        'user' => 'hddrecov_rom',
        'pass' => {json.dumps(DB_PASS)},
        'charset' => 'utf8mb4',
    ],
    'assets_path' => __DIR__ . '/assets/banners',
    'webhook_secret' => {json.dumps(secret)},
];
"""


def tg(method: str, **params):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/{method}"
    data = urllib.parse.urlencode(params).encode()
    req = Request(url, data=data, method="POST")
    with urlopen(req, context=CTX, timeout=60) as resp:
        return json.loads(resp.read().decode())


def main():
    print("Building remote package via FTP...")
    # Upload public files to chatrom root
    ftp_upload_tree(PUBLIC, "/public_html/chatrom")
    ftp_upload_tree(SRC, "/public_html/chatrom/src")
    ftp_upload_tree(SQL, "/public_html/chatrom/sql")

    print("Writing config.php ...")
    cfg = build_config()
    save_text(REMOTE_DIR, "config.php", cfg)
    # persist secret for webhook
    Path("/tmp/hamgap_webhook_secret.txt").write_text(WEBHOOK_SECRET)

    print("Running migrate...")
    install_url = f"{DOMAIN}/install.php?key={urllib.parse.quote(WEBHOOK_SECRET)}"
    with urlopen(install_url, context=CTX, timeout=60) as resp:
        print("install:", resp.status, resp.read().decode())

    webhook_url = f"{DOMAIN}/webhook.php?secret={urllib.parse.quote(WEBHOOK_SECRET)}"
    print("Setting webhook:", webhook_url)
    print(json.dumps(tg("setWebhook", url=webhook_url, drop_pending_updates="true"), ensure_ascii=False, indent=2))
    print(json.dumps(tg("getWebhookInfo"), ensure_ascii=False, indent=2))
    print(json.dumps(tg("setMyName", name="هم‌گپ"), ensure_ascii=False, indent=2))
    print(json.dumps(tg("setMyCommands", commands=json.dumps([
        {"command": "start", "description": "شروع / منوی اصلی"},
        {"command": "end", "description": "پایان چت"},
        {"command": "next", "description": "نفر بعدی"},
        {"command": "report", "description": "گزارش کاربر"},
    ], ensure_ascii=False)), ensure_ascii=False, indent=2))
    print("DONE")


if __name__ == "__main__":
    main()
