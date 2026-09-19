# Company Remote Desktop

AnyDesk-like remote desktop for **company training PCs**, hosted entirely by you. No AnyDesk cloud.

Production Hub (baked into Agent, Viewer, Setup, and `config.json`):

**`hdd-land.com` port `5938`**

End users do not type a Hub address. They open one window, see **My ID**, and **Connect to ID**.

1. Run **Hub** on the company server (`hdd-land.com`) — see [HUB-DEPLOY.md](HUB-DEPLOY.md).
2. Install **Company Remote Desktop** on each PC. It auto-connects to the Hub and shows a unique ID.
3. Enter the other person’s ID + password and connect. The Hub relays the session.

Windows is the primary desktop target (DXGI capture, SendInput, WIC JPEG). The Hub is portable C++ (Linux x86_64 for production).

## Apps

| Binary | Where | What |
| --- | --- | --- |
| `hub` | Company server (`hdd-land.com`) | Issues IDs, remembers agents, relays one viewer session per agent |
| `CompanyRemoteDesktop` | Each Windows PC | One window: My ID + Connect to ID (AnyDesk-style) |
| `agent` | Optional | ID / unattended password only |
| `viewer` | Optional | Connect to a remote ID only |

CMake targets: `hub`, `CompanyRemoteDesktop`, `agent`, `host`, `viewer`, `crd_protocol`, tests.

## Use (Windows)

1. Start `CompanyRemoteDesktop.exe`. Status shows **Connecting to Hub hdd-land.com…** then your ID (`390 367 767`).
2. Save an unattended password. Share **ID + password** with the other person.
3. Type their Remote ID + password. Click **Connect**.

If the Hub is down, the ID area shows **Hub unreachable — retrying…** and the status bar names `hdd-land.com:5938`.

`config.json` next to the exe (also the installer default):

```json
{ "hub_host": "hdd-land.com", "hub_port": 5938 }
```

Optional CLI override (IT only): `--hub` / `--hub-port`. Do not ship `127.0.0.1` as the production default.

## Deploy Hub (Linux)

See [HUB-DEPLOY.md](HUB-DEPLOY.md): bind `0.0.0.0:5938`, systemd unit, firewall TCP 5938.

```bash
./hub --bind 0.0.0.0 --port 5938 --data /var/lib/crd/hub-state.db
```

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

## Installer

`CompanyRemoteDesktop-Setup-x64.exe` installs the combined app, writes `config.json` for **hdd-land.com:5938**, and does not ask for a Hub address.

## Protocol

See [PROTOCOL.md](PROTOCOL.md). Access passwords use SHA-256(nonce || password). Device identity uses a Hub-issued secret. The Hub relays JPEG frames and input. TLS and NAT hole-punching are documented follow-ups.

## Tests

- `test_protocol` — encoding, IDs, SHA-256
- `test_loopback` — legacy direct host/viewer path
- `test_hub_relay` — register → ID → viewer-by-ID → frame + input + busy + bad password (uses `127.0.0.1` locally)
