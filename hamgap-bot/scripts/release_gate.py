#!/usr/bin/env python3
"""HamGap release gate: preflight → (caller deploys) → verify."""
from __future__ import annotations

import json
import ssl
import sys
import urllib.parse
import urllib.request

CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE
DOMAIN = "https://dev.hdd-land.com"
TOKEN = None  # optional


def get(url: str):
    with urllib.request.urlopen(url, context=CTX, timeout=30) as r:
        return r.read().decode()


def main():
    phase = sys.argv[1] if len(sys.argv) > 1 else "preflight"
    print(f"=== PHASE: {phase} ===")

    ver = json.loads(get(f"{DOMAIN}/version.php"))
    print("version:", ver)

    if phase in ("preflight", "verify"):
        assert ver.get("ok") is True, "version.php failed"
        assert ver.get("code_version"), "missing code_version"

    if phase == "verify":
        expected = sys.argv[2] if len(sys.argv) > 2 else None
        if expected:
            assert ver.get("code_version") == expected, f"expected {expected}, got {ver.get('code_version')}"
            print("OK version matches", expected)
        print("Manual Telegram checks still required: /start, gender, age, city, other-city, menu clear")

    print("DONE", phase)


if __name__ == "__main__":
    main()
