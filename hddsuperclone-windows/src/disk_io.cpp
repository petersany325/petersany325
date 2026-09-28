#include "disk_io.hpp"
#include "usb_direct.hpp"

#include <algorithm>
#include <cctype>
#include <chrono>
#include <cstddef>
#include <cstdio>
#include <cstring>
#include <fstream>
#include <mutex>
#include <set>
#include <sstream>
#include <thread>
#include <unordered_set>

#ifdef _WIN32
#ifndef NOMINMAX
#define NOMINMAX
#endif
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#include <winioctl.h>
#include <ntddscsi.h>
#include <ntdddisk.h>
#include <setupapi.h>
#include <cfgmgr32.h>
#include <shellapi.h>
#include <shlobj.h>
#else
#include <dirent.h>
#include <errno.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <unistd.h>
#ifdef __linux__
#include <linux/fs.h>
#include <scsi/sg.h>
#include <scsi/scsi.h>
#endif
#endif

namespace hsc {
namespace {

std::string format_bytes_impl(uint64_t bytes) {
    const char* units[] = {"B", "KiB", "MiB", "GiB", "TiB", "PiB"};
    double v = static_cast<double>(bytes);
    int u = 0;
    while (v >= 1024.0 && u < 5) {
        v /= 1024.0;
        ++u;
    }
    char buf[64];
    std::snprintf(buf, sizeof(buf), (u == 0) ? "%.0f %s" : "%.2f %s", v, units[u]);
    return buf;
}

void trim_inplace(std::string& s) {
    auto notspace = [](unsigned char c) { return !std::isspace(c); };
    s.erase(s.begin(), std::find_if(s.begin(), s.end(), notspace));
    s.erase(std::find_if(s.rbegin(), s.rend(), notspace).base(), s.end());
}

std::string ata_string(const uint8_t* id, int word_off, int nchars) {
    std::string out;
    out.resize(static_cast<size_t>(nchars));
    const uint8_t* p = id + word_off * 2;
    for (int i = 0; i < nchars; i += 2) {
        out[static_cast<size_t>(i)] = static_cast<char>(p[i + 1]);
        out[static_cast<size_t>(i) + 1] = static_cast<char>(p[i]);
    }
    trim_inplace(out);
    return out;
}

uint64_t ata_lba48(const uint8_t* id) {
    // Words 100-103
    uint64_t lba = 0;
    for (int i = 0; i < 4; ++i) {
        uint16_t w = static_cast<uint16_t>(id[(100 + i) * 2] | (id[(100 + i) * 2 + 1] << 8));
        lba |= static_cast<uint64_t>(w) << (16 * i);
    }
    if (lba == 0) {
        uint32_t w60 = static_cast<uint32_t>(id[60 * 2] | (id[60 * 2 + 1] << 8) |
                                               (id[61 * 2] << 16) | (id[61 * 2 + 1] << 24));
        lba = w60;
    }
    return lba;
}

class FileSession final : public DiskSession {
public:
    FileSession(std::string path, bool write, std::unordered_set<uint64_t> bad)
        : path_(std::move(path)), write_(write), bad_(std::move(bad)) {}

    bool open() {
#ifdef _WIN32
        DWORD access = GENERIC_READ | (write_ ? GENERIC_WRITE : 0);
        DWORD share = FILE_SHARE_READ | FILE_SHARE_WRITE;
        DWORD disp = write_ ? OPEN_ALWAYS : OPEN_EXISTING;
        handle_ = CreateFileA(path_.c_str(), access, share, nullptr, disp,
                               FILE_ATTRIBUTE_NORMAL | FILE_FLAG_RANDOM_ACCESS, nullptr);
        if (handle_ == INVALID_HANDLE_VALUE) return false;
        LARGE_INTEGER sz{};
        if (GetFileSizeEx(handle_, &sz)) size_ = static_cast<uint64_t>(sz.QuadPart);
        return true;
#else
        int flags = write_ ? O_RDWR : O_RDONLY;
        if (write_) flags |= O_CREAT;
        fd_ = ::open(path_.c_str(), flags, 0644);
        if (fd_ < 0) return false;
        struct stat st {};
        if (fstat(fd_, &st) == 0) size_ = static_cast<uint64_t>(st.st_size);
        return true;
#endif
    }

    bool is_open() const override {
#ifdef _WIN32
        return handle_ != INVALID_HANDLE_VALUE;
#else
        return fd_ >= 0;
#endif
    }
    bool writable() const override { return write_; }
    uint64_t size_bytes() const override { return size_; }
    uint32_t sector_size() const override { return sector_size_; }
    std::string path() const override { return path_; }

    void set_size(uint64_t s) { size_ = s; }
    void set_sector_size(uint32_t s) { sector_size_ = s; }

