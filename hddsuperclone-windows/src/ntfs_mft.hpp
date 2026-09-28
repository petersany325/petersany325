#pragma once

#include "disk_io.hpp"
#include "types.hpp"

#include <atomic>
#include <cstdint>
#include <functional>
#include <string>
#include <vector>

namespace hsc {

struct MftDataRun {
    int64_t lcn = 0;  // ignored when sparse
    uint64_t clusters = 0;
    bool sparse = false;
};

struct MftRecordView {
    uint64_t recno = 0;
    uint64_t parent_recno = 5;
    uint16_t sequence = 0;
    std::string name;
    std::string path;  // rebuilt from parent refs
    uint64_t data_size = 0;
    uint64_t allocated_size = 0;
    bool in_use = false;
    bool is_dir = false;
    bool deleted = false;
    bool resident = false;
    bool has_unnamed_data = false;
    bool parsed_ok = false;
    bool from_mirr = false;
    bool skipped_bad = false;
    uint32_t si_attrs = 0;
    std::vector<MftDataRun> runs;
    std::vector<uint8_t> resident_data;
};

struct NtfsVolumeInfo {
    uint64_t part_lba = 0;
    uint32_t bytes_per_sector = 512;
    uint32_t sectors_per_cluster = 8;
    uint32_t cluster_bytes = 4096;
    uint32_t record_size = 1024;
    uint64_t mft_lcn = 0;
    uint64_t mftmirr_lcn = 0;
    uint64_t total_clusters = 0;
    uint32_t records_scanned = 0;
    uint32_t records_ok = 0;
    uint32_t records_bad = 0;
    uint32_t used_mirr = 0;
    bool ok = false;
    std::string message;
    std::vector<MftRecordView> records;
};

NtfsVolumeInfo scan_ntfs_mft(DiskSession& src, uint64_t part_lba, std::atomic<bool>* stop,
                             const std::function<void(const std::string&)>& log, uint32_t max_records = 0);
NtfsVolumeInfo scan_ntfs_on_disk(DiskSession& src, std::atomic<bool>* stop,
                                 const std::function<void(const std::string&)>& log);
void rebuild_mft_paths(NtfsVolumeInfo& vol);

uint64_t copy_mft_record_data(DiskSession& src, const NtfsVolumeInfo& vol, const MftRecordView& rec,
                              const std::string& out_path, bool& failed, std::atomic<bool>* stop);

bool write_minimal_ntfs_sample(const std::string& path, std::string& err);

}  // namespace hsc
