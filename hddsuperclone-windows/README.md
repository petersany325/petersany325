# HDDSuperClone for Windows

Native Windows port of [HDDSuperClone](https://github.com/thesourcerer8/hddsuperclone) (Scott Dwyer, GPL-2). This is a **sector-level clone / recovery** tool for failing disks — not a file copy utility.

Upstream: <https://github.com/thesourcerer8/hddsuperclone> · original site: <http://www.hddsuperclone.com/>

## What this port does

- Lists physical disks (`\\.\PhysicalDriveN` on Windows, `/dev/sd*` / NVMe on Linux)
- Source and destination pickers (disk or image file)
- Sector-by-sector copy with the original multi-pass strategy:
  - Phase 1 forward with adaptive skip
  - Phase 2 reverse with adaptive skip
  - Phase 3 / 4 forward without skip
  - Trim, divide, scrape, optional retries
- HDDSuperClone-compatible **progress log** (resume after stop/crash; `.bak` kept)
- Optional ddrescue map export
- ATA pass-through (`IOCTL_ATA_PASS_THROUGH` / SG_IO ATA-16)
- SCSI pass-through (`IOCTL_SCSI_PASS_THROUGH_DIRECT` / SG_IO READ(16))
- Generic overlapped `ReadFile` / `WriteFile` with timeouts
- Administrator elevation manifest (`requireAdministrator`)
- Safety: never writes until a destination is chosen; extra typed confirmation (`OVERWRITE BOOT DISK`) before touching the Windows/system boot disk; source and destination cannot be the same device

## What is not ported (Linux-only in the original)

These need a kernel-mode storage driver, libusb direct mode, or hardware that this user-mode port cannot provide:

| Original feature | Status |
| --- | --- |
| Direct AHCI / OSCDriver | Not ported (would need a Windows kernel driver) |
| Direct IDE / MMIO | Not ported |
| USB mass-storage direct mode (libusb, bypass USBD) | Not ported |
| Virtual disk driver (`hddscbd`) | Not ported |
| USB relay power-cycle | Not ported |
| Rebuild Assist / NCQ error log / FPDMA | Not ported |
| HDDSuperTool script engine | Not ported |
| GTK3 Glade UI | Replaced with a native ImGui GUI (Win32+DX11 / GLFW) |

On a dying SATA disk attached to an AHCI controller, **a Linux live USB of HDDSuperClone / OpenSuperClone is still the stronger tool** because Direct AHCI can timeout and reset the controller independently of Windows storage drivers. This Windows build is the right tool when you must run on Windows and can use ATA/SCSI pass-through or generic block I/O.

## Build on Windows (MSVC)

```bat
cmake -S . -B build -G "Visual Studio 17 2022" -A x64
cmake --build build --config Release
```

Run `build\Release\hddsuperclone-windows.exe` **as Administrator**.

## Build on Windows (MinGW)

```bat
cmake -S . -B build -G "Ninja" -DCMAKE_BUILD_TYPE=Release
cmake --build build
```

## Cross-compile from Linux (MinGW-w64)

```bash
sudo apt install g++-mingw-w64-x86-64 cmake
cmake -S . -B build-win -DCMAKE_TOOLCHAIN_FILE=cmake/mingw-w64.cmake
cmake --build build-win
```

The GUI needs DirectX 11 at runtime (Windows 7+ with DX11). `--cli` does not need a GPU.

## Build and run on Linux (GUI preview / tests)

```bash
sudo apt install g++ cmake libglfw3-dev libgl1-mesa-dev
cmake -S . -B build -DCMAKE_BUILD_TYPE=Release
cmake --build build -j
./build/hsc_engine_tests
./build/hddsuperclone-windows
# clone two images without the GUI:
./build/hddsuperclone-windows --cli --source src.img --dest dst.img --log clone.log --source-file --dest-file --mode generic
```

Physical disk access on Linux still requires root.

## Safety

1. Refresh the disk list first. Nothing is opened for write until Start.
2. Choose source (read-only) and destination explicitly.
3. Confirm the overwrite dialog.
4. If the destination is the boot/system disk, type `OVERWRITE BOOT DISK`.
5. Always keep a progress log on a **third** drive (not source, not dest).

## License

GPL-2, same as HDDSuperClone. See `LICENSE`. Dear ImGui is MIT (`third_party/imgui/LICENSE.txt`).