    IoResult read_sectors(uint64_t lba, uint32_t count, void* buffer, IoMode, int) override {
        IoResult r;
        for (uint32_t i = 0; i < count; ++i) {
            if (bad_.count(lba + i)) {
                r.ok = false;
                r.sectors_transferred = static_cast<int>(i);
                r.host_error = 5;
                r.message = "injected bad sector";
                r.sense_key = 0x03;
                r.asc = 0x11;
                r.ata_lba = lba + i;
                return r;
            }
        }
        uint64_t off = lba * sector_size_;
        uint32_t bytes = count * sector_size_;
#ifdef _WIN32
        LARGE_INTEGER li;
        li.QuadPart = static_cast<LONGLONG>(off);
        if (!SetFilePointerEx(handle_, li, nullptr, FILE_BEGIN)) {
            r.message = last_os_error();
            return r;
        }
        DWORD got = 0;
        if (!ReadFile(handle_, buffer, bytes, &got, nullptr) || got != bytes) {
            r.sectors_transferred = static_cast<int>(got / sector_size_);
            r.message = last_os_error();
            return r;
        }
#else
        ssize_t got = pread(fd_, buffer, bytes, static_cast<off_t>(off));
        if (got != static_cast<ssize_t>(bytes)) {
            r.sectors_transferred = static_cast<int>(got > 0 ? got / sector_size_ : 0);
            r.message = std::strerror(errno);
            return r;
        }
#endif
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult write_sectors(uint64_t lba, uint32_t count, const void* buffer, int) override {
        IoResult r;
        if (!write_) {
            r.message = "destination not opened for write";
            return r;
        }
        uint64_t off = lba * sector_size_;
        uint32_t bytes = count * sector_size_;
#ifdef _WIN32
        LARGE_INTEGER li;
        li.QuadPart = static_cast<LONGLONG>(off);
        if (!SetFilePointerEx(handle_, li, nullptr, FILE_BEGIN)) {
            r.message = last_os_error();
            return r;
        }
        DWORD got = 0;
        if (!WriteFile(handle_, buffer, bytes, &got, nullptr) || got != bytes) {
            r.message = last_os_error();
            return r;
        }
        if (off + bytes > size_) size_ = off + bytes;
#else
        ssize_t got = pwrite(fd_, buffer, bytes, static_cast<off_t>(off));
        if (got != static_cast<ssize_t>(bytes)) {
            r.message = std::strerror(errno);
            return r;
        }
        if (off + bytes > size_) size_ = off + bytes;
#endif
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    bool identify(DiskInfo& info) override {
        info.path = path_;
        info.display_name = path_;
        info.model = "Image file";
        info.bus = "File";
        info.size_bytes = size_;
        info.sector_size = sector_size_;
        info.logical_sector_size = sector_size_;
        return true;
    }

    ~FileSession() override {
#ifdef _WIN32
        if (handle_ != INVALID_HANDLE_VALUE) CloseHandle(handle_);
#else
        if (fd_ >= 0) ::close(fd_);
#endif
    }

private:
    std::string path_;
    bool write_ = false;
    uint64_t size_ = 0;
    uint32_t sector_size_ = kDefaultSectorSize;
    std::unordered_set<uint64_t> bad_;
#ifdef _WIN32
    HANDLE handle_ = INVALID_HANDLE_VALUE;
#else
    int fd_ = -1;
#endif
};

#ifdef _WIN32

#ifndef ATA_FLAGS_DRDY_REQUIRED
#define ATA_FLAGS_DRDY_REQUIRED 0x01
#define ATA_FLAGS_DATA_IN 0x02
#define ATA_FLAGS_DATA_OUT 0x04
#define ATA_FLAGS_48BIT_COMMAND 0x08
#define ATA_FLAGS_USE_DMA 0x10
#endif

struct AtaPassThroughWithBuf {
    ATA_PASS_THROUGH_EX apt;
    uint8_t data[512 * 256];
};

class WinDiskSession final : public DiskSession {
public:
    WinDiskSession(std::string path, bool write) : path_(std::move(path)), write_(write) {}

    bool open() {
        DWORD access = GENERIC_READ | (write_ ? GENERIC_WRITE : 0);
        handle_ = CreateFileA(path_.c_str(), access,
                               FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr, OPEN_EXISTING,
                               FILE_FLAG_OVERLAPPED | FILE_FLAG_NO_BUFFERING, nullptr);
        if (handle_ == INVALID_HANDLE_VALUE) {
            // Retry without NO_BUFFERING for some virtual devices.
            handle_ = CreateFileA(path_.c_str(), access,
                                   FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr, OPEN_EXISTING,
                                   FILE_FLAG_OVERLAPPED, nullptr);
        }
        if (handle_ == INVALID_HANDLE_VALUE) return false;
        event_ = CreateEvent(nullptr, TRUE, FALSE, nullptr);

        DISK_GEOMETRY_EX geo{};
        DWORD br = 0;
        if (DeviceIoControl(handle_, IOCTL_DISK_GET_DRIVE_GEOMETRY_EX, nullptr, 0, &geo,
                             sizeof(geo), &br, nullptr)) {
            size_ = static_cast<uint64_t>(geo.DiskSize.QuadPart);
            sector_size_ = geo.Geometry.BytesPerSector ? geo.Geometry.BytesPerSector
                                                        : kDefaultSectorSize;
        } else {
            GET_LENGTH_INFORMATION len{};
            if (DeviceIoControl(handle_, IOCTL_DISK_GET_LENGTH_INFO, nullptr, 0, &len,
                                sizeof(len), &br, nullptr)) {
                size_ = static_cast<uint64_t>(len.Length.QuadPart);
            }
        }
        return true;
    }

    bool is_open() const override { return handle_ != INVALID_HANDLE_VALUE; }
    bool writable() const override { return write_; }
    uint64_t size_bytes() const override { return size_; }
    uint32_t sector_size() const override { return sector_size_; }
    std::string path() const override { return path_; }

    IoResult generic_rw(uint64_t lba, uint32_t count, void* buffer, bool write, int timeout_ms) {
        IoResult r;
        uint64_t off = lba * sector_size_;
        uint32_t bytes = count * sector_size_;
        OVERLAPPED ov{};
        ov.Offset = static_cast<DWORD>(off & 0xffffffffu);
        ov.OffsetHigh = static_cast<DWORD>(off >> 32);
        ov.hEvent = event_;
        ResetEvent(event_);
        BOOL ok;
        if (write) {
            ok = WriteFile(handle_, buffer, bytes, nullptr, &ov);
        } else {
            ok = ReadFile(handle_, buffer, bytes, nullptr, &ov);
        }
        DWORD err = ok ? ERROR_SUCCESS : GetLastError();
        if (!ok && err != ERROR_IO_PENDING) {
            r.host_error = static_cast<int>(err);
            r.message = last_os_error();
            return r;
        }
        DWORD wait = WaitForSingleObject(event_, timeout_ms > 0 ? static_cast<DWORD>(timeout_ms) : INFINITE);
        if (wait == WAIT_TIMEOUT) {
            CancelIo(handle_);
            r.timeout = true;
            r.message = "I/O timed out";
            return r;
        }
        DWORD got = 0;
        if (!GetOverlappedResult(handle_, &ov, &got, FALSE) || got != bytes) {
            r.host_error = static_cast<int>(GetLastError());
            r.sectors_transferred = static_cast<int>(got / sector_size_);
            r.message = last_os_error();
            return r;
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult ata_read(uint64_t lba, uint32_t count, void* buffer, int timeout_ms) {
        IoResult r;
        const uint32_t bytes = count * sector_size_;
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + bytes);
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND |
                        ATA_FLAGS_USE_DMA;
        apt->DataTransferLength = bytes;
        apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
        // READ DMA EXT 0x25; CurrentTaskFile: feat, count_lo, lba_lo, lba_mid, lba_hi, device, cmd, reserved
        uint8_t* tf = apt->CurrentTaskFile;
        uint8_t* ptf = apt->PreviousTaskFile;
        tf[0] = 0;
        tf[1] = static_cast<uint8_t>(count & 0xff);
        tf[2] = static_cast<uint8_t>(lba & 0xff);
        tf[3] = static_cast<uint8_t>((lba >> 8) & 0xff);
        tf[4] = static_cast<uint8_t>((lba >> 16) & 0xff);
        tf[5] = 0x40;  // LBA
        tf[6] = 0x25;  // READ DMA EXT
        ptf[1] = static_cast<uint8_t>((count >> 8) & 0xff);
        ptf[2] = static_cast<uint8_t>((lba >> 24) & 0xff);
        ptf[3] = static_cast<uint8_t>((lba >> 32) & 0xff);
        ptf[4] = static_cast<uint8_t>((lba >> 40) & 0xff);

        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt,
                             static_cast<DWORD>(pkt.size()), apt, static_cast<DWORD>(pkt.size()), &br,
                             nullptr)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = last_os_error();
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        r.ata_error = tf[0];
        r.ata_status = tf[6];
        if (r.ata_status & 0x01) {
            r.message = "ATA error";
            return r;
        }
        std::memcpy(buffer, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), bytes);
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult ata_read_direct(uint64_t lba, uint32_t count, void* buffer, int timeout_ms, uint8_t cmd,
                             bool dma) {
        IoResult r;
        const uint32_t bytes = count * sector_size_;
        ATA_PASS_THROUGH_DIRECT aptd{};
        aptd.Length = sizeof(aptd);
        aptd.AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND;
        if (dma) aptd.AtaFlags |= ATA_FLAGS_USE_DMA;
        aptd.DataTransferLength = bytes;
        aptd.TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        aptd.DataBuffer = buffer;
        uint8_t* tf = aptd.CurrentTaskFile;
        uint8_t* ptf = aptd.PreviousTaskFile;
        tf[1] = static_cast<uint8_t>(count & 0xff);
        tf[2] = static_cast<uint8_t>(lba & 0xff);
        tf[3] = static_cast<uint8_t>((lba >> 8) & 0xff);
        tf[4] = static_cast<uint8_t>((lba >> 16) & 0xff);
        tf[5] = 0x40;
        tf[6] = cmd;
        ptf[1] = static_cast<uint8_t>((count >> 8) & 0xff);
        ptf[2] = static_cast<uint8_t>((lba >> 24) & 0xff);
        ptf[3] = static_cast<uint8_t>((lba >> 32) & 0xff);
        ptf[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH_DIRECT, &aptd, sizeof(aptd), &aptd,
                             sizeof(aptd), &br, nullptr)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = last_os_error();
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        r.ata_error = tf[0];
        r.ata_status = tf[6];
        if (r.ata_status & 0x01) {
            r.message = "ATA DIRECT error";
            return r;
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult ata_fpdma_read(uint64_t lba, uint32_t count, void* buffer, int timeout_ms) {
        IoResult r;
        const uint32_t bytes = count * sector_size_;
        ATA_PASS_THROUGH_DIRECT aptd{};
        aptd.Length = sizeof(aptd);
        aptd.AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND |
                        ATA_FLAGS_USE_DMA;
        aptd.DataTransferLength = bytes;
        aptd.TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        aptd.DataBuffer = buffer;
        uint8_t* tf = aptd.CurrentTaskFile;
        uint8_t* ptf = aptd.PreviousTaskFile;
        // READ FPDMA QUEUED 0x60: Feature = sector count, Count bits 7:3 = NCQ tag 0
        tf[0] = static_cast<uint8_t>(count & 0xff);  // feature low = count
        tf[1] = 0;                                    // NCQ tag 0
        tf[2] = static_cast<uint8_t>(lba & 0xff);
        tf[3] = static_cast<uint8_t>((lba >> 8) & 0xff);
        tf[4] = static_cast<uint8_t>((lba >> 16) & 0xff);
        tf[5] = 0x40;
        tf[6] = 0x60;
        ptf[0] = static_cast<uint8_t>((count >> 8) & 0xff);
        ptf[2] = static_cast<uint8_t>((lba >> 24) & 0xff);
        ptf[3] = static_cast<uint8_t>((lba >> 32) & 0xff);
        ptf[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH_DIRECT, &aptd, sizeof(aptd), &aptd,
                             sizeof(aptd), &br, nullptr) ||
            (tf[6] & 0x01)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = "READ FPDMA QUEUED failed";
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult scsi_read(uint64_t lba, uint32_t count, void* buffer, int timeout_ms) {
        IoResult r;
        const uint32_t bytes = count * sector_size_;
        SCSI_PASS_THROUGH_DIRECT sptd{};
        uint8_t sense[32]{};
        sptd.Length = sizeof(SCSI_PASS_THROUGH_DIRECT);
        sptd.CdbLength = 16;
        sptd.SenseInfoLength = sizeof(sense);
        sptd.DataIn = SCSI_IOCTL_DATA_IN;
        sptd.DataTransferLength = bytes;
        sptd.TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        sptd.DataBuffer = buffer;
        sptd.SenseInfoOffset = 0;
        // READ(16)
        sptd.Cdb[0] = 0x88;
        sptd.Cdb[2] = static_cast<uint8_t>((lba >> 56) & 0xff);
        sptd.Cdb[3] = static_cast<uint8_t>((lba >> 48) & 0xff);
        sptd.Cdb[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
        sptd.Cdb[5] = static_cast<uint8_t>((lba >> 32) & 0xff);
        sptd.Cdb[6] = static_cast<uint8_t>((lba >> 24) & 0xff);
        sptd.Cdb[7] = static_cast<uint8_t>((lba >> 16) & 0xff);
        sptd.Cdb[8] = static_cast<uint8_t>((lba >> 8) & 0xff);
        sptd.Cdb[9] = static_cast<uint8_t>(lba & 0xff);
        sptd.Cdb[10] = static_cast<uint8_t>((count >> 24) & 0xff);
        sptd.Cdb[11] = static_cast<uint8_t>((count >> 16) & 0xff);
        sptd.Cdb[12] = static_cast<uint8_t>((count >> 8) & 0xff);
        sptd.Cdb[13] = static_cast<uint8_t>(count & 0xff);

        // Pass sense via a combined buffer when DIRECT sense offset is unused.
        struct {
            SCSI_PASS_THROUGH_DIRECT sptd;
            uint8_t sense[32];
        } buf{};
        buf.sptd = sptd;
        buf.sptd.SenseInfoOffset = offsetof(decltype(buf), sense);
        buf.sptd.DataBuffer = buffer;

        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_SCSI_PASS_THROUGH_DIRECT, &buf, sizeof(buf), &buf,
                             sizeof(buf), &br, nullptr)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = last_os_error();
            return r;
        }
        if (buf.sense[0] != 0) {
            r.sense_key = buf.sense[2] & 0x0f;
            r.asc = buf.sense[12];
            r.ascq = buf.sense[13];
            if (r.sense_key > 1) {
                r.message = "SCSI sense";
                return r;
            }
        }
        if (buf.sptd.ScsiStatus != 0 && r.sense_key > 1) {
            r.message = "SCSI status";
            return r;
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

    IoResult read_sectors(uint64_t lba, uint32_t count, void* buffer, IoMode mode,
                          int timeout_ms) override {
        if (mode == IoMode::AtaPassthrough) {
            IoResult r = ata_read(lba, count, buffer, timeout_ms);
            if (!r.ok && count > 1) {
                // Fallback: PIO READ SECTORS EXT 0x24 without DMA flag.
                const uint32_t bytes = count * sector_size_;
                std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + bytes);
                auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
                std::memset(pkt.data(), 0, pkt.size());
                apt->Length = sizeof(ATA_PASS_THROUGH_EX);
                apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND;
                apt->DataTransferLength = bytes;
                apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
                apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
                uint8_t* tf = apt->CurrentTaskFile;
                uint8_t* ptf = apt->PreviousTaskFile;
                tf[1] = static_cast<uint8_t>(count & 0xff);
                tf[2] = static_cast<uint8_t>(lba & 0xff);
                tf[3] = static_cast<uint8_t>((lba >> 8) & 0xff);
                tf[4] = static_cast<uint8_t>((lba >> 16) & 0xff);
                tf[5] = 0x40;
                tf[6] = 0x24;
                ptf[1] = static_cast<uint8_t>((count >> 8) & 0xff);
                ptf[2] = static_cast<uint8_t>((lba >> 24) & 0xff);
                ptf[3] = static_cast<uint8_t>((lba >> 32) & 0xff);
                ptf[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
                DWORD br = 0;
                if (DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt,
                                     static_cast<DWORD>(pkt.size()), apt, static_cast<DWORD>(pkt.size()),
                                     &br, nullptr) &&
                    !(tf[6] & 0x01)) {
                    std::memcpy(buffer, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), bytes);
                    r.ok = true;
                    r.sectors_transferred = static_cast<int>(count);
                    r.message.clear();
                    return r;
                }
            }
            return r;
        }
        if (mode == IoMode::ScsiPassthrough || mode == IoMode::UsbDirect) {
            return scsi_read(lba, count, buffer, timeout_ms);
        }
        if (mode == IoMode::DirectIde) {
            // PIO READ SECTORS EXT 0x24
            IoResult r;
            const uint32_t bytes = count * sector_size_;
            std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + bytes);
            auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
            std::memset(pkt.data(), 0, pkt.size());
            apt->Length = sizeof(ATA_PASS_THROUGH_EX);
            apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND;
            apt->DataTransferLength = bytes;
            apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
            apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
            uint8_t* tf = apt->CurrentTaskFile;
            uint8_t* ptf = apt->PreviousTaskFile;
            tf[1] = static_cast<uint8_t>(count & 0xff);
            tf[2] = static_cast<uint8_t>(lba & 0xff);
            tf[3] = static_cast<uint8_t>((lba >> 8) & 0xff);
            tf[4] = static_cast<uint8_t>((lba >> 16) & 0xff);
            tf[5] = 0x40;
            tf[6] = 0x24;
            ptf[1] = static_cast<uint8_t>((count >> 8) & 0xff);
            ptf[2] = static_cast<uint8_t>((lba >> 24) & 0xff);
            ptf[3] = static_cast<uint8_t>((lba >> 32) & 0xff);
            ptf[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
            DWORD br = 0;
            if (DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                                 static_cast<DWORD>(pkt.size()), &br, nullptr) &&
                !(tf[6] & 0x01)) {
                std::memcpy(buffer, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), bytes);
                r.ok = true;
                r.sectors_transferred = static_cast<int>(count);
                return r;
            }
            r.message = last_os_error();
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        if (mode == IoMode::DirectAhci || mode == IoMode::RebuildAssist) {
            IoResult r;
            if (mode == IoMode::RebuildAssist) {
                r = ata_fpdma_read(lba, count, buffer, timeout_ms);
                if (!r.ok) r = ata_read_direct(lba, count, buffer, timeout_ms, 0x25, true);
            } else {
                r = ata_read_direct(lba, count, buffer, timeout_ms, 0x25, true);
            }
            if (!r.ok && timeout_ms > 0) {
                (void)device_reset(timeout_ms);
            }
            if (!r.ok) {
                uint8_t log[512]{};
                if (read_log_ext(0x10, log, 512, timeout_ms).ok) {
                    uint64_t elba = 0;
                    for (int i = 0; i < 6; ++i) elba |= static_cast<uint64_t>(log[8 + i]) << (8 * i);
                    r.ata_lba = elba;
                }
            }
            if (r.ok) return r;
            if (mode == IoMode::DirectAhci) {
                return read_sectors(lba, count, buffer, IoMode::DirectIde, timeout_ms);
            }
            return r;
        }
        return generic_rw(lba, count, buffer, false, timeout_ms);
    }

    IoResult device_reset(int timeout_ms) override {
        IoResult r;
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX));
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED;
        apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        apt->CurrentTaskFile[6] = 0x08;  // DEVICE RESET
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                             static_cast<DWORD>(pkt.size()), &br, nullptr)) {
            r.message = last_os_error();
            return r;
        }
        r.ok = true;
        return r;
    }

