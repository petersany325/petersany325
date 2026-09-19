#pragma once

#include "crd/version.hpp"

#include <cstdint>
#include <string>

namespace crd {

struct AgentIdentity {
    std::string id;
    std::uint8_t device_secret[kDeviceSecretBytes]{};
    std::string access_password;
    bool has_secret = false;
};

bool load_identity(const std::string& path, AgentIdentity& id);
bool save_identity(const std::string& path, const AgentIdentity& id);

} // namespace crd
