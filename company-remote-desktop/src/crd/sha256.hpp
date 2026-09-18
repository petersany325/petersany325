#pragma once

#include <cstddef>
#include <cstdint>
#include <string>
#include <vector>

namespace crd {

void sha256(const std::uint8_t* data, std::size_t len, std::uint8_t out[32]);
std::vector<std::uint8_t> sha256(const std::uint8_t* data, std::size_t len);
std::string sha256_hex(const std::uint8_t* data, std::size_t len);

// AUTH digest = SHA-256(nonce[16] || password_utf8)
void auth_digest(const std::uint8_t nonce[16], const std::string& password, std::uint8_t out[32]);

} // namespace crd
