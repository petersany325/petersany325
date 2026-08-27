# HDD Land Remote Soft

Enterprise remote desktop for HDD LAND — full mouse/keyboard control and file transfer on company LAN.

Based on [RustDesk](https://github.com/rustdesk/rustdesk) with custom HDD LAND branding.

## Features

- Full remote desktop control (mouse + keyboard)
- Bidirectional file transfer
- LAN / self-hosted relay support
- Branded as **HDD Land Remote Soft** with official HDD LAND logo

## Platform support

| Windows | Build | Notes |
|---------|-------|-------|
| 10 / 11 64-bit | Custom Flutter build | Primary — use this project |
| 7 32/64-bit | RustDesk x86-sciter legacy | See BUILD.md fallback |

## Project structure

```
hdd-land-remote-soft/
├── assets/brand/          # HDD LAND logos and brand assets
├── config/branding.env    # App name, company, server settings
├── deploy/                # Windows install & LAN deploy scripts
├── patches/branding.patch # RustDesk source branding patch
├── scripts/               # setup.sh, generate-icons.py
├── source/                # RustDesk source (after setup.sh)
├── BUILD.md               # Windows build instructions
└── README.md
```

## Quick start

### 1. Setup (apply branding)

```bash
cd hdd-land-remote-soft
chmod +x scripts/setup.sh
./scripts/setup.sh
```

### 2. Build on Windows

See [BUILD.md](BUILD.md) — requires Visual Studio + Flutter + Rust.

### 3. Install on company PCs

```powershell
# Single PC (Admin PowerShell)
.\deploy\install-windows.ps1 -InstallerPath ".\HDDLandRemote.exe"

# Multiple PCs on LAN
.\deploy\deploy-lan.ps1 -ComputerList pcs.txt -Installer \\server\share\HDDLandRemote.exe
```

## Brand assets

| File | Use |
|------|-----|
| `assets/brand/hdd-land-logo-en.png` | Official HDD LAND English logo |
| `assets/brand/hdd-land-remote-soft-app-icon-v2.png` | App icon source |
| `assets/brand/hdd-land-remote-soft-brand-identity-v2.png` | Brand guidelines |

## Connection (LAN)

1. Install **HDD Land Remote Soft** on all PCs
2. Note each PC's **ID** shown in the app
3. From IT machine: enter remote PC IP or ID → connect
4. Use **File Transfer** tab for upload/download

For internet access: set up RustDesk hbbs/hbbr on internal server + VPN.

## Re-apply branding after RustDesk update

```bash
rm -rf source
./scripts/setup.sh
```

## License

RustDesk is licensed under AGPL-3.0. This custom client must comply with AGPL if distributed.

© 2026 HDD LAND — https://hdd-land.com