    IoResult read_log_ext(uint8_t log_addr, void* buf, uint32_t bytes, int timeout_ms) override {
        IoResult r;
        if (bytes < 512) bytes = 512;
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + bytes);
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN | ATA_FLAGS_48BIT_COMMAND;
        apt->DataTransferLength = bytes;
        apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
        uint8_t* tf = apt->CurrentTaskFile;
        uint8_t* ptf = apt->PreviousTaskFile;
        tf[1] = 1;          // sector count
        tf[2] = log_addr;   // LBA 7:0 = log address (0x10 NCQ, 0x15 rebuild assist)
        tf[5] = 0x40;
        tf[6] = 0x2F;       // READ LOG EXT
        ptf[1] = 0;
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                             static_cast<DWORD>(pkt.size()), &br, nullptr) ||
            (tf[6] & 0x01)) {
            r.message = "READ LOG EXT failed";
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        std::memcpy(buf, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), bytes);
        r.ok = true;
        return r;
    }

    IoResult write_log_ext(uint8_t log_addr, const void* buf, uint32_t bytes, int timeout_ms) override {
        IoResult r;
        if (bytes < 512) bytes = 512;
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + bytes);
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_OUT | ATA_FLAGS_48BIT_COMMAND;
        apt->DataTransferLength = bytes;
        apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
        std::memcpy(pkt.data() + sizeof(ATA_PASS_THROUGH_EX), buf, bytes);
        uint8_t* tf = apt->CurrentTaskFile;
        uint8_t* ptf = apt->PreviousTaskFile;
        tf[1] = 1;
        tf[2] = log_addr;
        tf[5] = 0x40;
        tf[6] = 0x3F;  // WRITE LOG EXT
        ptf[1] = 0;
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                             static_cast<DWORD>(pkt.size()), &br, nullptr) ||
            (tf[6] & 0x01)) {
            r.message = "WRITE LOG EXT failed";
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        r.ok = true;
        return r;
    }

    IoResult send_ata(const AtaTaskfile& cmd, void* buffer, uint32_t bytes, int timeout_ms) override {
        IoResult r;
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + (bytes ? bytes : 0));
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED;
        if (cmd.data_in) apt->AtaFlags |= ATA_FLAGS_DATA_IN;
        if (cmd.data_out) apt->AtaFlags |= ATA_FLAGS_DATA_OUT;
        if (cmd.ext48) apt->AtaFlags |= ATA_FLAGS_48BIT_COMMAND;
        if (cmd.dma) apt->AtaFlags |= ATA_FLAGS_USE_DMA;
        apt->DataTransferLength = bytes;
        apt->TimeOutValue = static_cast<ULONG>(std::max(1, (timeout_ms + 999) / 1000));
        apt->DataBufferOffset = bytes ? sizeof(ATA_PASS_THROUGH_EX) : 0;
        if (cmd.data_out && buffer && bytes)
            std::memcpy(pkt.data() + sizeof(ATA_PASS_THROUGH_EX), buffer, bytes);
        uint8_t* tf = apt->CurrentTaskFile;
        uint8_t* ptf = apt->PreviousTaskFile;
        tf[0] = cmd.feature;
        tf[1] = static_cast<uint8_t>(cmd.count & 0xff);
        tf[2] = static_cast<uint8_t>(cmd.lba & 0xff);
        tf[3] = static_cast<uint8_t>((cmd.lba >> 8) & 0xff);
        tf[4] = static_cast<uint8_t>((cmd.lba >> 16) & 0xff);
        tf[5] = cmd.device;
        tf[6] = cmd.command;
        ptf[1] = static_cast<uint8_t>((cmd.count >> 8) & 0xff);
        ptf[2] = static_cast<uint8_t>((cmd.lba >> 24) & 0xff);
        ptf[3] = static_cast<uint8_t>((cmd.lba >> 32) & 0xff);
        ptf[4] = static_cast<uint8_t>((cmd.lba >> 40) & 0xff);
        DWORD br = 0;
        if (!DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                             static_cast<DWORD>(pkt.size()), &br, nullptr)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = last_os_error();
            r.ata_status = tf[6];
            r.ata_error = tf[0];
            return r;
        }
        r.ata_error = tf[0];
        r.ata_status = tf[6];
        if (r.ata_status & 0x01) {
            r.message = "ATA error";
            return r;
        }
        if (cmd.data_in && buffer && bytes)
            std::memcpy(buffer, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), bytes);
        r.ok = true;
        r.sectors_transferred = sector_size_ ? static_cast<int>(bytes / sector_size_) : 0;
        return r;
    }

    IoResult write_sectors(uint64_t lba, uint32_t count, const void* buffer, int timeout_ms) override {
        if (!write_) {
            IoResult r;
            r.message = "destination not opened for write";
            return r;
        }
        return generic_rw(lba, count, const_cast<void*>(buffer), true, timeout_ms);
    }

    bool identify(DiskInfo& info) override {
        info.path = path_;
        info.display_name = path_;
        info.size_bytes = size_;
        info.sector_size = sector_size_;
        info.logical_sector_size = sector_size_;

        uint8_t id[512]{};
        std::vector<uint8_t> pkt(sizeof(ATA_PASS_THROUGH_EX) + 512);
        auto* apt = reinterpret_cast<ATA_PASS_THROUGH_EX*>(pkt.data());
        std::memset(pkt.data(), 0, pkt.size());
        apt->Length = sizeof(ATA_PASS_THROUGH_EX);
        apt->AtaFlags = ATA_FLAGS_DRDY_REQUIRED | ATA_FLAGS_DATA_IN;
        apt->DataTransferLength = 512;
        apt->TimeOutValue = 10;
        apt->DataBufferOffset = sizeof(ATA_PASS_THROUGH_EX);
        apt->CurrentTaskFile[6] = 0xEC;  // IDENTIFY DEVICE
        apt->CurrentTaskFile[5] = 0xA0;
        DWORD br = 0;
        if (DeviceIoControl(handle_, IOCTL_ATA_PASS_THROUGH, apt, static_cast<DWORD>(pkt.size()), apt,
                             static_cast<DWORD>(pkt.size()), &br, nullptr)) {
            std::memcpy(id, pkt.data() + sizeof(ATA_PASS_THROUGH_EX), 512);
            info.model = ata_string(id, 27, 40);
            info.serial = ata_string(id, 10, 20);
            uint64_t lba = ata_lba48(id);
            if (lba) info.size_bytes = lba * sector_size_;
            info.ata_identify_ok = true;
            info.bus = "ATA";
            info.display_name = info.model.empty() ? path_ : info.model + " (" + path_ + ")";
            return true;
        }

        STORAGE_PROPERTY_QUERY q{};
        q.PropertyId = StorageDeviceProperty;
        q.QueryType = PropertyStandardQuery;
        std::vector<uint8_t> buf(1024);
        if (DeviceIoControl(handle_, IOCTL_STORAGE_QUERY_PROPERTY, &q, sizeof(q), buf.data(),
                             static_cast<DWORD>(buf.size()), &br, nullptr)) {
            auto* desc = reinterpret_cast<STORAGE_DEVICE_DESCRIPTOR*>(buf.data());
            auto str_at = [&](DWORD off) -> std::string {
                if (!off || off >= buf.size()) return {};
                return reinterpret_cast<char*>(buf.data() + off);
            };
            info.model = str_at(desc->ProductIdOffset);
            info.serial = str_at(desc->SerialNumberOffset);
            trim_inplace(info.model);
            trim_inplace(info.serial);
            switch (desc->BusType) {
                case BusTypeAta:
                case BusTypeSata: info.bus = "ATA"; break;
                case BusTypeScsi: info.bus = "SCSI"; break;
                case BusTypeUsb: info.bus = "USB"; break;
                case BusTypeNvme: info.bus = "NVMe"; break;
                default: info.bus = "Storage"; break;
            }
            info.scsi_inquiry_ok = true;
        }
        if (info.display_name.empty() || info.display_name == path_) {
            info.display_name = info.model.empty() ? path_ : info.model + " (" + path_ + ")";
        }
        return true;
    }

    ~WinDiskSession() override {
        if (event_) CloseHandle(event_);
        if (handle_ != INVALID_HANDLE_VALUE) CloseHandle(handle_);
    }

