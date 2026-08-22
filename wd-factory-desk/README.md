# Windex WD — UI Prototype

**App path (saved):** `D:\test windex\WindexWD-Prototype-Test`

## Download & run (Windows)

https://github.com/petersany325/petersany325/raw/cursor/wd-factory-desk-ui-proto-a3a0/wd-factory-desk/dist/WindexWD-Prototype-Test.zip

1. Unzip to `D:\test windex\`
2. `SETUP-LAB.bat` → creates `FW` + `Backup`
3. `CHECK.bat` → verify files
4. **`RUN.bat`** → opens app (`http://127.0.0.1:8765`)

## Online demo

https://htmlpreview.github.io/?https://raw.githubusercontent.com/petersany325/petersany325/cursor/wd-factory-desk-ui-proto-a3a0/test-windex/WindexWD-Prototype-Test/resources/app/index.html

See [`ONLINE.md`](./ONLINE.md)

## Config

- [`project-path.json`](./project-path.json) — saved paths
- [`PROJECT-PATH.txt`](./PROJECT-PATH.txt) — folder layout

## Flow

Detect → Identify → ROM 0x4F → Backup first → family tools

## License

Demo unlock (14 days) or seller keygen (`license-keygen.html`). See [`LICENSE-SYSTEM.md`](./LICENSE-SYSTEM.md).

## Driver (real HDD I/O)

Legacy WdHd = Win7 32-bit only. Modern AHCI: [`driver/WxWdPass-v3/docs/WXWD-PASS-v3-SPEC.md`](../driver/WxWdPass-v3/docs/WXWD-PASS-v3-SPEC.md)

UI prototype — simulated I/O until WxWdPass v3 is built.
