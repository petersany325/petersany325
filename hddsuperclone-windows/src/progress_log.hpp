#pragma once

#include "sector_map.hpp"
#include "types.hpp"

#include <string>

namespace hsc {

struct ProgressLog {
    std::string path;
    std::string source;
    std::string destination;
    std::string model;
    std::string serial;
    CloneSettings settings;
    uint64_t current_lba = 0;
    uint64_t current_status = kPhase1;
    uint64_t total_sectors = 0;
    int retries_remaining = 0;
    SectorMap map;

    bool load(const std::string& file, std::string& error);
    bool save(const std::string& file, std::string& error) const;
    bool save_ddrescue(const std::string& file, std::string& error) const;
};

}  // namespace hsc
