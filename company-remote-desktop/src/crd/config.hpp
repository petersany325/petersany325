#pragma once

#include "crd/version.hpp"

#include <cstdint>
#include <string>

namespace crd {

struct AppConfig {
    std::string hub_host = "127.0.0.1";
    std::uint16_t hub_port = kDefaultPort;
};

bool load_app_config(const std::string& path, AppConfig& cfg);
bool save_app_config(const std::string& path, const AppConfig& cfg);
std::string default_config_path();

} // namespace crd
