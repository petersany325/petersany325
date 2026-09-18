# Company Remote Desktop

Private LAN remote-desktop for internal training PCs. A trainee or instructor runs the **viewer**; the training machine runs the **host**. The host captures the primary display, streams JPEG frames, and injects mouse/keyboard from the single connected viewer.

Windows is the primary (and only full) target. Capture uses **DXGI Desktop Duplication** with a **GDI BitBlt** fallback. Input uses **SendInput**. JPEG uses **Windows Imaging Component**. There is no proprietary RD SDK and no TLS in this MVP.

## Layout

```
company-remote-desktop/
  CMakeLists.txt
  PROTOCOL.md              Wire format (auth, frames, input)
  src/crd/                 Shared protocol, TCP, SHA-256, session
  src/host/                Host app (capture, inject, status window)
  src/viewer/              Viewer app (Win32 UI + input forward)
  src/win/                 WIC JPEG encode/decode
  tests/                   Protocol + TCP loopback tests
```

CMake targets: `host`, `viewer` (Windows), `crd_protocol`, `test_protocol`, `test_loopback`.

## Build (MSVC / CMake)

Needs Visual Studio 2019+ (or Build Tools) with the C++ desktop workload and the Windows 10/11 SDK. CMake 3.16+.

```bat
cd company-remote-desktop
cmake -S . -B build -G "Visual Studio 17 2022" -A x64
cmake --build build --config Release
```

Binaries:

```
build\Release\host.exe
build\Release\viewer.exe
```

Ninja + MSVC is also fine:

```bat
cmake -S . -B build -G Ninja -DCMAKE_BUILD_TYPE=Release
cmake --build build
```

Optional Linux cross-compile of `host.exe` / `viewer.exe` (MinGW-w64, compile-check only):

```bash
sudo apt-get install g++-mingw-w64-x86-64
cmake -S company-remote-desktop -B company-remote-desktop/build-mingw \
  -DCMAKE_TOOLCHAIN_FILE=company-remote-desktop/cmake/mingw-w64-x86_64.cmake \
  -DCRD_BUILD_TESTS=OFF
cmake --build company-remote-desktop/build-mingw
```

On Linux/macOS without a toolchain file, CMake builds `crd_protocol` and the tests only (`host` / `viewer` are `#ifdef _WIN32`).

```bash
cmake -S company-remote-desktop -B company-remote-desktop/build
cmake --build company-remote-desktop/build
ctest --test-dir company-remote-desktop/build --output-on-failure
```

## Run order

1. **Training PC (host)** — log on as the interactive desktop user (not session 0 / a service).

   ```bat
   host.exe --port 5938 --password TrainRoom1 --quality 62 --fps 15
   ```

   If `--password` is omitted, the host generates one and shows it in the status window and console.

2. **Windows Firewall** on the training PC — allow inbound TCP 5938 (or your port) from the LAN.

   ```bat
   netsh advfirewall firewall add rule name="Company Remote Desktop Host" dir=in action=allow protocol=TCP localport=5938
   ```

3. **Trainee / instructor PC (viewer)**

   ```bat
   viewer.exe --host 192.168.1.40 --port 5938 --password TrainRoom1
   ```

   Or start `viewer.exe` with no args and fill Host / Port / Password, then **Connect**.

4. Click the remote picture to focus it, then use mouse and keyboard. **Disconnect** (or close the window) ends the session cleanly.

Only **one viewer** is accepted. A second connection is rejected as busy until the first disconnects.

### Host flags

| Flag | Default | Meaning |
| --- | --- | --- |
| `--port` | `5938` | TCP listen port |
| `--bind` | `0.0.0.0` | Listen address |
| `--password` | (random 8 chars) | Shared secret |
| `--quality` | `62` | JPEG quality 1–100 |
| `--fps` | `15` | Capture / send cap |
| `--scale` | `100` | Downscale percent (e.g. `50` for slower LANs) |

### Viewer flags

| Flag | Default | Meaning |
| --- | --- | --- |
| `--host` / `--ip` | (UI) | Host IPv4 or name |
| `--port` | `5938` | Host TCP port |
| `--password` | (UI) | Shared secret |

## Auth and protocol

Challenge-response: `SHA-256(nonce || UTF-8 password)`. The password is never sent in the clear. The link is still **not encrypted** — treat this as an internal LAN tool. See [PROTOCOL.md](PROTOCOL.md) for framing, message IDs, mouse flags, and how to add H.264 or TLS later.

## Notes and limits (MVP)

- Primary monitor only. DXGI needs an interactive console session; locked screens / UAC secure desktop will stall or fall back.
- `SendInput` cannot drive a higher-integrity window than the host process. Run the host at the same (or higher) integrity as the apps you need to control. Do not run it as a Windows service.
- LAN first. TLS, IPv6, clipboard, file transfer, multi-monitor layout, mobile clients, and AD SSO are out of scope.
- JPEG over TCP is the MVP codec. H.264 is a documented follow-up (`FRAME.codec = 2`).

## Tests

```bat
ctest --test-dir build -C Release --output-on-failure
```

`test_protocol` checks SHA-256, encoding, and endianness. `test_loopback` runs a real TCP host/viewer handshake, one JPEG payload, mouse/key delivery, busy reject, and bad-password reject (no DXGI required).
