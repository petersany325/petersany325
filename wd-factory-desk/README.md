# Windex WD — UI Prototype

**Windex WD** by HamGap — family-centric factory tooling prototype (Windex/SASDEX model) for Win7/10/11 x64.

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

Switch active source in Backup → Firmware Paths.

## License (for sales)

App opens locked until a serial is activated. Preferred flow: customer sends **Machine ID** → seller issues a **machine-bound** key from `license-keygen.html`.

See [`LICENSE-SYSTEM.md`](./LICENSE-SYSTEM.md).

## Open

```bash
cd wd-factory-desk
python3 -m http.server 8765
```

- App: http://localhost:8765/  
- Seller keygen: http://localhost:8765/license-keygen.html  

## Docs

- [`FAMILY-FW-REFERENCE.md`](./FAMILY-FW-REFERENCE.md)
- [`family-fw-reference.json`](./family-fw-reference.json)
- [`LICENSE-SYSTEM.md`](./LICENSE-SYSTEM.md)

UI prototype only — no real drive I/O.
