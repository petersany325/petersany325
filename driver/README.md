# Driver stack — Windex WD (HamGap)

## Quick links

| Pack | OS | Use |
|------|-----|-----|
| **WxWdPass v3** (`driver/WxWdPass-v3/`) | Win10/11 x64 AHCI | Production target — spec + IOCTL |
| **WdHd legacy** (`test-windex/driver/WdHdSetup/`) | Win7 32-bit IDE | Lab reference only |

## Install

```bat
test-windex\INSTALL-DRIVER.bat
```

- Option **1** → `driver/WxWdPass-v3/install/INSTALL-AHCI.bat`
- Option **2** → legacy `WdHdSetup.exe`

## Documentation

- Legacy analysis: `driver/WxWdPass-v3/docs/LEGACY-WDHDD-32BIT.md`
- v3 spec (AHCI/SATA/lock): `driver/WxWdPass-v3/docs/WXWD-PASS-v3-SPEC.md`
- IOCTL header: `driver/WxWdPass-v3/include/wxwd_ioctl.h`

## Status (Aug 2026)

| Component | Status |
|-----------|--------|
| Legacy WdHd pack in repo | ✅ Reference binaries |
| WxWdPass v3 spec + IOCTL | ✅ This commit |
| WxWdPassAhci.sys kernel driver | ⏳ Phase 2 — WDK build |
| WxWdIoService fallback | ⏳ Phase 1 |
| WxWdPassSetup.exe GUI | ⏳ Phase 3 |
| Windex WD UI → real IOCTL | ⏳ After Phase 1 |

**Important:** The HTML/Electron prototype still uses **simulated** port lock. Real HDD I/O requires WxWdPass v3 or SCSI pass-through service.
