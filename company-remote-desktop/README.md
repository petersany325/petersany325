# Company Remote Desktop

AnyDesk-like remote desktop for **company training PCs**, hosted entirely by you. No AnyDesk cloud.

1. Run **Hub** on a company server (known host + port).
2. Install **Agent** on each training PC. It registers with the Hub and gets a **unique ID**.
3. Install **Viewer** on instructor/trainee PCs. Connect with **ID + password** — not the training PC’s LAN IP.

Windows is the primary Agent/Viewer target (DXGI capture, SendInput, WIC JPEG). The Hub is portable C++ (Windows and Linux).

## Apps

| Binary | Where | What |
| --- | --- | --- |
| `hub` | Company server | Issues IDs, remembers agents, relays one viewer session per agent |
| `agent` (`host` is the same app) | Training PC | Shows ID, stays online, captures screen, injects input |
| `viewer` | Instructor PC | Connects by ID through the Hub |

CMake targets: `hub`, `agent`, `host`, `viewer`, `crd_protocol`, tests.

## AnyDesk-like steps

1. **Server:** `hub.exe --bind 0.0.0.0 --port 5938 --data hub-state.db`  
   Open inbound TCP **5938** on the server firewall.
2. Put `config.json` next to Agent/Viewer (or pass `--hub` / `--hub-port`):

   ```json
   { "hub_host": "hub.company.local", "hub_port": 5938 }
   ```

3. **Training PC:** start `agent.exe`. First run talks to the Hub, receives an ID such as `390 367 767`, and shows it. Set/save an access password. The ID is stored under `%APPDATA%\CompanyRemoteDesktop\agent.json` and stays the same after reboot.
4. **Instructor:** start `viewer.exe`, enter that ID, Hub address, and the access password. Click Connect.
5. Remote screen + mouse/keyboard go through the Hub relay.

Default Hub for a laptop demo: `127.0.0.1:5938` (run Hub on the same PC).

## Build (MSVC)

```bat
cd company-remote-desktop
cmake -S . -B build -G "Visual Studio 17 2022" -A x64
cmake --build build --config Release
```

Linux (Hub + tests + headless agent):

```bash
cmake -S company-remote-desktop -B company-remote-desktop/build -DCMAKE_CXX_COMPILER=g++
cmake --build company-remote-desktop/build
ctest --test-dir company-remote-desktop/build --output-on-failure
```

## Run (Windows)

```bat
hub.exe --port 5938 --data hub-state.db
agent.exe --hub 192.168.1.10 --hub-port 5938
viewer.exe --id 390367767 --hub 192.168.1.10 --port 5938 --password TrainRoom1
```

Firewall on the **Hub server** (not each training PC):

```bat
netsh advfirewall firewall add rule name="CRD Hub" dir=in action=allow protocol=TCP localport=5938
```

## Installer

`CompanyRemoteDesktop-Setup-x64.exe` installs Agent + Viewer into Program Files and writes `config.json` (Hub host/port asked during setup). Deploy `hub.exe` on the server from the release zip.

## Protocol

See [PROTOCOL.md](PROTOCOL.md). Access passwords use SHA-256(nonce || password). Device identity uses a Hub-issued secret. The Hub relays JPEG frames and input. TLS and NAT hole-punching are documented follow-ups.

## Tests

- `test_protocol` — encoding, IDs, SHA-256
- `test_loopback` — legacy direct host/viewer path
- `test_hub_relay` — register → ID → viewer-by-ID → frame + input + busy + bad password
