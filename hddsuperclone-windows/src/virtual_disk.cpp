#include "virtual_disk.hpp"

#include <cstdio>
#include <fstream>

#ifdef _WIN32
#ifndef _WIN32_WINNT
#define _WIN32_WINNT 0x0A00
#endif
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#include <virtdisk.h>
#include <winioctl.h>
#else
#include <fcntl.h>
#include <unistd.h>
#include <sys/stat.h>
#endif

namespace hsc {

bool create_virtual_disk(const std::string& path, uint64_t size_bytes, std::string& error) {
#ifdef _WIN32
    VIRTUAL_STORAGE_TYPE st{};
#ifdef VIRTUAL_STORAGE_TYPE_DEVICE_VHDX
    st.DeviceId = VIRTUAL_STORAGE_TYPE_DEVICE_VHDX;
#else
    st.DeviceId = VIRTUAL_STORAGE_TYPE_DEVICE_VHD;
#endif
    st.VendorId = VIRTUAL_STORAGE_TYPE_VENDOR_MICROSOFT;
    CREATE_VIRTUAL_DISK_PARAMETERS p{};
    p.Version = CREATE_VIRTUAL_DISK_VERSION_1;
    p.Version1.MaximumSize = size_bytes < (3ull << 20) ? (3ull << 20) : size_bytes;  // VHD minimum ~3 MiB
    p.Version1.BlockSizeInBytes = 0;
    p.Version1.SectorSizeInBytes = 512;
    HANDLE h = INVALID_HANDLE_VALUE;
    std::wstring wpath(path.begin(), path.end());
    DWORD err = CreateVirtualDisk(&st, wpath.c_str(), VIRTUAL_DISK_ACCESS_ALL, nullptr,
                                  CREATE_VIRTUAL_DISK_FLAG_NONE, 0, &p, nullptr, &h);
    if (err == ERROR_SUCCESS) {
        CloseHandle(h);
        return true;
    }
    // Fallback: sparse file usable as a raw image destination.
    HANDLE f = CreateFileA(path.c_str(), GENERIC_READ | GENERIC_WRITE, 0, nullptr, CREATE_ALWAYS,
                            FILE_ATTRIBUTE_NORMAL, nullptr);
    if (f == INVALID_HANDLE_VALUE) {
        error = "CreateVirtualDisk failed (" + std::to_string(err) + ") and sparse file failed";
        return false;
    }
    LARGE_INTEGER sz;
    sz.QuadPart = static_cast<LONGLONG>(size_bytes);
    if (!SetFilePointerEx(f, sz, nullptr, FILE_BEGIN) || !SetEndOfFile(f)) {
        CloseHandle(f);
        error = "sparse image create failed";
        return false;
    }
    CloseHandle(f);
    return true;
#else
    int fd = ::open(path.c_str(), O_CREAT | O_RDWR, 0644);
    if (fd < 0) {
        error = "Cannot create virtual disk image";
        return false;
    }
    if (ftruncate(fd, static_cast<off_t>(size_bytes)) != 0) {
        error = "ftruncate failed";
        ::close(fd);
        return false;
    }
    ::close(fd);
    return true;
#endif
}

bool attach_virtual_disk(const std::string& path, std::string& mounted, std::string& error) {
#ifdef _WIN32
    auto try_open = [&](ULONG device_id) -> HANDLE {
        VIRTUAL_STORAGE_TYPE st{};
        st.DeviceId = device_id;
        st.VendorId = VIRTUAL_STORAGE_TYPE_VENDOR_MICROSOFT;
        HANDLE h = INVALID_HANDLE_VALUE;
        std::wstring wpath(path.begin(), path.end());
        DWORD err = OpenVirtualDisk(&st, wpath.c_str(), VIRTUAL_DISK_ACCESS_ATTACH_RW,
                                     OPEN_VIRTUAL_DISK_FLAG_NONE, nullptr, &h);
        if (err != ERROR_SUCCESS) return INVALID_HANDLE_VALUE;
        return h;
    };
    HANDLE h = INVALID_HANDLE_VALUE;
#ifdef VIRTUAL_STORAGE_TYPE_DEVICE_VHDX
    h = try_open(VIRTUAL_STORAGE_TYPE_DEVICE_VHDX);
#endif
    if (h == INVALID_HANDLE_VALUE) h = try_open(VIRTUAL_STORAGE_TYPE_DEVICE_VHD);
    if (h == INVALID_HANDLE_VALUE) {
        mounted = path;
        return true;  // sparse/raw image path
    }
    DWORD err = AttachVirtualDisk(h, nullptr, ATTACH_VIRTUAL_DISK_FLAG_PERMANENT_LIFETIME, 0, nullptr, nullptr);
    if (err != ERROR_SUCCESS) {
        CloseHandle(h);
        mounted = path;
        error = "AttachVirtualDisk failed (" + std::to_string(err) + ")";
        return true;
    }
    WCHAR phys[256]{};
    ULONG phys_bytes = sizeof(phys);
    if (GetVirtualDiskPhysicalPath(h, &phys_bytes, phys) == ERROR_SUCCESS) {
        char narrow[512]{};
        WideCharToMultiByte(CP_ACP, 0, phys, -1, narrow, sizeof(narrow), nullptr, nullptr);
        mounted = narrow;
    } else {
        mounted = path;
    }
    CloseHandle(h);
    return true;
#else
    mounted = path;
    (void)error;
    return true;  // sparse file is already usable as an image destination
#endif
}

bool detach_virtual_disk(const std::string& path, std::string& error) {
#ifdef _WIN32
    VIRTUAL_STORAGE_TYPE st{};
#ifdef VIRTUAL_STORAGE_TYPE_DEVICE_VHDX
    st.DeviceId = VIRTUAL_STORAGE_TYPE_DEVICE_VHDX;
#else
    st.DeviceId = VIRTUAL_STORAGE_TYPE_DEVICE_VHD;
#endif
    st.VendorId = VIRTUAL_STORAGE_TYPE_VENDOR_MICROSOFT;
    HANDLE h = INVALID_HANDLE_VALUE;
    std::wstring wpath(path.begin(), path.end());
    DWORD err = OpenVirtualDisk(&st, wpath.c_str(), VIRTUAL_DISK_ACCESS_DETACH, OPEN_VIRTUAL_DISK_FLAG_NONE,
                                 nullptr, &h);
    if (err != ERROR_SUCCESS) {
#ifdef VIRTUAL_STORAGE_TYPE_DEVICE_VHDX
        st.DeviceId = VIRTUAL_STORAGE_TYPE_DEVICE_VHD;
        err = OpenVirtualDisk(&st, wpath.c_str(), VIRTUAL_DISK_ACCESS_DETACH, OPEN_VIRTUAL_DISK_FLAG_NONE,
                               nullptr, &h);
#endif
    }
    if (err != ERROR_SUCCESS || h == INVALID_HANDLE_VALUE) {
        error = "OpenVirtualDisk failed";
        return false;
    }
    err = DetachVirtualDisk(h, DETACH_VIRTUAL_DISK_FLAG_NONE, 0);
    CloseHandle(h);
    if (err != ERROR_SUCCESS) {
        error = "DetachVirtualDisk failed";
        return false;
    }
    return true;
#else
    (void)path;
    (void)error;
    return true;
#endif
}

}  // namespace hsc
