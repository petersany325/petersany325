#pragma once

#include "types.hpp"

#include <string>
#include <vector>

namespace hsc {

// Sparse LBA status map matching HDDSuperClone's lposition/lsize/lstatus tables.
class SectorMap {
public:
    void reset(uint64_t total_sectors);
    void clear();

    int find_block(uint64_t position) const;
    int find_next(int start_line, uint64_t status_type, uint64_t status_mask) const;
    int find_prev(int start_line, uint64_t status_type, uint64_t status_mask) const;

    // Returns 0 on change, 1 if already matching, <0 on error.
    int change_chunk(uint64_t position, uint64_t size, uint64_t status, uint64_t mask);

    MapStats stats() const;
    uint64_t total_sectors() const { return total_; }
    int line_count() const { return static_cast<int>(regions_.size()); }
    const std::vector<MapRegion>& regions() const { return regions_; }
    std::vector<MapRegion>& regions() { return regions_; }

    void set_regions(std::vector<MapRegion> regions, uint64_t total);

private:
    void merge_around(int index, uint64_t mask);
    std::vector<MapRegion> regions_;
    uint64_t total_ = 0;
};

}  // namespace hsc
