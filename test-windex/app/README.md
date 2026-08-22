# Windex WD — UI Prototype

**Windex WD** by HamGap — family-centric factory tooling prototype (Windex/SASDEX model) for Win7/10/11 x64.

## Download — Windows desktop test pack

**Zip:**  
https://github.com/petersany325/petersany325/raw/cursor/wd-factory-desk-ui-proto-a3a0/wd-factory-desk/dist/WindexWD-Prototype-Test.zip

1. Unzip (e.g. to `D:\test windex`)  
2. Run **`START-WINDEX-WD.bat`** → opens as a Windows app window (Edge/Chrome app mode)  
3. **Demo unlock** or **File → License Settings…**  
4. Optional real EXE: run `BUILD-WINDOWS.bat` on a PC with Node.js  

See [`DESKTOP.md`](./DESKTOP.md) · project path [`PROJECT-PATH.txt`](./PROJECT-PATH.txt)

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