private:
    std::string path_;
    bool write_ = false;
    HANDLE handle_ = INVALID_HANDLE_VALUE;
    HANDLE event_ = nullptr;
    uint64_t size_ = 0;
    uint32_t sector_size_ = kDefaultSectorSize;
};

#else  // POSIX

class PosixDiskSession final : public DiskSession {
public:
    PosixDiskSession(std::string path, bool write) : path_(std::move(path)), write_(write) {}

    bool open() {
        int flags = write_ ? O_RDWR : O_RDONLY;
        fd_ = ::open(path_.c_str(), flags);
        if (fd_ < 0) {
            if (!write_) fd_ = ::open(path_.c_str(), O_RDONLY | O_NONBLOCK);
            if (fd_ < 0) return false;
        }
        struct stat st {};
        if (fstat(fd_, &st) == 0) {
            if (S_ISREG(st.st_mode)) size_ = static_cast<uint64_t>(st.st_size);
        }
#ifdef BLKGETSIZE64
        uint64_t bytes = 0;
        if (ioctl(fd_, BLKGETSIZE64, &bytes) == 0) size_ = bytes;
#endif
#ifdef BLKSSZGET
        int ss = 0;
        if (ioctl(fd_, BLKSSZGET, &ss) == 0 && ss > 0) sector_size_ = static_cast<uint32_t>(ss);
#endif
        return true;
    }

