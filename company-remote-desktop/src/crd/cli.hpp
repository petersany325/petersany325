#pragma once

#include "crd/version.hpp"

#include <cstdint>
#include <cstdlib>
#include <cstring>
#include <string>

namespace crd {

struct HostCli {
    std::string bind_ip = "0.0.0.0";
    std::uint16_t port = kDefaultPort;
    std::string password;
    int quality = kDefaultJpegQuality;
    int fps = kDefaultFps;
    int scale_percent = 100;
    bool show_help = false;
};

struct ViewerCli {
    std::string hub_host = kDefaultHubHost;
    std::string target_id;
    std::uint16_t port = kDefaultPort;
    std::string password;
    bool show_help = false;
    bool hub_from_cli = false;
    bool port_from_cli = false;
};

inline bool parse_u16(const char* s, std::uint16_t& out) {
    if (!s || !*s) {
        return false;
    }
    char* end = nullptr;
    const unsigned long v = std::strtoul(s, &end, 10);
    if (end == s || *end != '\0' || v > 65535ul) {
        return false;
    }
    out = static_cast<std::uint16_t>(v);
    return true;
}

inline bool parse_int(const char* s, int& out, int lo, int hi) {
    if (!s || !*s) {
        return false;
    }
    char* end = nullptr;
    const long v = std::strtol(s, &end, 10);
    if (end == s || *end != '\0' || v < lo || v > hi) {
        return false;
    }
    out = static_cast<int>(v);
    return true;
}

inline bool arg_eq(const char* a, const char* b) { return a && b && std::strcmp(a, b) == 0; }

inline HostCli parse_host_cli(int argc, char** argv) {
    HostCli c;
    for (int i = 1; i < argc; ++i) {
        if (arg_eq(argv[i], "--help") || arg_eq(argv[i], "-h")) {
            c.show_help = true;
        } else if (arg_eq(argv[i], "--port") && i + 1 < argc) {
            parse_u16(argv[++i], c.port);
        } else if (arg_eq(argv[i], "--bind") && i + 1 < argc) {
            c.bind_ip = argv[++i];
        } else if (arg_eq(argv[i], "--password") && i + 1 < argc) {
            c.password = argv[++i];
        } else if (arg_eq(argv[i], "--quality") && i + 1 < argc) {
            parse_int(argv[++i], c.quality, 1, 100);
        } else if (arg_eq(argv[i], "--fps") && i + 1 < argc) {
            parse_int(argv[++i], c.fps, 1, 60);
        } else if (arg_eq(argv[i], "--scale") && i + 1 < argc) {
            parse_int(argv[++i], c.scale_percent, 25, 100);
        }
    }
    return c;
}

inline ViewerCli parse_viewer_cli(int argc, char** argv) {
    ViewerCli c;
    for (int i = 1; i < argc; ++i) {
        if (arg_eq(argv[i], "--help") || arg_eq(argv[i], "-h")) {
            c.show_help = true;
        } else if ((arg_eq(argv[i], "--hub") || arg_eq(argv[i], "--host") || arg_eq(argv[i], "--ip")) && i + 1 < argc) {
            c.hub_host = argv[++i];
            c.hub_from_cli = true;
        } else if ((arg_eq(argv[i], "--id") || arg_eq(argv[i], "--target")) && i + 1 < argc) {
            c.target_id = argv[++i];
        } else if ((arg_eq(argv[i], "--hub-port") || arg_eq(argv[i], "--port")) && i + 1 < argc) {
            parse_u16(argv[++i], c.port);
            c.port_from_cli = true;
        } else if (arg_eq(argv[i], "--password") && i + 1 < argc) {
            c.password = argv[++i];
        }
    }
    return c;
}

} // namespace crd
