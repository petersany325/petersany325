# Windex WD — UI Prototype

**Windex WD** by HamGap — family-centric factory tooling prototype (Windex/SASDEX model) for Win7/10/11 x64.

## Download test pack (UI + driver installer)

**Zip:**  
https://github.com/petersany325/petersany325/raw/cursor/wd-factory-desk-ui-proto-a3a0/wd-factory-desk/dist/WindexWD-Prototype-Test.zip

Contents: prototype UI, HamGap branding, license/demo unlock, and legacy `WdHdSetup` (Win7 **32-bit** lab only).

1. Unzip → `START-WINDEX-WD.bat`  
2. Click **Demo unlock (14 days)**  
3. For driver (legacy 32-bit only): `INSTALL-DRIVER.bat` — never on the OS disk channel  

Details: [`TEST-PACK.md`](./TEST-PACK.md)

Brand assets: `assets/logo.png` (HamGap logo), favicon + app icons.

## Flow

1. Detect / lock port  
2. Identify (Vendor / Family / Model / SN / ATA FW)  
3. If WD + Family → Read ROM module `0x4F` → resolve ARCO/SF pack  
4. **Full Backup first**: ROM · ROM modules · SA modules · tracks  
5. Then repair tools (manual repair structure next)

## Two firmware roots

1. **ARCO + SF package** — factory pack (`epath`)  
2. **Backup original HDD FW** — `Backup\{Family}\{SN}\` dump from this drive  

## License (for sales)

App opens locked until a serial is activated. Test builds: **Demo unlock**. Sales: Machine ID → seller keygen (`license-keygen.html`).

See [`LICENSE-SYSTEM.md`](./LICENSE-SYSTEM.md).

## Open (dev)

```bash
cd wd-factory-desk
python3 -m http.server 8765
```

UI prototype only — no real drive I/O yet. Win10/11 x64 needs a new PassThrough driver (not this legacy WdHd pack).
