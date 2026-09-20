#pragma once

#include "disk_io.hpp"
#include "types.hpp"

#include <atomic>
#include <cstdint>
#include <functional>
#include <string>
#include <vector>

namespace hsc {

struct FileRecoveryStats {
    int partitions_found = 0;
    int files_written = 0;
    int files_failed = 0;
    int carved = 0;
    uint64_t bytes_written = 0;
    std::string message;
    bool ok = false;
};

using RecoveryLogFn = std::function<void(const std::string&)>;

// Recover files from a disk/image into dest_dir. Walks MBR/GPT, FAT12/16/32,
// a basic NTFS $MFT pass, then signature carving for JPEG/PNG/PDF/ZIP/DOCX.
FileRecoveryStats recover_files(DiskSession& source, const std::string& dest_dir,
                                std::atomic<bool>* stop, RecoveryLogFn log);

// Helpers used by tests.
bool looks_like_fat_boot(const uint8_t* s512);
bool looks_like_ntfs_boot(const uint8_t* s512);
int parse_mbr_partitions(const uint8_t* s512, uint64_t out_lba[4], uint64_t out_sectors[4]);

}  // namespace hsc