    bool is_open() const override { return fd_ >= 0; }
    bool writable() const override { return write_; }
    uint64_t size_bytes() const override { return size_; }
    uint32_t sector_size() const override { return sector_size_; }
    std::string path() const override { return path_; }

    IoResult generic_rw(uint64_t lba, uint32_t count, void* buffer, bool write, int timeout_ms) {
        (void)timeout_ms;
        IoResult r;
        uint64_t off = lba * sector_size_;
        uint32_t bytes = count * sector_size_;
        ssize_t got = write ? pwrite(fd_, buffer, bytes, static_cast<off_t>(off))
                           : pread(fd_, buffer, bytes, static_cast<off_t>(off));
        if (got != static_cast<ssize_t>(bytes)) {
            r.host_error = errno;
            r.sectors_transferred = static_cast<int>(got > 0 ? got / sector_size_ : 0);
            r.message = std::strerror(errno);
            return r;
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(count);
        return r;
    }

#ifdef __linux__
    IoResult sg_io(uint8_t* cdb, uint8_t cdb_len, void* buffer, uint32_t bytes, int dxfer,
                   int timeout_ms) {
        IoResult r;
        uint8_t sense[32]{};
        sg_io_hdr_t io {};
        io.interface_id = 'S';
        io.cmd_len = cdb_len;
        io.mx_sb_len = sizeof(sense);
        io.dxfer_direction = dxfer;
        io.dxfer_len = bytes;
        io.dxferp = buffer;
        io.cmdp = cdb;
        io.sbp = sense;
        io.timeout = timeout_ms > 0 ? timeout_ms : 2000;
        if (ioctl(fd_, SG_IO, &io) < 0) {
            r.host_error = errno;
            r.message = std::strerror(errno);
            return r;
        }
        if (io.status || (io.info & SG_INFO_CHECK)) {
            r.sense_key = sense[2] & 0x0f;
            r.asc = sense[12];
            r.ascq = sense[13];
            if (r.sense_key > 1) {
                r.message = "SCSI/ATA sense";
                return r;
            }
        }
        r.ok = true;
        r.sectors_transferred = static_cast<int>(bytes / sector_size_);
        return r;
    }

    IoResult ata_cmd(uint8_t command, uint64_t lba, uint32_t count, void* buffer, uint32_t bytes,
                     int timeout_ms, bool dma, bool data_in, bool data_out, uint8_t feature = 0) {
        uint8_t cdb[16]{};
        cdb[0] = 0x85;
        int proto = dma ? 6 : (data_in ? 4 : (data_out ? 5 : 3));
        if (command == 0x60) proto = 12;  // FPDMA
        cdb[1] = static_cast<uint8_t>((proto << 1) | 0x01);
        uint8_t t_dir = data_in ? 1 : 0;
        cdb[2] = static_cast<uint8_t>((1 << 3) | (t_dir << 2) | (bytes ? 0x2 : 0));
        cdb[3] = static_cast<uint8_t>((lba >> 40) & 0xff);
        cdb[4] = feature;
        cdb[5] = static_cast<uint8_t>((count >> 8) & 0xff);
        cdb[6] = static_cast<uint8_t>(count & 0xff);
        cdb[7] = static_cast<uint8_t>((count >> 8) & 0xff);
        cdb[8] = static_cast<uint8_t>(lba & 0xff);
        cdb[9] = static_cast<uint8_t>((lba >> 24) & 0xff);
        cdb[10] = static_cast<uint8_t>((lba >> 8) & 0xff);
        cdb[11] = static_cast<uint8_t>((lba >> 32) & 0xff);
        cdb[12] = static_cast<uint8_t>((lba >> 16) & 0xff);
        cdb[13] = 0x40;
        cdb[14] = command;
        int dx = data_out ? SG_DXFER_TO_DEV : (data_in ? SG_DXFER_FROM_DEV : SG_DXFER_NONE);
        return sg_io(cdb, 16, buffer, bytes, dx, timeout_ms);
    }

    IoResult ata_read(uint64_t lba, uint32_t count, void* buffer, int timeout_ms) {
        return ata_cmd(0x24, lba, count, buffer, count * sector_size_, timeout_ms, false, true, false);
    }

    IoResult scsi_read(uint64_t lba, uint32_t count, void* buffer, int timeout_ms) {
        uint8_t cdb[16]{};
        cdb[0] = 0x88;
        cdb[2] = static_cast<uint8_t>((lba >> 56) & 0xff);
        cdb[3] = static_cast<uint8_t>((lba >> 48) & 0xff);
        cdb[4] = static_cast<uint8_t>((lba >> 40) & 0xff);
        cdb[5] = static_cast<uint8_t>((lba >> 32) & 0xff);
        cdb[6] = static_cast<uint8_t>((lba >> 24) & 0xff);
        cdb[7] = static_cast<uint8_t>((lba >> 16) & 0xff);
        cdb[8] = static_cast<uint8_t>((lba >> 8) & 0xff);
        cdb[9] = static_cast<uint8_t>(lba & 0xff);
        cdb[10] = static_cast<uint8_t>((count >> 24) & 0xff);
        cdb[11] = static_cast<uint8_t>((count >> 16) & 0xff);
        cdb[12] = static_cast<uint8_t>((count >> 8) & 0xff);
        cdb[13] = static_cast<uint8_t>(count & 0xff);
        return sg_io(cdb, 16, buffer, count * sector_size_, SG_DXFER_FROM_DEV, timeout_ms);
    }
#endif

    IoResult read_sectors(uint64_t lba, uint32_t count, void* buffer, IoMode mode,
                          int timeout_ms) override {
#ifdef __linux__
        if (mode == IoMode::AtaPassthrough) return ata_read(lba, count, buffer, timeout_ms);
        if (mode == IoMode::ScsiPassthrough || mode == IoMode::UsbDirect)
            return scsi_read(lba, count, buffer, timeout_ms);
        if (mode == IoMode::DirectIde)
            return ata_cmd(0x24, lba, count, buffer, count * sector_size_, timeout_ms, false, true, false);
        if (mode == IoMode::DirectAhci) {
            IoResult r = ata_cmd(0x25, lba, count, buffer, count * sector_size_, timeout_ms, true, true, false);
            if (!r.ok) (void)device_reset(timeout_ms);
            if (!r.ok) r = ata_cmd(0x24, lba, count, buffer, count * sector_size_, timeout_ms, false, true, false);
            return r;
        }
        if (mode == IoMode::RebuildAssist) {
            IoResult r = ata_cmd(0x60, lba, count, buffer, count * sector_size_, timeout_ms, true, true, false);
            if (!r.ok) r = ata_cmd(0x25, lba, count, buffer, count * sector_size_, timeout_ms, true, true, false);
            if (!r.ok) {
                (void)device_reset(timeout_ms);
                uint8_t log[512]{};
                if (read_log_ext(0x10, log, 512, timeout_ms).ok) {
                    uint64_t elba = 0;
                    for (int i = 0; i < 6; ++i) elba |= static_cast<uint64_t>(log[8 + i]) << (8 * i);
                    r.ata_lba = elba;
                }
            }
            return r;
        }
#endif
        return generic_rw(lba, count, buffer, false, timeout_ms);
    }

    IoResult device_reset(int timeout_ms) override {
#ifdef __linux__
        return ata_cmd(0x08, 0, 0, nullptr, 0, timeout_ms, false, false, false);
#else
        (void)timeout_ms;
        IoResult r;
        r.message = "device reset not available";
        return r;
#endif
    }

    IoResult read_log_ext(uint8_t log_addr, void* buf, uint32_t bytes, int timeout_ms) override {
#ifdef __linux__
        if (bytes < 512) bytes = 512;
        return ata_cmd(0x2F, log_addr, 1, buf, bytes, timeout_ms, false, true, false);
#else
        (void)log_addr;
        (void)buf;
        (void)bytes;
        (void)timeout_ms;
        IoResult r;
        r.message = "READ LOG EXT not available";
        return r;
#endif
    }

    IoResult write_log_ext(uint8_t log_addr, const void* buf, uint32_t bytes, int timeout_ms) override {
#ifdef __linux__
        if (bytes < 512) bytes = 512;
        return ata_cmd(0x3F, log_addr, 1, const_cast<void*>(buf), bytes, timeout_ms, false, false, true);
#else
        (void)log_addr;
        (void)buf;
        (void)bytes;
        (void)timeout_ms;
        IoResult r;
        r.message = "WRITE LOG EXT not available";
        return r;
#endif
    }

    IoResult send_ata(const AtaTaskfile& tf, void* buffer, uint32_t bytes, int timeout_ms) override {
#ifdef __linux__
        return ata_cmd(tf.command, tf.lba, tf.count, buffer, bytes, timeout_ms, tf.dma, tf.data_in,
                       tf.data_out, tf.feature);
#else
        (void)tf;
        (void)buffer;
        (void)bytes;
        (void)timeout_ms;
        IoResult r;
        r.message = "ATA taskfile not available";
        return r;
#endif
    }

    IoResult write_sectors(uint64_t lba, uint32_t count, const void* buffer, int timeout_ms) override {
        if (!write_) {
            IoResult r;
            r.message = "destination not opened for write";
            return r;
        }
        return generic_rw(lba, count, const_cast<void*>(buffer), true, timeout_ms);
    }

    bool identify(DiskInfo& info) override {
        info.path = path_;
        info.display_name = path_;
        info.size_bytes = size_;
        info.sector_size = sector_size_;
        info.logical_sector_size = sector_size_;
#ifdef __linux__
        uint8_t id[512]{};
        uint8_t cdb[16]{};
        cdb[0] = 0x85;
        cdb[1] = (4 << 1);
        cdb[2] = (1 << 3) | (1 << 2) | 0x2;
        cdb[6] = 1;
        cdb[13] = 0xa0;
        cdb[14] = 0xec;
        IoResult ir = sg_io(cdb, 16, id, 512, SG_DXFER_FROM_DEV, 5000);
        if (ir.ok) {
            info.model = ata_string(id, 27, 40);
            info.serial = ata_string(id, 10, 20);
            info.ata_identify_ok = true;
            info.bus = "ATA";
            uint64_t lba = ata_lba48(id);
            if (lba) info.size_bytes = lba * sector_size_;
        }
        std::string model_path = "/sys/block/";
        auto slash = path_.find_last_of('/');
        std::string name = slash == std::string::npos ? path_ : path_.substr(slash + 1);
        // strip partition digits
        while (!name.empty() && std::isdigit(static_cast<unsigned char>(name.back()))) name.pop_back();
        std::ifstream mf("/sys/block/" + name + "/device/model");
        std::string model;
        if (mf && info.model.empty()) {
            std::getline(mf, model);
            trim_inplace(model);
            info.model = model;
        }
        std::ifstream sf("/sys/block/" + name + "/device/serial");
        if (sf && info.serial.empty()) {
            std::getline(sf, info.serial);
            trim_inplace(info.serial);
        }
#endif
        if (!info.model.empty()) info.display_name = info.model + " (" + path_ + ")";
        return true;
    }

    ~PosixDiskSession() override {
        if (fd_ >= 0) ::close(fd_);
    }

private:
    std::string path_;
    bool write_ = false;
    int fd_ = -1;
    uint64_t size_ = 0;
    uint32_t sector_size_ = kDefaultSectorSize;
};

#endif

bool looks_like_image_file(const std::string& path) {
#ifdef _WIN32
    if (path.rfind("\\\\.\\PhysicalDrive", 0) == 0) return false;
    if (path.rfind("\\\\.\\", 0) == 0) return false;
    return true;
#else
    return path.rfind("/dev/", 0) != 0;
#endif
}

}  // namespace

