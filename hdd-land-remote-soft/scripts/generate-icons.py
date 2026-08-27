#!/usr/bin/env python3
"""Generate RustDesk icon assets from the official HDD LAND company logo."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "assets" / "brand"
SOURCE_DIR = ROOT / "source"

# Always use the official HDD LAND logos provided by the company.
COMPANY_LOGO = BRAND_DIR / "hdd-land-logo-en-black.png"
COMPANY_LOGO_FALLBACK = BRAND_DIR / "hdd-land-logo-en.png"
COMPANY_BANNER = BRAND_DIR / "hdd-land-banner-sediv.png"


def trim_whitespace(image: Image.Image, padding: int = 8) -> Image.Image:
    """Crop to logo content; works for white or black backgrounds."""
    rgba = image.convert("RGBA")
    alpha = rgba.split()[3]
    if alpha.getextrema()[0] < 255:
        bbox = alpha.getbbox()
    else:
        rgb = rgba.convert("RGB")
        px = rgb.load()
        w, h = rgb.size
        min_x, min_y, max_x, max_y = w, h, 0, 0
        found = False
        for y in range(h):
            for x in range(w):
                r, g, b = px[x, y]
                if r > 20 or g > 20 or b > 20:
                    found = True
                    min_x = min(min_x, x)
                    min_y = min(min_y, y)
                    max_x = max(max_x, x)
                    max_y = max(max_y, y)
        bbox = (min_x, min_y, max_x + 1, max_y + 1) if found else None
    if not bbox:
        return rgba
    left, top, right, bottom = bbox
    left = max(0, left - padding)
    top = max(0, top - padding)
    right = min(rgba.width, right + padding)
    bottom = min(rgba.height, bottom + padding)
    return rgba.crop((left, top, right, bottom))


def to_square_icon(logo: Image.Image, size: int, margin_ratio: float = 0.08, bg=(255, 255, 255, 255)) -> Image.Image:
    """Fit logo inside a square icon canvas (white bg for Windows taskbar)."""
    logo = trim_whitespace(logo)
    canvas = Image.new("RGBA", (size, size), bg)
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

    if not COMPANY_LOGO.exists() and not COMPANY_LOGO_FALLBACK.exists():
        raise SystemExit(f"Missing company logo in {BRAND_DIR}")

    logo_path = COMPANY_LOGO if COMPANY_LOGO.exists() else COMPANY_LOGO_FALLBACK
    logo = Image.open(logo_path).convert("RGBA")
    print(f"Using company logo: {logo_path.name} ({logo.size[0]}x{logo.size[1]})")
    if COMPANY_BANNER.exists():
        print(f"Banner available: {COMPANY_BANNER.name}")

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

    # In-app logo: exact company logo (black background version)
    trim_whitespace(logo).save(flutter_assets / "logo.png")
    to_square_icon(logo, 512).save(flutter_assets / "icon.png")
    trim_whitespace(logo).save(BRAND_DIR / "icon-source.png")
    if COMPANY_BANNER.exists():
        Image.open(COMPANY_BANNER).convert("RGBA").save(flutter_assets / "banner.png")
        print(" wrote flutter/assets/banner.png (installer/splash)")
    print(" wrote flutter/assets/logo.png (exact logo) and flutter/assets/icon.png")


if __name__ == "__main__":
    main()
