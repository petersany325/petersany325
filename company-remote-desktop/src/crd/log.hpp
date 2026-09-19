#pragma once

#include <cstdarg>
#include <string>

namespace crd {

enum class LogLevel { Info, Warn, Error };

void log_set_prefix(const char* prefix);
void log_v(LogLevel level, const char* fmt, va_list args);
void log_info(const char* fmt, ...);
void log_warn(const char* fmt, ...);
void log_error(const char* fmt, ...);
std::string last_os_error();

} // namespace crd