std::string format_bytes(uint64_t bytes) { return format_bytes_impl(bytes); }

std::string last_os_error() {
#ifdef _WIN32
    DWORD err = GetLastError();
    char buf[256];
    FormatMessageA(FORMAT_MESSAGE_FROM_SYSTEM | FORMAT_MESSAGE_IGNORE_INSERTS, nullptr, err, 0,
                    buf, sizeof(buf), nullptr);
    std::string s = buf;
    trim_inplace(s);
    if (s.empty()) {
        std::snprintf(buf, sizeof(buf), "Win32 error %lu", err);
        return buf;
    }
    return s;
#else
    return std::strerror(errno);
#endif
}

std::unique_ptr<DiskSession> open_disk(const std::string& path, bool write, bool is_file) {
    if (looks_like_winusb_path(path)) {
        if (auto s = open_winusb_bot(path, write)) return s;
    }
    if (is_file || looks_like_image_file(path)) {
        auto s = std::make_unique<FileSession>(path, write, std::unordered_set<uint64_t>{});
        if (!s->open()) return nullptr;
        return s;
    }
#ifdef _WIN32
    auto s = std::make_unique<WinDiskSession>(path, write);
    if (!s->open()) return nullptr;
    return s;
#else
    auto s = std::make_unique<PosixDiskSession>(path, write);
    if (!s->open()) return nullptr;
    return s;
#endif
}

