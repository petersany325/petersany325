#!/usr/bin/env python3
"""Generate RustDesk icon assets from the official HDD LAND company logo."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "assets" / "brand"
SOURCE_DIR = ROOT / "source"

# Always use the company logo the user provided — never AI mockups.
COMPANY_LOGO = BRAND_DIR / "hdd-land-logo-en.png"


def trim_whitespace(image: Image.Image, padding: int = 8) -> Image.Image:
    """Crop to logo content, preserving aspect ratio."""
    rgba = image.convert("RGBA")
    background = Image.new("RGBA", rgba.size, (255, 255, 255, 255))
    diff = Image.alpha_composite(background, rgba).convert("RGB")
    bbox = diff.getbbox()
    if not bbox:
        return rgba
    left, top, right, bottom = bbox
    left = max(0, left - padding)
    top = max(0, top - padding)
    right = min(rgba.width, right + padding)
    bottom = min(rgba.height, bottom + padding)
    return rgba.crop((left, top, right, bottom))


def to_square_icon(logo: Image.Image, size: int, margin_ratio: float = 0.08) -> Image.Image:
    """Fit the wide HDD LAND logo inside a square icon canvas."""
    logo = trim_whitespace(logo)
    canvas = Image.new("RGBA", (size, size), (255, 255, 255, 255))
    margin = int(size * margin_ratio)
    inner = size - margin * 2
    lw, lh = logo.size
    scale = min(inner / lw, inner / lh)
    nw, nh = max(1, int(lw * scale)), max(1, int(lh * scale))
    resized = logo.resize((nw, nh), Image.LANCZOS)
    canvas.paste(resized, ((size - nw) // 2, (size - nh) // 2), resized)
    return canvas


def main() -> None:
    if not SOURCE_DIR.exists():
        raise SystemExit("Run scripts/setup.sh first to clone RustDesk source.")

    if not COMPANY_LOGO.exists():
        raise SystemExit(f"Missing company logo: {COMPANY_LOGO}")

    logo = Image.open(COMPANY_LOGO).convert("RGBA")
    print(f"Using company logo: {COMPANY_LOGO.name} ({logo.size[0]}x{logo.size[1]})")

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
        to_square_icon(logo, size).save(res_dir / filename)
        print(f" wrote res/{filename}")

    ico_sizes = [16, 32, 48, 64, 128, 256]
    ico_images = [to_square_icon(logo, size) for size in ico_sizes]
    ico_images[0].save(
        res_dir / "icon.ico",
        format="ICO",
        sizes=[(s, s) for s in ico_sizes],
        append_images=ico_images[1:],
    )
    ico_images[-1].save(flutter_win_res / "app_icon.ico", format="ICO", sizes=[(256, 256)])
    print(" wrote res/icon.ico and flutter/windows/runner/resources/app_icon.ico")

    # In-app logo: exact company logo, no square crop
    trim_whitespace(logo).save(flutter_assets / "logo.png")
    to_square_icon(logo, 512).save(flutter_assets / "icon.png")
    trim_whitespace(logo).save(BRAND_DIR / "icon-source.png")
    print(" wrote flutter/assets/logo.png (exact logo) and flutter/assets/icon.png")


if __name__ == "__main__":
    main()
