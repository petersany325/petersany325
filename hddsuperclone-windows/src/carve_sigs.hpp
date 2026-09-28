#pragma once

#include "disk_io.hpp"

#include <atomic>
#include <cstdint>
#include <functional>
#include <string>
#include <vector>

namespace hsc {

struct CarveCatalogEntry {
    std::string ext;
    std::string category;
    std::string magic_desc;
    uint32_t max_bytes = 0;
    bool greppable = true;
    std::string skip_reason;
};

struct CarveSig {
    std::string ext;
    std::string category;
    std::vector<uint8_t> mag;
    uint16_t mag_off = 0;
    std::vector<uint8_t> mag2;
    uint16_t mag2_off = 0;
    std::vector<uint8_t> footer;
    uint32_t max_bytes = 8 * 1024 * 1024;
    std::vector<std::string> ftyp_brands;  // if set, mag is 'ftyp' at offset 4
    bool skip = false;
    std::string skip_reason;
};

// Category toggles for the Grep scan menu. `all` ignores the rest.
struct CarveFilter {
    bool all = true;
    bool photo = true;
    bool video = true;
    bool audio = true;
    bool document = true;
    bool archive = true;
    bool database = true;
    bool mail = true;
    bool executable = true;
    bool media = true;
};

struct CarveProgress {
    std::atomic<uint64_t> bytes_done{0};
    std::atomic<uint64_t> bytes_total{0};
    std::atomic<int> hits{0};
};

const std::vector<CarveCatalogEntry>& carve_catalog();
const std::vector<CarveSig>& carve_signatures();
int carve_greppable_count();
int carve_skipped_count();
bool carve_filter_allows(const CarveFilter& filter, const std::string& category);
std::string carve_filter_summary(const CarveFilter& filter);

// Parse optional carve_signatures.txt (next to the exe). Falls back to the built-in table.
void load_carve_signatures(const std::string& optional_file);

int carve_scan(DiskSession& src, const std::string& dest_dir, std::atomic<bool>* stop,
               const std::function<void(const std::string&)>& log, int& files_written, uint64_t& bytes_written,
               const CarveFilter& filter = {}, CarveProgress* progress = nullptr);

}  // namespace hsc
