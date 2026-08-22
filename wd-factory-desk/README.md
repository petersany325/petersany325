# WD Factory Desk — UI Prototype

Family-centric interactive prototype for Win7/10/11 x64 factory tooling (Windex/SASDEX model).

## Menu layout (target)

Each **Family** has its own cascading tool menu:

- Cut Head
- Zone Ops (list / cut / del)
- P-List (view / add / clear)
- G-List (view / G→P / clear)
- Modules / DIR
- ARCO (Full / Hot / Mini)
- SF Chain
- DCM / Capacity

This lets every family add/extend tools independently.

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
