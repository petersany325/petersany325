# WD Factory Desk — UI Prototype

Family-centric interactive prototype for Win7/10/11 x64 factory tooling (Windex/SASDEX model).

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

## Menu layout (target)

Each **Family** has its own cascading tool menu:

- **Backup** (Full / ROM / ROM modules / SA / tracks / Paths) — do first
- Cut Head
- Zone Ops (list / cut / del)
- **ATA Scan** / **CHS Scan**
- P-List / G-List
- Modules / DIR
- ARCO / SF / DCM

## Open

```bash
cd wd-factory-desk
python3 -m http.server 8765
```

Or open `index.html` directly.

## Docs

- [`FAMILY-FW-REFERENCE.md`](./FAMILY-FW-REFERENCE.md)
- [`family-fw-reference.json`](./family-fw-reference.json)

UI prototype only — no real drive I/O.
