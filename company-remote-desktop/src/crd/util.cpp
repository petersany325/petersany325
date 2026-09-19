#include "crd/util.hpp"

#include "crd/platform.hpp"
#include "crd/random.hpp"

#include <cctype>
#include <chrono>
#include <cstdlib>
#include <cstdio>
#include <cstring>

#ifdef _WIN32
#include <shlobj.h>
#else
#include <pwd.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

namespace crd {
namespace {

int hex_nibble(char c) {
    if (c >= '0' && c <= '9') {
        return c - '0';
    }
    if (c >= 'a' && c <= 'f') {
        return c - 'a' + 10;
    }
    if (c >= 'A' && c <= 'F') {
        return c - 'A' + 10;
    }
    return -1;
}

} // namespace

std::string to_hex(const std::uint8_t* data, std::size_t n) {
    std::string out(n * 2, '0');
    for (std::size_t i = 0; i < n; ++i) {
        std::snprintf(out.data() + i * 2, 3, "%02x", data[i]);
    }
    return out;
}

bool from_hex(const std::string& hex, std::uint8_t* out, std::size_t n) {
    if (hex.size() != n * 2) {
        return false;
    }
    for (std::size_t i = 0; i < n; ++i) {
        const int hi = hex_nibble(hex[i * 2]);
        const int lo = hex_nibble(hex[i * 2 + 1]);
        if (hi < 0 || lo < 0) {
            return false;
        }
        out[i] = static_cast<std::uint8_t>((hi << 4) | lo);
    }
    return true;
}

std::string normalize_id(const std::string& raw) {
    std::string out;
    out.reserve(raw.size());
    for (unsigned char c : raw) {
        if (std::isdigit(c)) {
            out.push_back(static_cast<char>(c));
        }
    }
    return out;
}

std::string format_id(const std::string& id) {
    const std::string n = normalize_id(id);
    if (n.size() != static_cast<std::size_t>(kIdDigits)) {
        return n;
    }
    return n.substr(0, 3) + " " + n.substr(3, 3) + " " + n.substr(6, 3);
}

bool valid_id(const std::string& id) {
    const std::string n = normalize_id(id);
    return n.size() == static_cast<std::size_t>(kIdDigits);
}

std::string generate_id() {
    std::uint8_t raw[8]{};
    if (!random_bytes(raw, sizeof(raw))) {
        return {};
    }
    std::uint64_t v = 0;
    for (int i = 0; i < 8; ++i) {
        v = (v << 8) | raw[i];
    }
    const std::uint64_t num = 100000000ull + (v % 900000000ull);
    char buf[16];
    std::snprintf(buf, sizeof(buf), "%09llu", static_cast<unsigned long long>(num));
    return std::string(buf);
}

std::string exe_directory() {
#ifdef _WIN32
    wchar_t path[MAX_PATH]{};
    const DWORD n = GetModuleFileNameW(nullptr, path, MAX_PATH);
    if (n == 0) {
        return ".";
    }
    std::wstring w(path, n);
    const auto slash = w.find_last_of(L"\\/");
    if (slash != std::wstring::npos) {
        w.resize(slash);
    }
    const int bytes = WideCharToMultiByte(CP_UTF8, 0, w.c_str(), static_cast<int>(w.size()), nullptr, 0, nullptr, nullptr);
    std::string out(bytes, '\0');
    WideCharToMultiByte(CP_UTF8, 0, w.c_str(), static_cast<int>(w.size()), out.data(), bytes, nullptr, nullptr);
    return out.empty() ? "." : out;
#else
    char path[4096]{};
    const ssize_t n = readlink("/proc/self/exe", path, sizeof(path) - 1);
    if (n <= 0) {
        return ".";
    }
    std::string s(path, static_cast<std::size_t>(n));
    const auto slash = s.find_last_of('/');
    return slash == std::string::npos ? "." : s.substr(0, slash);
#endif
}

std::string default_identity_path() {
#ifdef _WIN32
    wchar_t appdata[MAX_PATH]{};
    if (FAILED(SHGetFolderPathW(nullptr, CSIDL_APPDATA, nullptr, SHGFP_TYPE_CURRENT, appdata))) {
        return exe_directory() + "\\agent.json";
    }
    std::wstring dir = std::wstring(appdata) + L"\\CompanyRemoteDesktop";
    CreateDirectoryW(dir.c_str(), nullptr);
    dir += L"\\agent.json";
    const int bytes = WideCharToMultiByte(CP_UTF8, 0, dir.c_str(), -1, nullptr, 0, nullptr, nullptr);
    std::string out(bytes > 0 ? static_cast<std::size_t>(bytes - 1) : 0, '\0');
    if (bytes > 1) {
        WideCharToMultiByte(CP_UTF8, 0, dir.c_str(), -1, out.data(), bytes, nullptr, nullptr);
    }
    return out;
#else
    const char* home = std::getenv("HOME");
    std::string dir = home ? std::string(home) + "/.config/company-remote-desktop" : ".";
    mkdir(dir.c_str(), 0700);
    return dir + "/agent.json";
#endif
}

std::uint64_t unix_now() {
    return static_cast<std::uint64_t>(
        std::chrono::duration_cast<std::chrono::seconds>(std::chrono::system_clock::now().time_since_epoch()).count());
}

} // namespace crd
