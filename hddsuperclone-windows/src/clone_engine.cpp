#include "clone_engine.hpp"

#include <algorithm>
#include <chrono>
#include <cstring>
#include <fstream>

namespace hsc {

void CloneEngine::log_line(const std::string& s) {
    std::lock_guard<std::mutex> g(mu_);
    log_lines_.push_back(s);
    if (log_lines_.size() > 500) log_lines_.erase(log_lines_.begin(), log_lines_.begin() + 100);
}

CloneProgress CloneEngine::progress() const {
    std::lock_guard<std::mutex> g(mu_);
    CloneProgress p = progress_;
    p.stats = log_.map.stats();
    p.current_lba = log_.current_lba;
    p.current_status = log_.current_status;
    p.phase_name = phase_name(log_.current_status);
    p.skip_count = skip_.skip_count();
    p.slow_skips = skip_.slow_skips();
    p.skip_runs = skip_.skip_runs();
    p.skip_resets = skip_.skip_resets();
    p.skip_size = skip_.skip_size();
    p.running = running_;
    p.paused = paused_.load();
    p.finished = finished_;
    return p;
}

std::vector<std::string> CloneEngine::log_snapshot() const {
    std::lock_guard<std::mutex> g(mu_);
    return log_lines_;
}

void CloneEngine::request_stop() { stop_ = true; }
void CloneEngine::set_paused(bool p) { paused_ = p; }

bool CloneEngine::prepare(const std::string& source, bool source_is_file,
                          const std::string& dest, bool dest_is_file,
                          const std::string& log_path, const CloneSettings& settings,
                          const std::string& confirm_token, std::string& error,
                          const std::vector<uint64_t>& injected_bad) {
    settings_ = settings;
    source_path_ = source;
    dest_path_ = dest;

    SafetyRequest req;
    req.source_path = source;
    req.dest_path = dest;
    req.source_is_file = source_is_file;
    req.dest_is_file = dest_is_file;
    req.dest_is_boot_disk = !dest_is_file && path_is_boot_disk(dest);
    req.typed_confirmation = confirm_token;
    auto chk = check_clone_safety(req);
    if (!chk.ok) {
        error = chk.message;
        return false;
    }

    if (!injected_bad.empty() && source_is_file) {
        source_ = open_faulty_image(source, false, injected_bad);
    } else {
        source_ = open_disk(source, false, source_is_file);
    }
    if (!source_ || !source_->is_open()) {
        error = "Cannot open source: " + (source.empty() ? std::string("(empty)") : last_os_error());
        return false;
    }
    dest_ = open_disk(dest, true, dest_is_file);
    if (!dest_ || !dest_->is_open()) {
        error = "Cannot open destination for write: " + last_os_error();
        source_.reset();
        return false;
    }

    DiskInfo info;
    source_->identify(info);
    uint32_t ss = source_->sector_size() ? source_->sector_size() : settings_.sector_size;
    settings_.sector_size = static_cast<int>(ss);
    uint64_t src_sectors = source_->size_bytes() / ss;
    if (src_sectors == 0) {
        error = "Source reports zero size";
        return false;
    }

    start_lba_ = settings_.input_offset_sectors;
    if (settings_.read_size_sectors > 0) {
        end_lba_ = start_lba_ + static_cast<uint64_t>(settings_.read_size_sectors);
        if (end_lba_ > src_sectors) end_lba_ = src_sectors;
    } else {
        end_lba_ = src_sectors;
    }

    log_.path = log_path;
    log_.source = source;
    log_.destination = dest;
    log_.model = info.model;
    log_.serial = info.serial;
    log_.settings = settings_;
    log_.total_sectors = end_lba_ - start_lba_;

    bool resumed = false;
    if (!log_path.empty()) {
        std::ifstream exists(log_path);
        if (exists) {
            exists.close();
            std::string err;
            if (log_.load(log_path, err)) {
                resumed = true;
                log_line("Resumed from log " + log_path);
            } else {
                log_line("Could not load existing log, starting new: " + err);
            }
        }
    }
    if (!resumed) {
        log_.map.reset(end_lba_ - start_lba_);
        log_.current_lba = 0;
        log_.current_status = settings_.reverse ? kPhase2 : kPhase1;
        log_.retries_remaining = settings_.retries;
    }

    skip_.configure(settings_);
    buffer_.assign(static_cast<size_t>(settings_.cluster_size) * ss, 0);
    finished_ = false;
    stop_ = false;
    paused_ = false;
    {
        std::lock_guard<std::mutex> g(mu_);
        progress_ = {};
        progress_.running = false;
    }
    log_line("Source: " + source + "  " + format_bytes(source_->size_bytes()));
    log_line("Destination: " + dest);
    log_line(std::string("Mode: ") + io_mode_name(settings_.io_mode) +
             "  cluster=" + std::to_string(settings_.cluster_size) +
             "  skip=" + std::to_string(settings_.min_skip_sectors) + " sectors");
    return true;
}

IoMode CloneEngine::resolved_mode() const {
    if (settings_.io_mode != IoMode::Auto) return settings_.io_mode;
    DiskInfo info;
    if (source_) {
        source_->identify(info);
        if (info.ata_identify_ok) return IoMode::AtaPassthrough;
        if (info.scsi_inquiry_ok) return IoMode::ScsiPassthrough;
    }
    return IoMode::Generic;
}

uint64_t CloneEngine::block_align(uint64_t lba) const {
    int bs = std::max(1, settings_.block_size);
    return (lba / bs) * bs;
}

bool CloneEngine::read_write_chunk(uint64_t map_pos, int sectors, bool skip_on_error,
                                   uint64_t fail_status, CloneResult& result) {
    while (paused_.load() && !stop_.load()) {
        std::this_thread::sleep_for(std::chrono::milliseconds(50));
    }
    if (stop_) {
        result = CloneResult::Stopped;
        return false;
    }
    uint64_t src_lba = start_lba_ + map_pos;
    uint64_t dest_lba = (settings_.output_offset_sectors >= 0)
                             ? static_cast<uint64_t>(settings_.output_offset_sectors) + map_pos
                             : src_lba;
    uint32_t bytes = static_cast<uint32_t>(sectors) * static_cast<uint32_t>(settings_.sector_size);
    if (buffer_.size() < bytes) buffer_.resize(bytes);

    auto t0 = std::chrono::steady_clock::now();
    IoResult rr = source_->read_sectors(src_lba, static_cast<uint32_t>(sectors), buffer_.data(),
                                         resolved_mode(), static_cast<int>(settings_.read_timeout_ms));
    auto t1 = std::chrono::steady_clock::now();
    int64_t ms = std::chrono::duration_cast<std::chrono::milliseconds>(t1 - t0).count();
    bool slow = settings_.skip_timeout_ms > 0 && ms >= settings_.skip_timeout_ms;

    bytes_this_second_ += rr.ok ? bytes : 0;
    copied_bytes_ += rr.ok ? bytes : 0;

    if (rr.ok) {
        IoResult wr = dest_->write_sectors(dest_lba, static_cast<uint32_t>(sectors), buffer_.data(),
                                            static_cast<int>(settings_.read_timeout_ms));
        if (!wr.ok) {
            result = CloneResult::DestError;
            log_line("Write error at LBA " + std::to_string(dest_lba) + ": " + wr.message);
            std::lock_guard<std::mutex> g(mu_);
            progress_.last_error = wr.message;
            return false;
        }
        log_.map.change_chunk(map_pos, static_cast<uint64_t>(sectors), kFinished, kStatusMask);
        if (skip_on_error && slow && settings_.skip_enabled) {
            skip_.on_skip(map_pos, false, true);
        }
        return true;
    }

    log_.map.change_chunk(map_pos, static_cast<uint64_t>(sectors), fail_status, kStatusMask);
    if (skip_on_error && settings_.skip_enabled) {
        skip_.on_skip(map_pos, false, slow || rr.timeout);
    }
    return true;
}

void CloneEngine::maybe_flush_log(bool force) {
    auto now = std::chrono::steady_clock::now();
    if (!force && now - last_log_write_ < std::chrono::seconds(settings_.log_update_seconds)) return;
    last_log_write_ = now;
    if (log_.path.empty()) return;
    std::string err;
    if (!log_.save(log_.path, err)) {
        log_line("Log save failed: " + err);
    }
}

CloneResult CloneEngine::copy_pass(uint64_t want_status, uint64_t status_mask,
                                    uint64_t fail_status, bool forward, bool do_skip,
                                    int cluster) {
    CloneResult result = CloneResult::Ok;
        skip_.reset_run();
        skip_.set_size(settings_.min_skip_sectors);
    uint64_t pos = log_.current_lba;
    if (forward) {
        if (pos < start_lba_ - start_lba_) pos = 0;
    } else {
        if (pos >= log_.map.total_sectors()) pos = log_.map.total_sectors() ? log_.map.total_sectors() - 1 : 0;
    }

    auto t_start = std::chrono::steady_clock::now();
    uint64_t start_finished = log_.map.stats().finished_sectors;

    while (!stop_) {
        while (paused_.load() && !stop_.load()) {
            std::this_thread::sleep_for(std::chrono::milliseconds(40));
        }
        if (stop_) return CloneResult::Stopped;

        int line = log_.map.find_block(pos);
        if (line < 0) break;
        const auto& r = log_.map.regions()[static_cast<size_t>(line)];
        if ((r.status & status_mask) != want_status) {
            if (forward) {
                int n = log_.map.find_next(line, want_status, status_mask);
                if (n < 0) break;
                pos = log_.map.regions()[static_cast<size_t>(n)].position;
            } else {
                int n = log_.map.find_prev(line, want_status, status_mask);
                if (n < 0) break;
                const auto& pr = log_.map.regions()[static_cast<size_t>(n)];
                pos = pr.position + pr.size - 1;
            }
        }

        if (forward && pos >= log_.map.total_sectors()) break;
        if (!forward && pos >= log_.map.total_sectors()) break;

        line = log_.map.find_block(pos);
        if (line < 0) break;
        const auto& blk = log_.map.regions()[static_cast<size_t>(line)];
        int64_t rsize = cluster;
        if (forward) {
            int64_t avail = static_cast<int64_t>(blk.position + blk.size - pos);
            if (rsize > avail) rsize = avail;
            int64_t remain = static_cast<int64_t>(log_.map.total_sectors() - pos);
            if (rsize > remain) rsize = remain;
        } else {
            int64_t avail = static_cast<int64_t>(pos - blk.position + 1);
            if (rsize > avail) rsize = avail;
            uint64_t start = pos + 1 - static_cast<uint64_t>(rsize);
            pos = start;
        }
        if (rsize <= 0) break;

        skip_.on_good_stretch(pos, settings_.min_skip_sectors);

        uint64_t before = pos;
        if (!read_write_chunk(pos, static_cast<int>(rsize), do_skip, fail_status, result)) {
            return result;
        }

        if (do_skip && settings_.skip_enabled) {
            int line2 = log_.map.find_block(before);
            if (line2 >= 0) {
                uint64_t st = log_.map.regions()[static_cast<size_t>(line2)].status & kStatusMask;
                if (st != kFinished) {
                    int64_t jump = skip_.skip_size();
                    if (forward) {
                        pos = before + static_cast<uint64_t>(jump);
                    } else {
                        pos = (before > static_cast<uint64_t>(jump))
                                   ? before - static_cast<uint64_t>(jump)
                                   : 0;
                    }
                    pos = block_align(pos);
                    log_.current_lba = pos;
                    maybe_flush_log(false);
                    update_runtime(t_start, start_finished);
                    continue;
                }
            }
        }

        if (forward) pos = before + static_cast<uint64_t>(rsize);
        else {
            if (before == 0) break;
            pos = before - 1;
        }
        log_.current_lba = pos;
        maybe_flush_log(false);
        update_runtime(t_start, start_finished);
    }
    return stop_ ? CloneResult::Stopped : CloneResult::Ok;
}

void CloneEngine::update_runtime(std::chrono::steady_clock::time_point start,
                                  uint64_t start_finished) {
    auto now = std::chrono::steady_clock::now();
    auto elapsed = std::chrono::duration_cast<std::chrono::milliseconds>(now - start);
    auto stats = log_.map.stats();
    double secs = elapsed.count() / 1000.0;
    double copied = static_cast<double>(stats.finished_sectors - start_finished) *
                    settings_.sector_size;
    std::lock_guard<std::mutex> g(mu_);
    progress_.elapsed_ms = elapsed.count();
    progress_.avg_rate_bps = secs > 0 ? copied / secs : 0;
    progress_.rate_bps = progress_.avg_rate_bps;
    progress_.stats = stats;
    progress_.current_lba = log_.current_lba;
    progress_.current_status = log_.current_status;
    progress_.phase_name = phase_name(log_.current_status);
    progress_.running = true;
}

CloneResult CloneEngine::run() {
    running_ = true;
    finished_ = false;
    auto overall = std::chrono::steady_clock::now();
    CloneResult ret = CloneResult::Ok;
    int cluster = std::max(1, settings_.cluster_size);
    int block = std::max(1, settings_.block_size);
    uint64_t mask = kStatusMask;

    auto run_phase = [&](uint64_t status, const char* name, auto fn) {
        if (ret != CloneResult::Ok) return;
        log_.current_status = status;
        log_line(std::string("=== ") + name + " ===");
        ret = fn();
        maybe_flush_log(true);
    };

    if (!settings_.no_phase1 && log_.current_status <= kPhase1) {
        run_phase(kPhase1, "Phase 1 forward skip", [&] {
            return copy_pass(kNonTried, mask, kNonTrimmed, true, true, cluster);
        });
        if (ret == CloneResult::Ok) log_.current_status = kPhase2;
    }
    if (!settings_.no_phase2 && ret == CloneResult::Ok && log_.current_status <= kPhase2) {
        log_.current_lba = log_.map.total_sectors() ? log_.map.total_sectors() - 1 : 0;
        run_phase(kPhase2, "Phase 2 reverse skip", [&] {
            return copy_pass(kNonTried, mask, kNonTrimmed, false, true, cluster);
        });
        if (ret == CloneResult::Ok) log_.current_status = kPhase3;
    }
    if (!settings_.no_phase3 && ret == CloneResult::Ok && log_.current_status <= kPhase3) {
        log_.current_lba = 0;
        run_phase(kPhase3, "Phase 3 forward no-skip", [&] {
            return copy_pass(kNonTried, mask, kNonTrimmed, true, false, cluster);
        });
        if (ret == CloneResult::Ok) log_.current_status = kPhase4;
    }
    if (!settings_.no_phase4 && ret == CloneResult::Ok && log_.current_status <= kPhase4) {
        log_.current_lba = 0;
        run_phase(kPhase4, "Phase 4 forward no-skip", [&] {
            return copy_pass(kNonTried, mask, kNonTrimmed, true, false, cluster);
        });
        if (ret == CloneResult::Ok) log_.current_status = kTrimming;
    }
    if (!settings_.no_trim && ret == CloneResult::Ok && log_.current_status <= kTrimming) {
        log_.current_lba = 0;
        run_phase(kTrimming, "Trimming", [&] {
            return copy_pass(kNonTrimmed, mask, kNonScraped, true, false, block);
        });
        if (ret == CloneResult::Ok) log_.current_status = kDividing1;
    }
    if (!settings_.no_divide1 && ret == CloneResult::Ok && log_.current_status <= kDividing1) {
        int div = cluster / (settings_.do_divide2 ? kDivide1Value : kDivideValue);
        if (div < block) div = block;
        log_.current_lba = 0;
        run_phase(kDividing1, "Dividing 1", [&] {
            return copy_pass(kNonTrimmed, mask,
                             settings_.do_divide2 ? kNonDivided : kNonScraped, true, false, div);
        });
        if (ret == CloneResult::Ok) {
            log_.current_status = settings_.do_divide2 ? kDividing2 : kScraping;
        }
    }
    if (settings_.do_divide2 && ret == CloneResult::Ok && log_.current_status <= kDividing2) {
        int div = cluster / kDivide2Value;
        if (div < block) div = block;
        log_.current_lba = 0;
        run_phase(kDividing2, "Dividing 2", [&] {
            return copy_pass(kNonDivided, mask, kNonScraped, true, false, div);
        });
        if (ret == CloneResult::Ok) log_.current_status = kScraping;
    }
    if (!settings_.no_scrape && ret == CloneResult::Ok && log_.current_status <= kScraping) {
        log_.current_lba = 0;
        run_phase(kScraping, "Scraping", [&] {
            return copy_pass(kNonScraped, mask, kBad, true, false, block);
        });
        if (ret == CloneResult::Ok) log_.current_status = kRetrying;
    }
    int retries = log_.retries_remaining > 0 ? log_.retries_remaining : settings_.retries;
    while (retries > 0 && ret == CloneResult::Ok) {
        log_.current_lba = 0;
        run_phase(kRetrying, "Retrying", [&] {
            return copy_pass(kBad, mask, kBad, true, false, block);
        });
        --retries;
        log_.retries_remaining = retries;
    }

    if (ret == CloneResult::Ok) {
        log_.current_status = kFinished;
        log_line("Rescue finished");
        finished_ = true;
    } else if (ret == CloneResult::Stopped) {
        log_line("Stopped by user — progress log saved, resume from the same log");
    }
    maybe_flush_log(true);
    if (settings_.export_ddrescue && !settings_.ddrescue_map.empty()) {
        std::string err;
        log_.save_ddrescue(settings_.ddrescue_map, err);
    }
    running_ = false;
    {
        std::lock_guard<std::mutex> g(mu_);
        progress_.running = false;
        progress_.finished = finished_;
        progress_.elapsed_ms =
            std::chrono::duration_cast<std::chrono::milliseconds>(std::chrono::steady_clock::now() - overall)
                .count();
        progress_.stats = log_.map.stats();
        progress_.current_status = log_.current_status;
        progress_.phase_name = phase_name(log_.current_status);
    }
    source_.reset();
    dest_.reset();
    return ret;
}

}  // namespace hsc
