#pragma once

#include <cstdint>

namespace crd {

inline constexpr const char* kProductName = "Company Remote Desktop";
inline constexpr const char* kProductVersion = "0.1.0";
inline constexpr std::uint16_t kProtocolVersion = 1;
inline constexpr std::uint16_t kDefaultPort = 5938;
inline constexpr int kDefaultJpegQuality = 62; // 1..100
inline constexpr int kDefaultFps = 15;
inline constexpr int kMaxPasswordBytes = 128;
inline constexpr int kMaxMessageBytes = 12 * 1024 * 1024;
inline constexpr int kNonceBytes = 16;
inline constexpr int kSha256Bytes = 32;

} // namespace crd
