#include "sector_map.hpp"

#include <algorithm>

namespace hsc {

void SectorMap::reset(uint64_t total_sectors) {
    regions_.clear();
    total_ = total_sectors;
    if (total_sectors > 0) {
        regions_.push_back({0, total_sectors, kNonTried});
    }
}

void SectorMap::clear() {
    regions_.clear();
    total_ = 0;
}

void SectorMap::set_regions(std::vector<MapRegion> regions, uint64_t total) {
    regions_ = std::move(regions);
    total_ = total;
}

int SectorMap::find_block(uint64_t position) const {
    if (regions_.empty()) return -1;
    int lo = 0;
    int hi = static_cast<int>(regions_.size()) - 1;
    while (lo <= hi) {
        int mid = (lo + hi) / 2;
        const auto& r = regions_[static_cast<size_t>(mid)];
        if (position < r.position) {
            hi = mid - 1;
        } else if (position >= r.position + r.size) {
            lo = mid + 1;
        } else {
            return mid;
        }
    }
    return -1;
}

int SectorMap::find_next(int start_line, uint64_t status_type, uint64_t status_mask) const {
    for (int i = start_line + 1; i < static_cast<int>(regions_.size()); ++i) {
        if ((regions_[static_cast<size_t>(i)].status & status_mask) == status_type) {
            return i;
        }
    }
    return -1;
}

int SectorMap::find_prev(int start_line, uint64_t status_type, uint64_t status_mask) const {
    for (int i = start_line - 1; i >= 0; --i) {
        if ((regions_[static_cast<size_t>(i)].status & status_mask) == status_type) {
            return i;
        }
    }
    return -1;
}

void SectorMap::merge_around(int index, uint64_t mask) {
    if (index < 0 || index >= static_cast<int>(regions_.size())) return;
    if (index + 1 < static_cast<int>(regions_.size())) {
        auto& a = regions_[static_cast<size_t>(index)];
        auto& b = regions_[static_cast<size_t>(index) + 1];
        if ((a.status & mask) == (b.status & mask) && a.position + a.size == b.position) {
            b.position = a.position;
            b.size += a.size;
            regions_.erase(regions_.begin() + index);
        }
    }
    if (index > 0 && index < static_cast<int>(regions_.size())) {
        auto& a = regions_[static_cast<size_t>(index) - 1];
        auto& b = regions_[static_cast<size_t>(index)];
        if ((a.status & mask) == (b.status & mask) && a.position + a.size == b.position) {
            a.size += b.size;
            regions_.erase(regions_.begin() + index);
        }
    }
}

int SectorMap::change_chunk(uint64_t position, uint64_t size, uint64_t status, uint64_t mask) {
    if (size == 0) return 1;
    int block = find_block(position);
    if (block < 0) return -1;
    auto& r = regions_[static_cast<size_t>(block)];
    if (position < r.position || position + size > r.position + r.size) return -1;

    if ((r.status & mask) == (status & mask)) return 1;

    // Preserve BAD_HEAD and skip-info bits from the existing region when the
    // incoming status does not carry them (same rule as change_chunk_status_ccc).
    if ((r.status & kBadHead) == kBadHead) {
        status |= kBadHead;
    }
    if ((r.status & kSkipInfoMask) && !(status & kSkipInfoMask)) {
        status |= (r.status & kSkipInfoMask);
    }

    if (position == r.position && size == r.size) {
        r.status = status;
        merge_around(block, mask);
        return 0;
    }

    if (position == r.position) {
        // Split prefix.
        MapRegion left{position, size, status};
        r.position += size;
        r.size -= size;
        regions_.insert(regions_.begin() + block, left);
        merge_around(block, mask);
        return 0;
    }

    if (position + size == r.position + r.size) {
        r.size = position - r.position;
        MapRegion right{position, size, status};
        regions_.insert(regions_.begin() + block + 1, right);
        merge_around(block + 1, mask);
        return 0;
    }

    // Split into three.
    uint64_t orig_end = r.position + r.size;
    uint64_t orig_status = r.status;
    r.size = position - r.position;
    MapRegion mid{position, size, status};
    MapRegion right{position + size, orig_end - (position + size), orig_status};
    regions_.insert(regions_.begin() + block + 1, mid);
    regions_.insert(regions_.begin() + block + 2, right);
    merge_around(block + 1, mask);
    return 0;
}

MapStats SectorMap::stats() const {
    MapStats s;
    s.total_sectors = total_;
    for (const auto& r : regions_) {
        uint64_t st = r.status & kStatusMask;
        auto add = [&](uint64_t& bytes, int& count) {
            bytes += r.size;
            ++count;
        };
        if (st == kFinished) add(s.finished_sectors, s.finished_count);
        else if (st == kNonTried) add(s.nontried_sectors, s.nontried_count);
        else if (st == kNonTrimmed) add(s.nontrimmed_sectors, s.nontrimmed_count);
        else if (st == kNonDivided) add(s.nondivided_sectors, s.nondivided_count);
        else if (st == kNonScraped) add(s.nonscraped_sectors, s.nonscraped_count);
        else if (st == kBad || st == kBadHead) add(s.bad_sectors, s.bad_count);
        else add(s.other_sectors, s.other_count);
    }
    return s;
}

}  // namespace hsc
