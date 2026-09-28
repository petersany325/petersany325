#pragma once

#include <cstdint>
#include <string>

namespace hsc {

// Create a sparse image (Linux) or a dynamic VHDX (Windows Virtual Disk API).
bool create_virtual_disk(const std::string& path, uint64_t size_bytes, std::string& error);
bool attach_virtual_disk(const std::string& path, std::string& mounted, std::string& error);
bool detach_virtual_disk(const std::string& path, std::string& error);

}  // namespace hsc
