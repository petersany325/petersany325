#include "crd/config.hpp"

#include "crd/util.hpp"

#include <cctype>
#include <cstdlib>
#include <cstdio>
#include <fstream>
#include <sstream>

namespace crd {
namespace {

bool extract_quoted(const std::string& json, const char* key, std::string& out) {
    const std::string needle = std::string("\"") + key + "\"";
    auto pos = json.find(needle);
    if (pos == std::string::npos) {
        return false;
    }
    pos = json.find(':', pos + needle.size());
    if (pos == std::string::npos) {
        return false;
    }
    pos = json.find('"', pos + 1);
    if (pos == std::string::npos) {
        return false;
    }
    const auto end = json.find('"', pos + 1);
    if (end == std::string::npos) {
        return false;
    }
    out = json.substr(pos + 1, end - pos - 1);
    return true;
}

bool extract_u16(const std::string& json, const char* key, std::uint16_t& out) {
    const std::string needle = std::string("\"") + key + "\"";
    auto pos = json.find(needle);
    if (pos == std::string::npos) {
        return false;
    }
    pos = json.find(':', pos + needle.size());
    if (pos == std::string::npos) {
        return false;
    }
    ++pos;
    while (pos < json.size() && std::isspace(static_cast<unsigned char>(json[pos]))) {
        ++pos;
    }
    char* end = nullptr;
    const unsigned long v = std::strtoul(json.c_str() + pos, &end, 10);
    if (end == json.c_str() + pos || v > 65535ul) {
        return false;
    }
    out = static_cast<std::uint16_t>(v);
    return true;
}

} // namespace

bool load_app_config(const std::string& path, AppConfig& cfg) {
    std::ifstream in(path);
    if (!in) {
        return false;
    }
    std::ostringstream ss;
    ss << in.rdbuf();
    const std::string json = ss.str();
    extract_quoted(json, "hub_host", cfg.hub_host);
    extract_u16(json, "hub_port", cfg.hub_port);
    return true;
}

bool save_app_config(const std::string& path, const AppConfig& cfg) {
    std::ofstream out(path, std::ios::trunc);
    if (!out) {
        return false;
    }
    out << "{\n  \"hub_host\": \"" << cfg.hub_host << "\",\n  \"hub_port\": " << cfg.hub_port << "\n}\n";
    return true;
}

std::string default_config_path() { return exe_directory() +
#ifdef _WIN32
                                           "\\config.json";
#else
                                           "/config.json";
#endif
}

} // namespace crd
