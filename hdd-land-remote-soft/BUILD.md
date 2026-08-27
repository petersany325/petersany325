# Build HDD Land Remote Soft (Windows)

Branded RustDesk client for HDD LAND. Build must run on **Windows 10/11** (recommended) or Windows Server with Visual Studio.

## Prerequisites (Windows)

1. **Visual Studio 2022** with:
   - Desktop development with C++
   - Windows 10/11 SDK
2. **Rust** (stable): https://rustup.rs
3. **Flutter 3.24.x** (for Win 10/11 build)
4. **vcpkg** (for libvpx, libyuv, opus)
5. **Python 3** + Pillow (for icon generation)

## Quick setup

```powershell
# 1. Clone this repo and run setup (Linux/WSL or after clone on Windows)
bash scripts/setup.sh

# 2. On Windows - install deps (see RustDesk docs)
# https://rustdesk.com/docs/en/dev/build/windows/
```

## Build Win 10 / 11 (Flutter, 64-bit)

```powershell
cd source
python build.py --flutter
```

Output: `source/flutter/build/windows/x64/runner/Release/HDDLandRemote.exe`

Rename/copy as needed after build (exe name follows `Cargo.toml` → `hddlandremote.exe`).

## Build Win 7 (Legacy 32-bit Sciter)

Win 7 requires the **x86-sciter** legacy build from RustDesk releases, rebranded separately, OR:

```powershell
# Use official RustDesk 1.4.x x86-sciter + manual config
# https://github.com/rustdesk/rustdesk/releases
# File: rustdesk-1.4.x-x86-sciter.exe
```

For full custom branding on Win 7, use UltraVNC as fallback (see README).

## Silent install (after build)

```powershell
.\HDDLandRemote.exe --silent-install
```

Or use `deploy/install-windows.ps1`.

## LAN deployment

1. Build once on a build machine
2. Copy `HDDLandRemote.exe` to file share
3. Run `deploy/deploy-lan.ps1` with PC list

## Self-hosted relay (optional)

Edit `config/branding.env` and set:

```env
RENDEZVOUS_SERVER=relay.hdd-land.local
RELAY_SERVER=relay.hdd-land.local
PUBLIC_KEY=your_key
```

Then embed in custom config before build (RustDesk Server hbbs/hbbr).

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `librustdesk.dll` not found | Build Flutter runner, not only cargo |
| Win 7 install fails | Use x86-sciter legacy build |
| Wrong app name in tray | Re-run `scripts/generate-icons.py` and rebuild |

## License

Based on RustDesk (AGPL-3.0). Custom branding © HDD LAND.
