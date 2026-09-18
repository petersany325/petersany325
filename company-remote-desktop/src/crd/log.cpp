#include "crd/log.hpp"
#include "crd/platform.hpp"

#include <chrono>
#include <cstdio>
#include <cstring>
#include <mutex>

namespace crd {
namespace {

std::mutex g_log_mu;
std::string g_prefix = "crd";

void timestamp(char* buf, std::size_t n) {
    using clock = std::chrono::system_clock;
    const auto now = clock::now();
    const auto t = clock::to_time_t(now);
    const auto ms = std::chrono::duration_cast<std::chrono::milliseconds>(now.time_since_epoch()) % 1000;
    std::tm tm{};
#ifdef _WIN32
    localtime_s(&tm, &t);
#else
    localtime_r(&t, &tm);
#endif
    std::snprintf(buf, n, "%02d:%02d:%02d.%03d", tm.tm_hour, tm.tm_min, tm.tm_sec,
                  static_cast<int>(ms.count()));
}

const char* level_name(LogLevel level) {
    switch (level) {
    case LogLevel::Info:
        return "INFO";
    case LogLevel::Warn:
        return "WARN";
    case LogLevel::Error:
        return "ERROR";
    }
    return "?";
}

} // namespace

void log_set_prefix(const char* prefix) {
    std::lock_guard<std::mutex> lock(g_log_mu);
    g_prefix = prefix ? prefix : "crd";
}

void log_v(LogLevel level, const char* fmt, va_list args) {
    char time_buf[32];
    timestamp(time_buf, sizeof(time_buf));

    char msg[2048];
    va_list copy;
    va_copy(copy, args);
    std::vsnprintf(msg, sizeof(msg), fmt, copy);
    va_end(copy);

    char line[2200];
    {
        std::lock_guard<std::mutex> lock(g_log_mu);
        std::snprintf(line, sizeof(line), "%s [%s] %s: %s\n", time_buf, level_name(level),
                      g_prefix.c_str(), msg);
        std::fputs(line, level == LogLevel::Error ? stderr : stdout);
        std::fflush(level == LogLevel::Error ? stderr : stdout);
    }
#ifdef _WIN32
    OutputDebugStringA(line);
#endif
}

void log_info(const char* fmt, ...) {
    va_list args;
    va_start(args, fmt);
    log_v(LogLevel::Info, fmt, args);
    va_end(args);
}

void log_warn(const char* fmt, ...) {
    va_list args;
    va_start(args, fmt);
    log_v(LogLevel::Warn, fmt, args);
    va_end(args);
}

void log_error(const char* fmt, ...) {
    va_list args;
    va_start(args, fmt);
    log_v(LogLevel::Error, fmt, args);
    va_end(args);
}

std::string last_os_error() {
#ifdef _WIN32
    const DWORD code = GetLastError();
    if (code == 0) {
        return "no error";
    }
    char buf[512];
    DWORD n = FormatMessageA(FORMAT_MESSAGE_FROM_SYSTEM | FORMAT_MESSAGE_IGNORE_INSERTS, nullptr, code,
                             MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT), buf, sizeof(buf), nullptr);
    if (n == 0) {
        return "win32 error " + std::to_string(code);
    }
    while (n > 0 && (buf[n - 1] == '\n' || buf[n - 1] == '\r' || buf[n - 1] == ' ')) {
        buf[--n] = '\0';
    }
    return std::string(buf) + " (" + std::to_string(code) + ")";
#else
    return std::string(std::strerror(errno));
#endif
}

} // namespace crd
