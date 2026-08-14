#!/usr/bin/env python3
"""Deploy HamGap PHP files via cPanel session + Fileman UAPI.

Basic-auth /execute often returns 401 on this host; login_only + cpsess works.
Do not overwrite config.php unless --write-config is passed.
"""
from __future__ import annotations

import argparse
import json
import os
import ssl
import sys
import time
import urllib.parse
import urllib.request
import http.cookiejar
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get("CPANEL_SESSION_HOST", "https://hdd-land.com:2083").rstrip("/")
USER = os.environ.get("CPANEL_USERNAME") or os.environ.get("CPANEL_USER", "hddrecov")
PASS = os.environ["CPANEL_PASSWORD"]
REMOTE = "/home/hddrecov/public_html/chatrom"
DOMAIN = os.environ.get("DOMAIN", "https://dev.hdd-land.com").rstrip("/")

CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE

DEFAULT_FILES = [
    ("src/Handlers.php", f"{REMOTE}/src"),
    ("src/Database.php", f"{REMOTE}/src"),
    ("src/Keyboards.php", f"{REMOTE}/src"),
    ("src/Matcher.php", f"{REMOTE}/src"),
    ("src/Migrator.php", f"{REMOTE}/src"),
    ("src/Gender.php", f"{REMOTE}/src"),
    ("src/Telegram.php", f"{REMOTE}/src"),
    ("src/IranLocations.php", f"{REMOTE}/src"),
    ("public/webhook.php", REMOTE),
    ("public/version.php", REMOTE),
]


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


def upload(opener, token: str, dest: str, path: Path) -> dict:
    boundary = f"----Bound{int(time.time() * 1000)}"
    raw = path.read_bytes()
    body = b""
    for k, v in {"dir": dest, "overwrite": "1"}.items():
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    body += (
        f'--{boundary}\r\nContent-Disposition: form-data; name="file-1"; filename="{path.name}"\r\n'
        f"Content-Type: application/octet-stream\r\n\r\n"
    ).encode() + raw + b"\r\n"
    body += f"--{boundary}--\r\n".encode()
    req = urllib.request.Request(
        f"{BASE}{token}/execute/Fileman/upload_files",
        data=body,
        method="POST",
    )
    req.add_header("Content-Type", f"multipart/form-data; boundary={boundary}")
    with opener.open(req, timeout=180) as r:
        return json.loads(r.read().decode())


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--verify-only", action="store_true")
    args = ap.parse_args()

    if not args.verify_only:
        ok = True
        for rel, dest in DEFAULT_FILES:
            path = ROOT / rel
            for attempt in range(1, 5):
                try:
                    opener, token = login()
                    res = upload(opener, token, dest, path)
                    print(path.name, "status", res.get("status"), "errors", res.get("errors"))
                    if res.get("status") == 1:
                        break
                except Exception as e:
                    print(path.name, "attempt", attempt, type(e).__name__, e)
                    if attempt == 4:
                        ok = False
                    time.sleep(2 * attempt)
            time.sleep(0.5)
        if not ok:
            return 1

    with urllib.request.urlopen(f"{DOMAIN}/version.php", context=CTX, timeout=30) as r:
        ver = json.loads(r.read().decode())
    print("version", ver)
    return 0 if ver.get("ok") else 2


if __name__ == "__main__":
    sys.exit(main())
