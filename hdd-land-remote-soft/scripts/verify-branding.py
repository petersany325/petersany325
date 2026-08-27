#!/usr/bin/env python3
"""Verify HDD Land Remote Soft branding is applied to RustDesk source."""

from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "source"

CHECKS = [
    (SOURCE / "Cargo.toml", 'name = "hddlandremote"'),
    (SOURCE / "Cargo.toml", "HDD Land Remote Soft"),
    (SOURCE / "libs/hbb_common/src/config.rs", '"HDD Land Remote Soft"'),
    (SOURCE / "flutter/windows/runner/Runner.rc", "HDD LAND"),
    (SOURCE / "res/icon.ico", None),
    (ROOT / "assets/brand/hdd-land-logo-en.png", None),
]


def main() -> int:
    failed = 0
    for path, needle in CHECKS:
        if not path.exists():
            print(f"FAIL missing: {path.relative_to(ROOT)}")
            failed += 1
            continue
        if needle is None:
            print(f" OK  {path.relative_to(ROOT)}")
            continue
        content = path.read_text(encoding="utf-8", errors="ignore")
        if needle in content:
            print(f" OK  {path.relative_to(ROOT)}")
        else:
            print(f"FAIL {path.relative_to(ROOT)} — expected: {needle!r}")
            failed += 1
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
