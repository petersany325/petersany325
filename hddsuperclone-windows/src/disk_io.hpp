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
    uint64_t ata_lba = 0;  // NCQ / ATA reported error LBA when known
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
    virtual IoResult device_reset(int /*timeout_ms*/) {
        IoResult r;
        r.message = "device reset not available on this handle";
        return r;
    }
    virtual IoResult read_log_ext(uint8_t /*log_addr*/, void* /*buf*/, uint32_t /*bytes*/, int /*timeout_ms*/) {
        IoResult r;
        r.message = "READ LOG EXT not available";
        return r;
    }
    virtual IoResult write_log_ext(uint8_t /*log_addr*/, const void* /*buf*/, uint32_t /*bytes*/, int /*timeout_ms*/) {
        IoResult r;
        r.message = "WRITE LOG EXT not available";
        return r;
    }

    struct AtaTaskfile {
        uint8_t command = 0;
        uint8_t feature = 0;
        uint16_t count = 1;
        uint64_t lba = 0;
        uint8_t device = 0x40;
        bool dma = false;
        bool data_in = true;
        bool data_out = false;
        bool ext48 = true;
    };
    virtual IoResult send_ata(const AtaTaskfile& /*tf*/, void* /*buffer*/, uint32_t /*bytes*/, int /*timeout_ms*/) {
        IoResult r;
        r.message = "ATA taskfile not available on this handle";
        return r;
    }
    virtual bool enable_rebuild_assist(std::string& error) {
        uint8_t log[512]{};
        IoResult r = read_log_ext(0x15, log, 512, 5000);
        if (!r.ok) {
            error = r.message.empty() ? "READ LOG EXT 0x15 failed" : r.message;
            return false;
        }
        log[0] |= 0x01;  // rebuild assist enabled
        r = write_log_ext(0x15, log, 512, 5000);
        if (!r.ok) {
            error = r.message.empty() ? "WRITE LOG EXT 0x15 failed" : r.message;
            return false;
        }
        return true;
    }
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
