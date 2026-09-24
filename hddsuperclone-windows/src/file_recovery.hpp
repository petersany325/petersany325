#pragma once

#include "carve_sigs.hpp"
#include "disk_io.hpp"
#include "ntfs_mft.hpp"
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

FileRecoveryStats recover_files(DiskSession& source, const std::string& dest_dir,
                                std::atomic<bool>* stop, RecoveryLogFn log);

FileRecoveryStats recover_mft_records(DiskSession& source, const NtfsVolumeInfo& vol,
                                      const std::vector<uint64_t>& recnos, const std::string& dest_dir,
                                      std::atomic<bool>* stop, RecoveryLogFn log);

FileRecoveryStats grep_scan_disk(DiskSession& source, const std::string& dest_dir, const CarveFilter& filter,
                                 std::atomic<bool>* stop, RecoveryLogFn log, CarveProgress* progress = nullptr);

bool looks_like_fat_boot(const uint8_t* s512);
bool looks_like_ntfs_boot(const uint8_t* s512);
int parse_mbr_partitions(const uint8_t* s512, uint64_t out_lba[4], uint64_t out_sectors[4]);

}  // namespace hsc
