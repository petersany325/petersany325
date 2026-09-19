#include "crd/random.hpp"

#ifdef _WIN32
#include "crd/platform.hpp"
#include <bcrypt.h>
#else
#include <fstream>
#endif

#include <vector>

namespace crd {

bool random_bytes(std::uint8_t* out, std::size_t n) {
#ifdef _WIN32
    const NTSTATUS st = BCryptGenRandom(nullptr, out, static_cast<ULONG>(n), BCRYPT_USE_SYSTEM_PREFERRED_RNG);
    return st == 0;
#else
    std::ifstream urandom("/dev/urandom", std::ios::binary);
    if (!urandom) {
        return false;
    }
    urandom.read(reinterpret_cast<char*>(out), static_cast<std::streamsize>(n));
    return urandom.good();
#endif
}

std::string random_password(std::size_t length) {
    static const char kAlphabet[] = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
    const std::size_t nalpha = sizeof(kAlphabet) - 1;
    std::string out(length, '0');
    std::vector<std::uint8_t> raw(length);
    if (!random_bytes(raw.data(), raw.size())) {
        return "changeme";
    }
    for (std::size_t i = 0; i < length; ++i) {
        out[i] = kAlphabet[raw[i] % nalpha];
    }
    return out;
}

} // namespace crd
