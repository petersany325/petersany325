#pragma once

#include "crd/version.hpp"

#include <cstdint>
#include <string>
#include <vector>

namespace crd {

std::string to_hex(const std::uint8_t* data, std::size_t n);
bool from_hex(const std::string& hex, std::uint8_t* out, std::size_t n);
std::string normalize_id(const std::string& raw);
std::string format_id(const std::string& id);
bool valid_id(const std::string& id);
std::string generate_id();
std::string exe_directory();
std::string default_identity_path();
std::uint64_t unix_now();

} // namespace crd
