#pragma once

#ifdef _WIN32

#include <cstdint>
#include <vector>

namespace crd {

// Encode BGRA (top-down, stride = width * 4) to JPEG using Windows Imaging Component.
bool jpeg_encode_bgra(const std::uint8_t* bgra, int width, int height, int quality, std::vector<std::uint8_t>& jpeg);

// Decode JPEG to BGRA (top-down, stride = width * 4).
bool jpeg_decode_bgra(const std::uint8_t* jpeg, std::size_t jpeg_size, std::vector<std::uint8_t>& bgra, int& width,
                      int& height);

} // namespace crd

#endif
