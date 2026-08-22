# Family / FW / ARCO-SF Reference (from `F palmer`)

Source: Windex `F palmer` script. Prototype reference for three families — pattern extends to the rest.

## Boot / identify flow (first step)

```
1) Detect port + Add/Lock exclusive
2) ATA Identify + vscon/vscid
   → Vendor, Family ID, Model, SN, ATA FW, heads, …
3) If Vendor = WD (WDC) AND Family known:
   Read ROM image
   Find marker ROYL (0x4C594F52)
   DIR entry 0x0B
   Module id 0x4F → Overlay FW Rev string (szOVLFWRev)
   Module id 0x0D → Ctrl FW Rev (optional)
4) Map ROM 4F FW string → epath FW pack folder
5) **Full Backup first** (before repair):
   - Backup ROM (`SVROM` → `ROM.BIN`)
   - Backup ROM modules (DIR `0x0B` dumps, esp. `0x4F` / `0x0D`)
   - Backup SA modules (`rdflnom` → `0x0B`/`0x33`/`0x34`/`0x47`/…)
   - Backup tracks (`svtrack` → `trkNNN.bin`)
6) Then ARCO / SF / family tools / manual repair
```

### Two firmware path roots

| # | Root | Purpose |
|---|------|---------|
| 1 | **ARCO + SF package** | Factory pack folder (`epath`) with `.rpm` overlays |
| 2 | **Backup original HDD FW** | Session dump of this drive: `Backup\{Family}\{SN}\` |

Active source for write/repair can be switched: Pack **or** Original backup.

Suggested backup layout:

```
D:\Backup\{Family}\{SN}\
  ROM.BIN
  ROM_MOD\mod_0x4F.bin  mod_0x0D.bin  …
  SA\sa_0x0B.bin  sa_0x33.bin  sa_0x34.bin  sa_0x47.bin  …
  TRACK\trk000.bin  …
```

Source logic in `F palmer` `headSelect`: scan ROM for `0x4F` entry and copy 8-char FW rev.

## How selection works

```
Family2.5 / Family3.5
  → Auto (vscon;vscid) or Manual ID
  → optional PCB / FW Ver prompt
  → set epath = D:\2.5\NN\FW\  or  D:\3.5\NN\FW\
  → set flags (arco_type, tar_file, Tscan_type, …)
  → later run uses dlfile(epath + FileID.rpm) + XF + msf
```

## Family comparison

| | ATLANTIS | FIREBIRD | PALMER |
|--|----------|----------|--------|
| Form | 3.5 | 2.5 | 2.5 |
| Menu ID | 01 | 10 | 46 |
| Family IDs (hex) | `0xAB`, `0xB9`, `0x9D` | `0xE8` | `0x29A` |
| FW / PCB choices | `701537`, `771668`, `701590` | `0353B`, `0379C` | `020PP`, `0506B` |
| epath examples | `D:\3.5\1\701537\` | `D:\2.5\10\0353B\` | `d:\2.5\46\020PP\` |
| `arco_type` | **0** (legacy path) | **0** | **1** (new ARCO IDs) |
| `tar_file` | `0xC8` | `0x290B` | `0x2420` |
| `tpi_file` / type | `0xC3`, `tpi_type=0` | `tpi_type=1` | `tpi_type=1` |
| `dcm_type` | 0 | 1 | 1 |
| `Tscan_type` | (default) | `0x34D1` | `0x34D1` |
| Extra SF steps | fewer (`norun_target=1`) | moderate | many (`type_243b/244a/241e/…=1`) |

### Meaning of key flags

| Flag | Role |
|------|------|
| `epath` | Folder of `.rpm` overlays for that FW |
| `arco_type=0` | Use older ARCO FileIDs (`0xC4` / TouchDn style) |
| `arco_type=1` | Use newer ARCO FileIDs (`0x2407` / `0x2409` + extra cals) |
| `tar_file` | Capacity / TPI-cap exec FileID |
| `Tscan_type` | Surface/scan PST FileID (often `0x34D1`) |
| `type_xxxx=1` | Enable that XF step in the SF chain |

### Command pattern (all families)

```
vscon
dlfile <FileID>                 # loads epath\<id>.rpm into drive
XF <FileID>, <TestID>, parms…   # KeySector AC_EXECFILE + SmartWrLog 0xBE
msf                             # PollTestStatus until done
```

ARCO examples:

- Full legacy: `XF 0xC4, 0x46, HeadDCM, MediaDCM…` → `msf`
- Full new: `XF 0x2407, 0x46, …` → `msf`
- Hot: `XF 0xC4/0x2409, 0x4A, …` → `msf`

---

## SA / module access codes (shared)

### VSC Action Codes (`ENG1`)

| Code | Name | Use |
|------|------|-----|
| 8 | `AC_RDWRResFile` | Read/Write resident SA file |
| 25 | `AC_EXECFILE` | Run PST/overlay (`XF`) |
| 39 | `AC_HDDEPOPCTRL` | Manual head depop / cut |
| 40 | `AC_FileMgr` | File system manager |
| 41 | `AC_PSTTEST` | PST test mode |
| 48 / 114 | `AC_FmtSelect` / `AC_FMTSEL` | Format / capacity select |

Transport for most SA ops: fill KeySector → `SmartWrLog` with `S=0xBE` (control) / `0xBF` (data).

### Resident file IDs used in scripts

| FileID | Typical role |
|--------|----------------|
| `0x0B` / `0x20B` | Flash / module DIR |
| `0x02` | Config / related |
| `0x28` | PST table |
| `0x31`–`0x36`, `0xE0`, `0xE6`, `0xE8` | SF / IBI logs (`clrsflog`) |
| `0x33` | **P-List** (`plist`, `addp`) |
| `0x34` | **G-List** (`VG`, `VGlist`, `gtop`) |
| `0x47` | DCM / config sector (Head/Media DCM) |
| `0xC3` | TPI cal overlay |
| `0xC4` / `0x2401` / `0x2407` / `0x2409` | ARCO overlays |
| `0xC8` / `0x2420` / `0x290B` | Target capacity / TPI-cap |

### Script commands → SA

| User intent | Script command | Under the hood |
|-------------|----------------|----------------|
| Read SA file | `rdflnom <id>` | VSC resident read |
| Write SA file | `wrflnom <id>` | VSC resident write |
| Clear module | `ClrRES` / `ClearFile` | read-modify-clear-write |
| View DIR | `dir` / `Map` / `dirrom` | DIR `0x0B`/`0x20B` |
| View P-List | `plist` | `rdflnom 33h` + parse |
| View G-List | `VG` / `gtop` | `rdflnom 34h` + parse |
| Add P defect | `addp hd,cyl,…` | edit P-List then write |
| G→P merge | `gtop` | merge then write |
| Cut head | `kill param, head` | `AC_HDDEPOPCTRL` |

---

## Monitor / “graph” fields (`msf`)

From `PollTestStatus` / `vscstat`:

- `PTMId`, `LastActCode`, `ExtErr`
- `CurVirHd`, `CurZOne`, `CurVirCyl`
- `BGProgress` (for some PTMs)
- Elapsed / ARCO timers

UI should show these as live telemetry while ARCO/SF runs — not a separate chart engine.

---

## JSON

Machine-readable copy: [`family-fw-reference.json`](./family-fw-reference.json)