std::unique_ptr<DiskSession> open_faulty_image(const std::string& path, bool write,
                                                const std::vector<uint64_t>& bad_lbas) {
    std::unordered_set<uint64_t> bad(bad_lbas.begin(), bad_lbas.end());
    auto s = std::make_unique<FileSession>(path, write, std::move(bad));
    if (!s->open()) return nullptr;
    return s;
}

#ifdef _WIN32
static bool disk_is_system(int disk_number) {
    char sys[MAX_PATH]{};
    GetSystemDirectoryA(sys, MAX_PATH);
    char root[4] = {sys[0], ':', '\\', 0};
    char vol[MAX_PATH]{};
    if (!GetVolumeNameForVolumeMountPointA(root, vol, MAX_PATH)) return disk_number == 0;
    std::string device = std::string("\\\\.\\") + root[0] + ":";
    HANDLE h = CreateFileA(device.c_str(), 0, FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr,
                            OPEN_EXISTING, 0, nullptr);
    if (h == INVALID_HANDLE_VALUE) return disk_number == 0;
    STORAGE_DEVICE_NUMBER sdn{};
    DWORD br = 0;
    bool match = false;
    if (DeviceIoControl(h, IOCTL_STORAGE_GET_DEVICE_NUMBER, nullptr, 0, &sdn, sizeof(sdn), &br,
                        nullptr)) {
        match = static_cast<int>(sdn.DeviceNumber) == disk_number;
    }
    CloseHandle(h);
    return match;
}
#endif

