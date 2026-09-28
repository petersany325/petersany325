#include "usb_direct.hpp"

#include <algorithm>
#include <cctype>
#include <cstring>
#include <vector>

#ifdef _WIN32
#ifndef NOMINMAX
#define NOMINMAX
#endif
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#ifndef _WIN32_WINNT
#define _WIN32_WINNT 0x0A00
#endif
#include <windows.h>
#include <setupapi.h>
#include <winusb.h>
#include <usb100.h>
#ifndef USB_ENDPOINT_DIRECTION_IN
#define USB_ENDPOINT_DIRECTION_IN(addr) (((addr) & 0x80) != 0)
#endif
#endif

namespace hsc {
namespace {

#ifdef _WIN32

constexpr uint32_t kCbwSig = 0x43425355u;
constexpr uint32_t kCswSig = 0x53425355u;

#pragma pack(push, 1)
struct Cbw {
    uint32_t signature;
    uint32_t tag;
    uint32_t data_length;
    uint8_t flags;
    uint8_t lun;
    uint8_t cb_length;
    uint8_t cb[16];
};
struct Csw {
    uint32_t signature;
    uint32_t tag;
    uint32_t residue;
    uint8_t status;
};
#pragma pack(pop)

class WinUsbBotSession final : public DiskSession {
public:
    WinUsbBotSession(std::string path, bool write) : path_(std::move(path)), write_(write) {}

    bool open() {
        handle_ = CreateFileA(path_.c_str(), GENERIC_READ | GENERIC_WRITE,
                               FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr, OPEN_EXISTING,
                               FILE_FLAG_OVERLAPPED, nullptr);
        if (handle_ == INVALID_HANDLE_VALUE) return false;
        if (!WinUsb_Initialize(handle_, &usb_)) {
            CloseHandle(handle_);
            handle_ = INVALID_HANDLE_VALUE;
            return false;
        }
        USB_INTERFACE_DESCRIPTOR idesc{};
        if (!WinUsb_QueryInterfaceSettings(usb_, 0, &idesc)) return true;
        for (UCHAR i = 0; i < idesc.bNumEndpoints; ++i) {
            WINUSB_PIPE_INFORMATION pipe{};
            if (!WinUsb_QueryPipe(usb_, 0, i, &pipe)) continue;
            if (pipe.PipeType != UsbdPipeTypeBulk) continue;
            if (USB_ENDPOINT_DIRECTION_IN(pipe.PipeId))
                in_pipe_ = pipe.PipeId;
            else
                out_pipe_ = pipe.PipeId;
        }
        return in_pipe_ != 0 && out_pipe_ != 0;
    }

    bool is_open() const override { return handle_ != INVALID_HANDLE_VALUE && usb_ != nullptr; }
    bool writable() const override { return write_; }
    uint64_t size_bytes() const override { return size_; }
    uint32_t sector_size() const override { return sector_size_; }
    std::string path() const override { return path_; }

    IoResult bot_scsi(const uint8_t* cdb, uint8_t cdb_len, void* data, uint32_t bytes, bool data_in,
                      int timeout_ms) {
        (void)timeout_ms;
        IoResult r;
        if (!usb_ || !in_pipe_ || !out_pipe_) {
            r.message = "WinUSB BOT pipes not ready (bind the device to WinUSB / Zadig)";
            return r;
        }
        Cbw cbw{};
        cbw.signature = kCbwSig;
        cbw.tag = ++tag_;
        cbw.data_length = bytes;
        cbw.flags = data_in ? 0x80 : 0x00;
        cbw.cb_length = cdb_len;
        std::memcpy(cbw.cb, cdb, cdb_len);

        ULONG xfer = 0;
        if (!WinUsb_WritePipe(usb_, out_pipe_, reinterpret_cast<PUCHAR>(&cbw), sizeof(cbw), &xfer,
                              nullptr)) {
            r.host_error = static_cast<int>(GetLastError());
            r.message = "BOT CBW write failed";
            return r;
        }
        if (bytes && data) {
            BOOL ok = data_in ? WinUsb_ReadPipe(usb_, in_pipe_, static_cast<PUCHAR>(data), bytes, &xfer,
                                                nullptr)
                              : WinUsb_WritePipe(usb_, out_pipe_, static_cast<PUCHAR>(data), bytes, &xfer,
                                                 nullptr);
            if (!ok) {
                r.host_error = static_cast<int>(GetLastError());
                r.message = "BOT data stage failed";
                return r;
            }
        }
        Csw csw{};
        if (!WinUsb_ReadPipe(usb_, in_pipe_, reinterpret_cast<PUCHAR>(&csw), sizeof(csw), &xfer,
                             nullptr) ||
            csw.signature != kCswSig || csw.status != 0) {
            r.message = "BOT CSW failed";
            r.host_error = csw.status;
            return r;
        }
        r.ok = true;
        r.sectors_transferred = sector_size_ ? static_cast<int>(bytes / sector_size_) : 0;
        return r;
    }

    IoResult read_sectors(uint64_t lba, uint32_t count, void* buffer, IoMode, int timeout_ms) override {
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
        return bot_scsi(cdb, 16, buffer, count * sector_size_, true, timeout_ms);
    }

