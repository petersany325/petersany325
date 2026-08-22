# Driver stack — Windex WD

## Windows 11 / 10 x64 (production path)

**WxWdPass v3** — AHCI/SATA exclusive port for factory repair.

```
D:\test windex\INSTALL-DRIVER.bat          ← Run as Administrator
D:\test windex\driver\WxWdPass-v3\
```

| Script | Purpose |
|--------|---------|
| `install/INSTALL-WIN11.bat` | Phase 1 install + AHCI scan |
| `install/CHECK-DRIVER.bat` | Verify state + scan disks |
| `install/UNINSTALL-WIN11.bat` | Remove Phase 1 state |
| `service/WxWdIoService.ps1` | Scan / Lock / Unlock DUT disk |
| `docs/INSTALL-WIN11-FA.md` | Persian guide |
| `docs/WXWD-PASS-v3-SPEC.md` | Full architecture |

**Phase 1 (now):** boot-disk protection, AHCI enumeration, disk offline lock.  
**Phase 2 (next):** `WxWdPassAhci.sys` kernel filter + IOCTL.

## Legacy (do NOT use on Win11)

`test-windex/driver/WdHdSetup/` — Win7 32-bit IDE only.
