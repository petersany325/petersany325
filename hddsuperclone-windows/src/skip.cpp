#include "skip.hpp"

namespace hsc {

void SkipController::configure(const CloneSettings& s) {
    original_min_skip_ = s.min_skip_sectors > 0 ? s.min_skip_sectors : kDefaultSkipSize;
    min_skip_ = original_min_skip_;
    max_skip_ = s.max_skip_sectors > 0 ? s.max_skip_sectors : kMaxSkipSize;
    skip_size_ = min_skip_;
    skip_fast_ = s.skip_fast;
    skip_run_limit_ = skip_fast_ ? kSkipRunCountFast : kSkipRunCount;
    skip_run_retard_ = skip_fast_ ? kSkipRunRetardFast : kSkipRunRetard;
    skip_multiplier_ = skip_fast_ ? 925 : 750;
    skip_mod_ = skip_multiplier_ + 1000;
    skip_count_ = 0;
    total_skip_count_ = 0;
    total_slow_skips_ = 0;
    skip_run_count_ = 0;
    total_skip_runs_ = 0;
    total_skip_resets_ = 0;
    skipping_ = false;
    for (int i = 0; i < 5; ++i) skip_history_[i] = 0;
}

void SkipController::reset_run() {
    skip_run_limit_ = skip_fast_ ? kSkipRunCountFast : kSkipRunCount;
    if (skipping_) {
        skipping_ = false;
        skip_history_[4] = skip_history_[3];
        skip_history_[3] = skip_history_[2];
        skip_history_[2] = skip_history_[1];
        skip_history_[1] = skip_history_[0];
        skip_history_[0] = last_skip_size_;
    }
    if (skip_run_count_ >= skip_run_limit_) {
        ++total_skip_runs_;
    }
    if (skip_fast_) {
        if (skip_run_count_ < 4 && skip_run_count_ > 2) {
            min_skip_ = (min_skip_ * 990) / 1000;
        }
    } else {
        if (skip_run_count_ < 8 && skip_run_count_ > 4) {
            min_skip_ = (min_skip_ * 990) / 1000;
        }
    }
    if (min_skip_ < original_min_skip_) min_skip_ = original_min_skip_;
    skip_run_count_ = 0;
    skip_multiplier_ = skip_fast_ ? 925 : 750;
    skip_mod_ = skip_multiplier_ + 1000;
    skip_size_ = min_skip_;
}

void SkipController::on_good_stretch(uint64_t current_lba, int64_t min_skip) {
    if (static_cast<int64_t>(current_lba) > last_skip_position_ + min_skip) {
        reset_run();
    }
}

bool SkipController::on_skip(uint64_t current_lba, bool reverse, bool slow) {
    (void)reverse;
    if (skip_run_count_ == 0) {
        skip_run_start_ = static_cast<int64_t>(current_lba) - skip_size_;
    }
    ++skip_run_count_;
    if (skip_run_count_ > 0x3f) skip_run_count_ = 0x3f;
    if (slow) ++total_slow_skips_;
    ++total_skip_count_;
    ++skip_count_;
    if (skip_count_ > skip_run_retard_) {
        skip_count_ = 0;
        min_skip_ = (min_skip_ * 950) / 1000;
        if (min_skip_ < original_min_skip_) min_skip_ = original_min_skip_;
    }
    last_skip_position_ = static_cast<int64_t>(current_lba);
    last_skip_size_ = skip_size_;
    last_skip_jump_ = static_cast<int64_t>(current_lba);
    skipping_ = true;

    int64_t new_skip = skip_size_;
    if ((skip_history_[0] + skip_history_[1]) / 2 < skip_size_) {
        new_skip = (skip_size_ + skip_history_[0] + skip_history_[1]) / 3;
    }
    if (skip_fast_) {
        if (skip_run_count_ > 10) new_skip = (new_skip * 1200) / 1000;
        else if (skip_run_count_ > 7) new_skip = (new_skip * 1100) / 1000;
        else if (skip_run_count_ < 5) new_skip = (new_skip * 990) / 1000;
        if (min_skip_ < new_skip / 5) min_skip_ = new_skip / 5;
        skip_size_ = ((skip_size_ * skip_mod_) / 1000) +
                     ((skip_size_ / 100) * (skip_run_count_ / 4) + skip_run_count_);
        skip_multiplier_ = (skip_multiplier_ * 1000) / 1925;
    } else {
        if (skip_run_count_ > 12) new_skip = (new_skip * 1200) / 1000;
        else if (skip_run_count_ > 9) new_skip = (new_skip * 1100) / 1000;
        else if (skip_run_count_ < 8) new_skip = (new_skip * 990) / 1000;
        if (min_skip_ < new_skip / 4) min_skip_ = new_skip / 4;
        skip_size_ = ((skip_size_ * skip_mod_) / 1000) +
                     ((skip_size_ / 100) * (skip_run_count_ / 4) + skip_run_count_);
        skip_multiplier_ = (skip_multiplier_ * 1000) / 1750;
    }
    skip_mod_ = skip_multiplier_ + 1000;

    bool reset = false;
    if (skip_size_ >= max_skip_) {
        skip_size_ = max_skip_;
        ++total_skip_resets_;
        reset_run();
        reset = true;
    }
    if (skip_run_count_ >= skip_run_limit_) {
        ++total_skip_runs_;
        skip_run_count_ = 0;
    }
    return reset;
}

}  // namespace hsc
