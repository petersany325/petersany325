# Direct AHCI kernel driver (Windows)

The Linux original (`hscahci` / OSCDriver) maps the AHCI HBA MMIO BAR, issues DMA
commands, and can reset the controller independently of the OS storage stack.
That is the strongest recovery path on a dying SATA disk.

## Why this folder is not a signed `.sys`

64-bit Windows 10/11 will not load an unsigned kernel driver. This port cannot
ship a working production AHCI MMIO driver without:

1. A Microsoft attestation / EV-signed catalog (HLK), or
2. The user enabling Test Signing (`bcdedit /set testsigning on`) **and** a
   test-signed `.sys` built with the Windows Driver Kit.

Neither is available in this build environment. An unsigned-dev `.sys` dropped
next to the GUI would fail to load (`STATUS_INVALID_IMAGE_HASH` /
Code 52) and would not recover any sectors.

## Closest working Windows path (implemented)

The GUI **Direct AHCI** mode uses:

| Original Linux | Windows equivalent in this port |
| --- | --- |
| AHCI command list MMIO | `IOCTL_ATA_PASS_THROUGH_DIRECT` + `ATA_FLAGS_USE_DMA` (`READ DMA EXT` 0x25) |
| HBA reset / port reset | ATA `DEVICE RESET` 0x08 via pass-through, then PIO 0x24 fallback |
| StorAHCI | The in-box StorAHCI driver remains bound; we talk through it |

**Direct IDE** uses PIO `READ SECTORS EXT` 0x24 (no DMA flag).

If you have a WDK and a test-signing certificate, a future KMDF miniport that
claims the AHCI BAR (replacing StorAHCI) could be built from this folder. Until
then, a Linux live USB of HDDSuperClone remains stronger for controller-level
timeouts.

Do not disable driver signature enforcement on a production machine to load an
unsigned recovery driver.
