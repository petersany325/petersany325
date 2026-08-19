#!/usr/bin/env python3
"""Generate RustDesk icon assets from HDD LAND brand logo."""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "assets" / "brand"
SOURCE_DIR = ROOT / "source"

ICON_CANDIDATES = [
    BRAND_DIR / "hdd-land-remote-soft-app-icon-v2.png",
    BRAND_DIR / "icon-source.png",
    BRAND_DIR / "hdd-land-logo-en.png",
]


def pick_source() -> Path:
    for candidate in ICON_CANDIDATES:
        if candidate.exists():
            return candidate
    raise FileNotFoundError("No brand icon source found in assets/brand/")


def square_canvas(image: Image.Image) -> Image.Image:
    width, height = image.size
    size = max(width, height)
    canvas = Image.new("RGBA", (size, size), (255, 255, 255, 255))
    canvas.paste(image, ((size - width) // 2, (size - height) // 2))
    return canvas


def main() -> None:
    if not SOURCE_DIR.exists():
        raise SystemExit("Run scripts/setup.sh first to clone RustDesk source.")

    source = pick_source().resolve()
    image = square_canvas(Image.open(source).convert("RGBA"))

    res_dir = SOURCE_DIR / "res"
    flutter_assets = SOURCE_DIR / "flutter" / "assets"
    flutter_win_res = SOURCE_DIR / "flutter" / "windows" / "runner" / "resources"
    flutter_win_res.mkdir(parents=True, exist_ok=True)

    png_sizes = {
        "32x32.png": 32,
        "64x64.png": 64,
        "128x128.png": 128,
        "128x128@2x.png": 256,
        "icon.png": 512,
        "mac-icon.png": 512,
    }

    for filename, size in png_sizes.items():
        image.resize((size, size), Image.LANCZOS).save(res_dir / filename)
        print(f" wrote res/{filename}")

    ico_sizes = [(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)]
    ico_images = [image.resize(size, Image.LANCZOS) for size in ico_sizes]
    ico_images[0].save(
        res_dir / "icon.ico",
        format="ICO",
        sizes=[img.size for img in ico_images],
        append_images=ico_images[1:],
    )
    ico_images[-1].save(
        flutter_win_res / "app_icon.ico",
        format="ICO",
        sizes=[(256, 256)],
    )
    print(" wrote res/icon.ico and flutter/windows/runner/resources/app_icon.ico")

    image.resize((512, 512), Image.LANCZOS).save(flutter_assets / "icon.png")
    logo = Image.open(BRAND_DIR / "hdd-land-logo-en.png").convert("RGBA")
    logo.save(flutter_assets / "logo.png")
    print(" wrote flutter/assets/icon.png and flutter/assets/logo.png")


if __name__ == "__main__":
    main()
