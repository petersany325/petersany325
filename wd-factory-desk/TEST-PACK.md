# Windex WD — Test Prototype Pack

HamGap · UI prototype + legacy WD PassThrough driver installer for lab testing.

## Download

After push, use one of:

- **Zip (this pack):** see `dist/WindexWD-Prototype-Test.zip` on the branch  
- **GitHub archive (whole branch):**  
  https://github.com/petersany325/petersany325/archive/refs/heads/cursor/wd-factory-desk-ui-proto-a3a0.zip

## Project path (this PC)

Default lab folder:

```
D:\test windex\
  FW\          ← ARCO+SF packs
  Backup\      ← original HDD dumps
```

See `PROJECT-PATH.txt`.

## What’s inside

| Folder | Contents |
|--------|----------|
| `app/` | Windex WD HTML prototype (menus, backup, license gate) |
| `driver/WdHdSetup/` | WD Drive PassThrough installer (`WdHdSetup.exe` 1.13) |
| `START-WINDEX-WD.bat` | Open the prototype in your browser |
| `START-KEYGEN.bat` | Seller license keygen |
| `START-LOCAL-SERVER.bat` | Optional local HTTP server (Python) |

## Important limits (read first)

1. **UI is a prototype** — no real HDD I/O yet. You can test menus, identify flow (simulated), backup UI, and license activation.
2. **WdHd driver is Win XP/Vista/7 32-bit only** — it will **not** install cleanly on modern Win10/11 x64 as a shippable path. Included so you can inspect / try on a legacy 32-bit lab PC. Win10/11 exclusive access needs a new x64 PassThrough driver (next engineering step).
3. **Never install WdHd on the boot/OS controller.** It hides disks from Windows on that channel.

## Quick test (UI only — any Windows)

1. Unzip the pack.
2. Double-click `START-WINDEX-WD.bat`.
3. On the license gate: click **Demo unlock (14 days)** *or* use `START-KEYGEN.bat` to issue a machine-bound key.
4. Walk: Detect → Identify → Read ROM 4F → Resolve pack → **Full Backup First** → family tools.

## Driver install (legacy 32-bit lab only)

1. Use a **non-production** PC, Windows **7 32-bit** (or XP/Vista 32-bit).
2. Connect DUT HDDs on a **secondary** IDE/SATA channel (not the OS disk).
3. Run `driver\WdHdSetup\WdHdSetup.exe` **as Administrator**.
4. Select the DUT controller/channel → Install.
5. Confirm Windows no longer shows those DUT disks in Explorer (expected).
6. Read `driver\WdHdSetup\WdHdSetup.txt` and the `.doc` in that folder.

To force qualify without ATAPI.sys:

```bat
WdHdSetup.exe -NoAtapi
```

## License test

1. Copy Machine ID from the gate.
2. Open keygen → paste Machine ID → Issue machine-bound key → Activate.
3. Or use **Demo unlock** on the gate (test builds only).

## Next (real product)

- Native Win7/10/11 **x64** app  
- New exclusive PassThrough / SCSI Pass Through driver path  
- Wire Backup / ARCO / SF to real VSC  

## Brand

Windex WD · HamGap logo in `app/assets/`
