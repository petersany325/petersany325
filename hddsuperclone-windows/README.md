# HDDSuperClone for Windows

Native Windows port of [HDDSuperClone](https://github.com/thesourcerer8/hddsuperclone) (Scott Dwyer, GPL-2). This is a **sector-level clone / recovery** tool for failing disks — not a file copy utility.

Upstream: <https://github.com/thesourcerer8/hddsuperclone> · original site: <http://www.hddsuperclone.com/>

Install with **HDDSuperClone-Windows-Setup.exe** (NSIS). It installs to `Program Files\HDDSuperClone`, adds Start Menu + desktop shortcuts, requires Administrator, and registers Add/Remove Programs.

## What this port does

- **Start scan**, then tick one or more discovered HDD/USB devices to recover (multi-select after the scan, not before)
- Three explicit jobs:
  - **Disk-to-disk** — sector clone source disk onto a destination disk (several sources become one `.img` per disk)
  - **Image onto a hard drive** — write a `.img`/`.dd` file onto the ticked HDD
  - **File recovery only** — copy files into a folder (FAT/NTFS walk + JPEG/PNG/PDF/ZIP carving), not a full-disk overwrite
- Sector-by-sector copy with the original multi-pass strategy:
  - Phase 1 forward with adaptive skip
  - Phase 2 reverse with adaptive skip
  - Phase 3 / 4 forward without skip
  - Trim, divide, scrape, optional retries
- HDDSuperClone-compatible **progress log** (resume after stop/crash; `.bak` kept)
- Optional ddrescue map export
- Administrator elevation manifest (`requireAdministrator`)
- Safety: never writes until a destination is chosen; extra typed confirmation (`OVERWRITE BOOT DISK`) before touching the Windows/system boot disk; source and destination cannot be the same device

## Recovery methods (Windows)

| Method | Windows path | Notes |
| --- | --- | --- |
| Generic | Overlapped `ReadFile` / `WriteFile` | Works on any disk or image |
| ATA pass-through | `IOCTL_ATA_PASS_THROUGH` | `READ DMA EXT` 0x25, PIO 0x24 fallback |
| SCSI pass-through | `IOCTL_SCSI_PASS_THROUGH_DIRECT` | `READ(16)` |
| Direct AHCI | `IOCTL_ATA_PASS_THROUGH_DIRECT` + DMA + `DEVICE RESET` 0x08 | Closest user-mode equivalent of Linux AHCI MMIO. A signed kernel driver is required for true HBA MMIO; see `driver/hscahci/README.md`. |
| Direct IDE | ATA PIO taskfile 0x24 | No DMA |
| USB-direct | SCSI BOT via USBSTOR; WinUSB BOT if the device is bound to WinUSB (Zadig) | Original libusb bypass of USBD is not possible while USBSTOR owns the device |
| Rebuild Assist / FPDMA | `READ FPDMA QUEUED` 0x60, NCQ log 0x10, enable log 0x15 | Error LBA splits the chunk (prefix finished, LBA marked bad) |
| Virtual disk | Windows Virtual Disk API (`CreateVirtualDisk` VHDX + attach) or sparse image | Original `hddscbd` kernel module is Linux-only |
| USB relay | dcttech HID `16C0:05DF` (`HidD_SetFeature`) | Power-cycle on read error |
| HDDSuperTool scripts | Interpreter subset + original `scripts/` | `echo`, `seti`/`sets`, `if`, `ata28cmd`/`ata48cmd`, `printbuffer`, resets, `include` |

On a dying SATA disk, **a Linux live USB of HDDSuperClone / OpenSuperClone is still stronger** when you need controller MMIO timeouts independent of StorAHCI. This Windows build is the right tool when you must run on Windows.

## Build on Windows (MSVC)

```bat
cmake -S . -B build -G "Visual Studio 17 2022" -A x64
cmake --build build --config Release
```

Run `build\Release\hddsuperclone-windows.exe` **as Administrator**.

## Cross-compile from Linux (MinGW-w64)

```bash
sudo apt install g++-mingw-w64-x86-64 cmake nsis
cmake -S . -B build-win -DCMAKE_TOOLCHAIN_FILE=cmake/mingw-w64.cmake
cmake --build build-win
makensis -DEXE_PATH=build-win/hddsuperclone-windows.exe \
         -DSRC_DIR=. \
         -DOUT_FILE=HDDSuperClone-Windows-Setup.exe \
         installer/hddsuperclone.nsi
```

The GUI is a Windows-subsystem PE64 (no leftover console window). `--cli` and `--script` attach a console. DirectX 11 is required for the GUI (Windows 7+).

## Build and run on Linux (GUI preview / tests)

```bash
sudo apt install g++ cmake libglfw3-dev libgl1-mesa-dev
cmake -S . -B build -DCMAKE_BUILD_TYPE=Release
cmake --build build -j
./build/hsc_engine_tests
./build/hddsuperclone-windows
./build/hddsuperclone-windows --cli --source src.img --dest dst.img --log clone.log --source-file --dest-file --mode generic
./build/hddsuperclone-windows --script scripts/ata_identify_device --source src.img --source-file
```

## Safety

1. Refresh the disk list first. Nothing is opened for write until Start.
2. Choose source (read-only) and destination explicitly.
3. Confirm the overwrite dialog.
4. If the destination is the boot/system disk, type `OVERWRITE BOOT DISK`.
5. Always keep a progress log on a **third** drive (not source, not dest).

## License

GPL-2, same as HDDSuperClone. See `LICENSE`. Dear ImGui is MIT (`third_party/imgui/LICENSE.txt`).
