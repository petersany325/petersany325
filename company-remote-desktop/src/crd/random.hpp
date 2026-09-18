#pragma once

#include <cstddef>
#include <cstdint>
#include <string>

namespace crd {

bool random_bytes(std::uint8_t* out, std::size_t n);
std::string random_password(std::size_t length = 8);

} // namespace crd
