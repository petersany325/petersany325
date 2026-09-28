#pragma once

#include "disk_io.hpp"
#include "progress_log.hpp"
#include "safety.hpp"
#include "skip.hpp"
#include "types.hpp"

#include <atomic>
#include <chrono>
#include <memory>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

namespace hsc {

class CloneEngine {
public:
    bool prepare(const std::string& source, bool source_is_file, const std::string& dest,
                 bool dest_is_file, const std::string& log_path, const CloneSettings& settings,
                 const std::string& confirm_token, std::string& error,
                 const std::vector<uint64_t>& injected_bad = {});

    CloneResult run();
    void request_stop();
    void set_paused(bool paused);

    CloneProgress progress() const;
    std::vector<std::string> log_snapshot() const;
    const ProgressLog& log() const { return log_; }
    SectorMap& map() { return log_.map; }
    const SectorMap& map() const { return log_.map; }

private:
    CloneResult copy_pass(uint64_t want_status, uint64_t status_mask, uint64_t fail_status,
                          bool forward, bool do_skip, int cluster);
    bool read_write_chunk(uint64_t map_pos, int sectors, bool skip_on_error, uint64_t fail_status,
                           CloneResult& result);
    void maybe_flush_log(bool force);
    void log_line(const std::string& s);
    void update_runtime(std::chrono::steady_clock::time_point start, uint64_t start_finished);
    IoMode resolved_mode() const;
    uint64_t block_align(uint64_t lba) const;

    CloneSettings settings_;
    ProgressLog log_;
    SkipController skip_;
    std::unique_ptr<DiskSession> source_;
    std::unique_ptr<DiskSession> dest_;
    std::string source_path_;
    std::string dest_path_;
    uint64_t start_lba_ = 0;
    uint64_t end_lba_ = 0;
    std::vector<uint8_t> buffer_;
    std::atomic<bool> stop_{false};
    std::atomic<bool> paused_{false};
    std::atomic<bool> running_{false};
    bool finished_ = false;
    std::chrono::steady_clock::time_point last_log_write_{};
    uint64_t bytes_this_second_ = 0;
    uint64_t copied_bytes_ = 0;
    mutable std::mutex mu_;
    CloneProgress progress_;
    std::vector<std::string> log_lines_;
};

}  // namespace hsc