bool path_is_boot_disk(const std::string& path) {
#ifdef _WIN32
    int n = -1;
    if (sscanf(path.c_str(), "\\\\.\\PhysicalDrive%d", &n) == 1) return disk_is_system(n);
    return false;
#else
    std::ifstream cmdline("/proc/cmdline");
    std::string line;
    std::getline(cmdline, line);
    // Heuristic: /dev/sda or nvme0n1 that hosts /
    std::ifstream mounts("/proc/mounts");
    std::string dev, mp, rest;
    while (mounts >> dev >> mp >> rest) {
        std::string discard;
        std::getline(mounts, discard);
        if (mp == "/") {
            if (path == dev) return true;
            // /dev/sda1 vs /dev/sda
            if (dev.rfind(path, 0) == 0) return true;
        }
    }
    return false;
#endif
}

std::vector<DiskInfo> enumerate_disks() {
    std::vector<DiskInfo> disks;
#ifdef _WIN32
    for (int i = 0; i < 32; ++i) {
        char path[64];
        std::snprintf(path, sizeof(path), "\\\\.\\PhysicalDrive%d", i);
        auto s = open_disk(path, false, false);
        if (!s) continue;
        DiskInfo info;
        info.index = i;
        info.path = path;
        s->identify(info);
        info.is_boot_disk = disk_is_system(i);
        info.is_system_disk = info.is_boot_disk;
        if (info.size_bytes == 0) info.size_bytes = s->size_bytes();
        disks.push_back(info);
    }
    for (auto& u : enumerate_winusb_disks()) {
        bool dup = false;
        for (const auto& d : disks) {
            if (d.path == u.path) {
                dup = true;
                break;
            }
        }
        if (!dup) disks.push_back(u);
    }
#else
    DIR* d = opendir("/sys/block");
    if (d) {
        while (auto* ent = readdir(d)) {
            std::string name = ent->d_name;
            if (name == "." || name == "..") continue;
            if (name.rfind("loop", 0) == 0 || name.rfind("ram", 0) == 0 || name.rfind("sr", 0) == 0)
                continue;
            std::string path = "/dev/" + name;
            auto s = open_disk(path, false, false);
            if (!s) continue;
            DiskInfo info;
            info.path = path;
            s->identify(info);
            info.is_boot_disk = path_is_boot_disk(path);
            info.is_system_disk = info.is_boot_disk;
            disks.push_back(info);
        }
        closedir(d);
    }
#endif
    return disks;
}

}  // namespace hsc
