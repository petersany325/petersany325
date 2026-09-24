#pragma once

#include "disk_io.hpp"

#include <memory>
#include <string>
#include <vector>

namespace hsc {

// USB mass-storage Bulk-Only Transport (BOT) via WinUSB when the device is
// bound to WinUSB (Zadig). PhysicalDrive USB disks use SCSI pass-through
// (UsbDirect I/O mode) which is the working path under USBSTOR.
bool looks_like_winusb_path(const std::string& path);
std::unique_ptr<DiskSession> open_winusb_bot(const std::string& path, bool write);
std::vector<DiskInfo> enumerate_winusb_disks();

}  // namespace hsc
