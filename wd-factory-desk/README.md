# WD Factory Desk — UI Prototype

Interactive HTML prototype for a Windows x64 factory tool concept:

- Menu bar with cascading submenus (Port, Family, Process/ARCO/SF, …)
- SATA / Terminal mode switch
- Port detect → Add (exclusive lock) → Release
- Family 2.5 (48) and 3.5 (66) browser
- Simulated ARCO + SF run queue and telemetry

## Open

Open `index.html` in a browser, or:

```bash
cd wd-factory-desk
python3 -m http.server 8765
```

Then visit `http://localhost:8765`.

This is a UI prototype only — no real drive I/O.