    IoResult write_sectors(uint64_t lba, uint32_t count, const void* buffer, int timeout_ms) override {
        if (!write_) {
            IoResult r;
            r.message = "destination not opened for write";
            return r;
        }
        uint8_t cdb[16]{};
        cdb[0] = 0x8A;
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
        return bot_scsi(cdb, 16, const_cast<void*>(buffer), count * sector_size_, false, timeout_ms);
    }

    bool identify(DiskInfo& info) override {
        info.path = path_;
        info.display_name = "WinUSB BOT " + path_;
        info.bus = "USB";
        info.size_bytes = size_;
        info.sector_size = sector_size_;
        uint8_t inq[36]{};
        uint8_t cdb[6]{0x12, 0, 0, 0, 36, 0};
        if (bot_scsi(cdb, 6, inq, 36, true, 3000).ok) {
            char prod[17]{};
            std::memcpy(prod, inq + 16, 16);
            info.model = prod;
            info.scsi_inquiry_ok = true;
            info.display_name = info.model + " (WinUSB BOT)";
        }
        uint8_t rc[8]{};
        uint8_t cdb10[10]{0x25};
        if (bot_scsi(cdb10, 10, rc, 8, true, 3000).ok) {
            uint32_t last = (uint32_t(rc[0]) << 24) | (uint32_t(rc[1]) << 16) | (uint32_t(rc[2]) << 8) | rc[3];
            uint32_t bps = (uint32_t(rc[4]) << 24) | (uint32_t(rc[5]) << 16) | (uint32_t(rc[6]) << 8) | rc[7];
            if (bps) sector_size_ = bps;
            size_ = (static_cast<uint64_t>(last) + 1) * sector_size_;
            info.size_bytes = size_;
            info.sector_size = sector_size_;
        }
        return true;
    }

    ~WinUsbBotSession() override {
        if (usb_) WinUsb_Free(usb_);
        if (handle_ != INVALID_HANDLE_VALUE) CloseHandle(handle_);
    }

private:
    std::string path_;
    bool write_ = false;
    HANDLE handle_ = INVALID_HANDLE_VALUE;
    WINUSB_INTERFACE_HANDLE usb_ = nullptr;
    UCHAR in_pipe_ = 0;
    UCHAR out_pipe_ = 0;
    uint32_t tag_ = 0;
    uint64_t size_ = 0;
    uint32_t sector_size_ = kDefaultSectorSize;
};

#endif  // _WIN32

}  // namespace

bool looks_like_winusb_path(const std::string& path) {
    std::string p = path;
    std::transform(p.begin(), p.end(), p.begin(),
                   [](unsigned char c) { return static_cast<char>(std::tolower(c)); });
    return p.find("\\\\?\\usb") == 0 || p.find("{dee824ef-") != std::string::npos ||
           p.find("winusb") != std::string::npos;
}

std::unique_ptr<DiskSession> open_winusb_bot(const std::string& path, bool write) {
#ifdef _WIN32
    auto s = std::make_unique<WinUsbBotSession>(path, write);
    if (!s->open()) return nullptr;
    return s;
#else
    (void)path;
    (void)write;
    return nullptr;
#endif
}

std::vector<DiskInfo> enumerate_winusb_disks() {
    std::vector<DiskInfo> out;
#ifdef _WIN32
    GUID guid{};
    // GUID_DEVINTERFACE_USB_DEVICE
    guid.Data1 = 0xA5DCBF10;
    guid.Data2 = 0x6530;
    guid.Data3 = 0x11D2;
    guid.Data4[0] = 0x90;
    guid.Data4[1] = 0x1F;
    guid.Data4[2] = 0x00;
    guid.Data4[3] = 0xC0;
    guid.Data4[4] = 0x4F;
    guid.Data4[5] = 0xB9;
    guid.Data4[6] = 0x51;
    guid.Data4[7] = 0xED;
    HDEVINFO set = SetupDiGetClassDevsA(&guid, nullptr, nullptr, DIGCF_PRESENT | DIGCF_DEVICEINTERFACE);
    if (set == INVALID_HANDLE_VALUE) return out;
    SP_DEVICE_INTERFACE_DATA ifd{};
    ifd.cbSize = sizeof(ifd);
    for (DWORD i = 0; SetupDiEnumDeviceInterfaces(set, nullptr, &guid, i, &ifd); ++i) {
        DWORD need = 0;
        SetupDiGetDeviceInterfaceDetailA(set, &ifd, nullptr, 0, &need, nullptr);
        std::vector<uint8_t> buf(need);
        auto* det = reinterpret_cast<SP_DEVICE_INTERFACE_DETAIL_DATA_A*>(buf.data());
        det->cbSize = sizeof(SP_DEVICE_INTERFACE_DETAIL_DATA_A);
        if (!SetupDiGetDeviceInterfaceDetailA(set, &ifd, det, need, nullptr, nullptr)) continue;
        DiskInfo info;
        info.path = det->DevicePath;
        info.bus = "USB";
        info.display_name = std::string("USB device (WinUSB if bound) ") + det->DevicePath;
        info.removable = true;
        out.push_back(info);
    }
    SetupDiDestroyDeviceInfoList(set);
#endif
    return out;
}

}  // namespace hsc
