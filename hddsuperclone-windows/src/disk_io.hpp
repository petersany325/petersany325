#pragma once

#include "types.hpp"

#include <cstdint>
#include <functional>
#include <memory>
#include <string>
#include <vector>

namespace hsc {

struct IoResult {
    bool ok = false;
    int sectors_transferred = 0;
    int host_error = 0;      // OS error
    uint8_t ata_status = 0;
    uint8_t ata_error = 0;
    uint8_t sense_key = 0;
    uint8_t asc = 0;
    uint8_t ascq = 0;
    bool timeout = false;
    std::string message;
};

class DiskSession {
public:
    virtual ~DiskSession() = default;
    virtual bool is_open() const = 0;
    virtual bool writable() const = 0;
    virtual uint64_t size_bytes() const = 0;
    virtual uint32_t sector_size() const = 0;
    virtual IoResult read_sectors(uint64_t lba, uint32_t count, void* buffer, IoMode mode,
                                  int timeout_ms) = 0;
    virtual IoResult write_sectors(uint64_t lba, uint32_t count, const void* buffer,
                                    int timeout_ms) = 0;
    virtual bool identify(DiskInfo& info) = 0;
    virtual std::string path() const = 0;
};

struct EnumOptions {
    bool include_files = false;
};

std::vector<DiskInfo> enumerate_disks();
std::unique_ptr<DiskSession> open_disk(const std::string& path, bool write, bool is_file);
bool path_is_boot_disk(const std::string& path);
std::string format_bytes(uint64_t bytes);
std::string last_os_error();

// Test hook: image file with injected unreadable LBAs.
std::unique_ptr<DiskSession> open_faulty_image(const std::string& path, bool write,
                                                const std::vector<uint64_t>& bad_lbas);

}  // namespace hsc
