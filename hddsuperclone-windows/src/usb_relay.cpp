#include "usb_relay.hpp"

#include <chrono>
#include <cstring>
#include <thread>

#ifdef _WIN32
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#include <setupapi.h>
extern "C" {
#include <hidsdi.h>
}
#else
#include <dirent.h>
#include <fcntl.h>
#include <unistd.h>
#include <linux/hidraw.h>
#include <sys/ioctl.h>
#endif

namespace hsc {

void encode_dcttech_set(uint8_t out[8], int channel, bool on) {
    std::memset(out, 0, 8);
    if (channel <= 0) {
        out[0] = on ? 0xFE : 0xFC;  // all on / all off
        return;
    }
    out[0] = on ? 0xFF : 0xFD;
    out[1] = static_cast<uint8_t>(channel);
}

#ifdef _WIN32
std::vector<RelayInfo> enumerate_relays() {
    std::vector<RelayInfo> out;
    GUID hid_guid;
    HidD_GetHidGuid(&hid_guid);
    HDEVINFO set = SetupDiGetClassDevsA(&hid_guid, nullptr, nullptr, DIGCF_PRESENT | DIGCF_DEVICEINTERFACE);
    if (set == INVALID_HANDLE_VALUE) return out;
    SP_DEVICE_INTERFACE_DATA ifd{};
    ifd.cbSize = sizeof(ifd);
    for (DWORD i = 0; SetupDiEnumDeviceInterfaces(set, nullptr, &hid_guid, i, &ifd); ++i) {
        DWORD need = 0;
        SetupDiGetDeviceInterfaceDetailA(set, &ifd, nullptr, 0, &need, nullptr);
        std::vector<uint8_t> buf(need);
        auto* det = reinterpret_cast<SP_DEVICE_INTERFACE_DETAIL_DATA_A*>(buf.data());
        det->cbSize = sizeof(SP_DEVICE_INTERFACE_DETAIL_DATA_A);
        if (!SetupDiGetDeviceInterfaceDetailA(set, &ifd, det, need, nullptr, nullptr)) continue;
        HANDLE h = CreateFileA(det->DevicePath, GENERIC_READ | GENERIC_WRITE,
                                FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr, OPEN_EXISTING, 0, nullptr);
        if (h == INVALID_HANDLE_VALUE) continue;
        HIDD_ATTRIBUTES attr{};
        attr.Size = sizeof(attr);
        if (HidD_GetAttributes(h, &attr) && attr.VendorID == 0x16C0 && attr.ProductID == 0x05DF) {
            RelayInfo r;
            r.path = det->DevicePath;
            r.vid = attr.VendorID;
            r.pid = attr.ProductID;
            r.name = "USBRelay (dcttech)";
            r.channels = 8;
            out.push_back(r);
        }
        CloseHandle(h);
    }
    SetupDiDestroyDeviceInfoList(set);
    return out;
}

bool relay_set(const std::string& path, int channel, bool on, std::string& error) {
    HANDLE h = CreateFileA(path.c_str(), GENERIC_READ | GENERIC_WRITE,
                            FILE_SHARE_READ | FILE_SHARE_WRITE, nullptr, OPEN_EXISTING, 0, nullptr);
    if (h == INVALID_HANDLE_VALUE) {
        error = "Cannot open USB relay";
        return false;
    }
    uint8_t report[9]{};
    encode_dcttech_set(report + 1, channel, on);
    BOOL ok = HidD_SetFeature(h, report, sizeof(report));
    CloseHandle(h);
    if (!ok) {
        error = "HID SetFeature failed";
        return false;
    }
    return true;
}
#else
std::vector<RelayInfo> enumerate_relays() {
    std::vector<RelayInfo> out;
    DIR* d = opendir("/dev");
    if (!d) return out;
    while (auto* e = readdir(d)) {
        std::string n = e->d_name;
        if (n.rfind("hidraw", 0) != 0) continue;
        RelayInfo r;
        r.path = "/dev/" + n;
        r.name = "HID raw (open to probe dcttech 16c0:05df)";
        r.channels = 8;
        out.push_back(r);
    }
    closedir(d);
    return out;
}

bool relay_set(const std::string& path, int channel, bool on, std::string& error) {
    int fd = ::open(path.c_str(), O_RDWR);
    if (fd < 0) {
        error = "Cannot open HID relay device";
        return false;
    }
    uint8_t report[9]{};
    encode_dcttech_set(report + 1, channel, on);
    ssize_t n = ::write(fd, report, sizeof(report));
    ::close(fd);
    if (n < 0) {
        error = "HID write failed";
        return false;
    }
    return true;
}
#endif

bool relay_power_cycle(const std::string& path, int channel, int off_ms, int on_ms, std::string& error) {
    if (!relay_set(path, channel, false, error)) return false;
    std::this_thread::sleep_for(std::chrono::milliseconds(off_ms > 0 ? off_ms : 2000));
    if (!relay_set(path, channel, true, error)) return false;
    std::this_thread::sleep_for(std::chrono::milliseconds(on_ms > 0 ? on_ms : 1000));
    return true;
}

}  // namespace hsc
