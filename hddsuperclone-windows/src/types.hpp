// Copyright (C) 2015-2022 Scott Dwyer (original HDDSuperClone, GPL-2).
// Windows port derived from thesourcerer8/hddsuperclone (GPL-2).
// Status codes and phase values match hddsuperclone.h so progress logs
// remain compatible with HDDSuperClone / HDDSCViewer.

#pragma once

#include <cstdint>
#include <string>
#include <vector>
#include <chrono>

namespace hsc {

inline constexpr int kDefaultSectorSize = 512;
inline constexpr int kAdvancedSectorSize = 4096;
inline constexpr int kDefaultClusterSize = 128;   // sectors
inline constexpr int kDefaultBlockSize = 1;        // sectors
inline constexpr int64_t kDefaultSkipSize = 0x1000; // 4096 sectors = 2 MiB @ 512
inline constexpr int64_t kMaxSkipSize = 0x4000000; // 32 GiB @ 512
inline constexpr int kLogUpdateSeconds = 30;
inline constexpr int kDisplayUpdateMs = 250;
inline constexpr int kSkipRunCount = 5;
inline constexpr int kSkipRunCountFast = 3;
inline constexpr int kSkipRunRetard = 0x1f;
inline constexpr int kSkipRunRetardFast = 0xf;
inline constexpr int kDivideValue = 8;
inline constexpr int kDivide1Value = 4;
inline constexpr int kDivide2Value = 16;
inline constexpr int kForwardSkip = 0x80;
inline constexpr int kReverseSkip = 0xc0;

// Region status (STATUS_MASK 0xff) — identical to original.
inline constexpr uint64_t kNonTried = 0x00;
inline constexpr uint64_t kNonTrimmed = 0x10;
inline constexpr uint64_t kNonDivided = 0x20;
inline constexpr uint64_t kNonScraped = 0x30;
inline constexpr uint64_t kBad = 0x40;
inline constexpr uint64_t kBadHead = 0x80;
inline constexpr uint64_t kFinished = 0x7f;

inline constexpr uint64_t kStatusMask = 0xff;
inline constexpr uint64_t kInfoMask = 0xffffff00ull;
inline constexpr uint64_t kSkipInfoMask = 0xff00ull;
inline constexpr uint64_t kFullMask = 0xffffffffffffffffull;

// Phase / current-status values — identical to original.
inline constexpr uint64_t kPhase1 = 0x0;
inline constexpr uint64_t kPhase2 = 0x2;
inline constexpr uint64_t kPhase3 = 0x6;
inline constexpr uint64_t kPhase4 = 0x8;
inline constexpr uint64_t kTrimming = 0x10;
inline constexpr uint64_t kDividing1 = 0x20;
inline constexpr uint64_t kDividing2 = 0x22;
inline constexpr uint64_t kScraping = 0x30;
inline constexpr uint64_t kRetrying = 0x40;
inline constexpr uint64_t kFilling = 0x100;

enum class IoMode {
    Auto = 0,
    Generic,          // overlapped ReadFile / pread
    AtaPassthrough,   // IOCTL_ATA_PASS_THROUGH / SG_IO ATA-16
    ScsiPassthrough,  // SCSI READ(16)/READ(10)
    DirectAhci,       // ATA_PASS_THROUGH_DIRECT + DMA + reset-on-timeout (user-mode)
    DirectIde,        // ATA PIO taskfile (no DMA)
    UsbDirect,        // USB BOT / USB SCSI pass-through
    RebuildAssist,    // READ FPDMA QUEUED + NCQ error log
};

enum class TargetKind {
    None = 0,
    PhysicalDisk,
    ImageFile,
};

// What the user asked the job to do. Ticks are always the damaged source.
enum class JobMode {
    DiskToDisk = 0,      // clone damaged source onto dest HDD (copy disk)
    ImageOntoDrive,      // save .img/.dd of damaged disk onto Image HDD
    FileRecovery,        // recover files from damaged disk into a folder
    RestoreImageToDisk,  // write an existing .img onto a physical dest disk
};

enum class CloneResult {
    Ok = 0,
    Stopped,
    SourceError,
    DestError,
    LogError,
    InternalError,
    SafetyAbort,
};

struct DiskInfo {
    int index = -1;                 // PhysicalDriveN / Linux disk number
    std::string path;              // \\.\PhysicalDriveN or /dev/sdX or file path
    std::string display_name;
    std::string model;
    std::string serial;
    std::string bus;             // ATA, SCSI, USB, NVMe, File
    uint64_t size_bytes = 0;
    uint32_t sector_size = kDefaultSectorSize;
    uint32_t logical_sector_size = kDefaultSectorSize;
    bool is_boot_disk = false;
    bool is_system_disk = false;
    bool removable = false;
    bool readonly = false;
    bool ata_identify_ok = false;
    bool scsi_inquiry_ok = false;
};

struct CloneSettings {
    IoMode io_mode = IoMode::Auto;
    int cluster_size = kDefaultClusterSize;
    int block_size = kDefaultBlockSize;
    int sector_size = kDefaultSectorSize;
    int retries = 0;
    int64_t min_skip_sectors = kDefaultSkipSize;
    int64_t max_skip_sectors = kMaxSkipSize;
    int64_t skip_timeout_ms = 1000;
    int64_t read_timeout_ms = 2000;
    int64_t max_read_rate_bps = 0; // 0 = unlimited
    bool skip_enabled = true;
    bool skip_fast = false;
    bool reverse = false;
    bool no_phase1 = false;
    bool no_phase2 = false;
    bool no_phase3 = false;
    bool no_phase4 = false;
    bool no_trim = false;
    bool no_divide1 = false;
    bool do_divide2 = false;
    bool no_scrape = false;
    bool fill_mode = false;
    uint8_t fill_byte = 0x00;
    bool fill_mark = false;
    uint64_t input_offset_sectors = 0;
    int64_t output_offset_sectors = -1; // -1 = same as input
    int64_t read_size_sectors = -1;     // -1 = full source
    bool use_domain = false;
    std::string domain_file;
    bool export_ddrescue = false;
    std::string ddrescue_map;
    int log_update_seconds = kLogUpdateSeconds;
    bool rebuild_assist = false;
    bool virtual_disk_dest = false;
    bool relay_on_error = false;
    std::string relay_path;
    int relay_channel = 1;
};

struct MapRegion {
    uint64_t position = 0; // LBA
    uint64_t size = 0;       // sectors
    uint64_t status = kNonTried;
};

struct MapStats {
    uint64_t finished_sectors = 0;
    uint64_t nontried_sectors = 0;
    uint64_t nontrimmed_sectors = 0;
    uint64_t nondivided_sectors = 0;
    uint64_t nonscraped_sectors = 0;
    uint64_t bad_sectors = 0;
    uint64_t other_sectors = 0;
    uint64_t total_sectors = 0;
    int finished_count = 0;
    int nontried_count = 0;
    int nontrimmed_count = 0;
    int nondivided_count = 0;
    int nonscraped_count = 0;
    int bad_count = 0;
    int other_count = 0;
};

struct CloneProgress {
    uint64_t current_lba = 0;
    uint64_t current_status = kPhase1;
    std::string phase_name;
    double rate_bps = 0;
    double avg_rate_bps = 0;
    int64_t elapsed_ms = 0;
    int skip_count = 0;
    int slow_skips = 0;
    int skip_runs = 0;
    int skip_resets = 0;
    int64_t skip_size = 0;
    MapStats stats;
    std::string last_error;
    bool running = false;
    bool paused = false;
    bool finished = false;
};

inline const char* phase_name(uint64_t status) {
    switch (status) {
        case kPhase1: return "Phase 1 (forward, skip)";
        case kPhase2: return "Phase 2 (reverse, skip)";
        case kPhase3: return "Phase 3 (forward, no skip)";
        case kPhase4: return "Phase 4 (forward, no skip)";
        case kTrimming: return "Trimming";
        case kDividing1: return "Dividing 1";
        case kDividing2: return "Dividing 2";
        case kScraping: return "Scraping";
        case kRetrying: return "Retrying";
        case kFinished: return "Finished";
        default: return "Unknown";
    }
}

inline const char* io_mode_name(IoMode m) {
    switch (m) {
        case IoMode::Auto: return "Auto-detect";
        case IoMode::Generic: return "Generic (block I/O)";
        case IoMode::AtaPassthrough: return "ATA pass-through";
        case IoMode::ScsiPassthrough: return "SCSI pass-through";
        case IoMode::DirectAhci: return "Direct AHCI (pass-through DIRECT + reset)";
        case IoMode::DirectIde: return "Direct IDE (ATA PIO)";
        case IoMode::UsbDirect: return "USB-direct (BOT / USB SCSI)";
        case IoMode::RebuildAssist: return "Rebuild Assist / FPDMA";
        default: return "Unknown";
    }
}

inline constexpr int kIoModeCount = 8;

inline const char* job_mode_name(JobMode m) {
    switch (m) {
        case JobMode::DiskToDisk:
            return "Disk-to-disk (clone damaged source onto dest HDD)";
        case JobMode::ImageOntoDrive:
            return "Image onto Image HDD (save .img of damaged disk)";
        case JobMode::FileRecovery:
            return "File recovery only (recover files, not a full sector clone)";
        case JobMode::RestoreImageToDisk:
            return "Restore image to dest disk (overwrite dest with an .img file)";
        default:
            return "Unknown";
    }
}

}  // namespace hsc
