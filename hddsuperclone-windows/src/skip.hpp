#pragma once

#include "types.hpp"

namespace hsc {

// Adaptive skip from process_skip_ccc / reset_skip_ccc in hddsuperclone.c.
class SkipController {
public:
    void configure(const CloneSettings& s);
    void reset_run();
    void on_good_stretch(uint64_t current_lba, int64_t min_skip);
    // Called after a skip jump. Returns true if skip size was reset (run too long).
    bool on_skip(uint64_t current_lba, bool reverse, bool slow);
    int64_t skip_size() const { return skip_size_; }
    int skip_count() const { return total_skip_count_; }
    int slow_skips() const { return total_slow_skips_; }
    int skip_runs() const { return total_skip_runs_; }
    int skip_resets() const { return total_skip_resets_; }
    int run_count() const { return skip_run_count_; }
    void set_size(int64_t v) { skip_size_ = v; }

private:
    int64_t min_skip_ = kDefaultSkipSize;
    int64_t original_min_skip_ = kDefaultSkipSize;
    int64_t max_skip_ = kMaxSkipSize;
    int64_t skip_size_ = kDefaultSkipSize;
    int64_t skip_history_[5] = {0, 0, 0, 0, 0};
    int64_t skip_multiplier_ = 750;
    int64_t skip_mod_ = 1750;
    int skip_count_ = 0;
    int total_skip_count_ = 0;
    int total_slow_skips_ = 0;
    int skip_run_count_ = 0;
    int skip_run_limit_ = kSkipRunCount;
    int skip_run_retard_ = kSkipRunRetard;
    int total_skip_runs_ = 0;
    int total_skip_resets_ = 0;
    int64_t last_skip_size_ = 0;
    int64_t last_skip_position_ = 0;
    int64_t last_skip_jump_ = 0;
    int64_t skip_run_start_ = 0;
    bool skipping_ = false;
    bool skip_fast_ = false;
};

}  // namespace hsc
