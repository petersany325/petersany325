#ifdef _WIN32

#include "agent/agent_window.hpp"
#include "agent/runtime.hpp"
#include "crd/cli.hpp"
#include "crd/config.hpp"
#include "crd/log.hpp"
#include "crd/platform.hpp"
#include "crd/random.hpp"
#include "crd/tcp_socket.hpp"
#include "crd/util.hpp"
#include "host/desktop_capture.hpp"
#include "host/input_inject.hpp"

#include <objbase.h>

#include <atomic>
#include <cstdio>
#include <thread>

#pragma comment(lib, "user32.lib")
#pragma comment(lib, "ole32.lib")
#pragma comment(lib, "shell32.lib")

namespace {

void enable_dpi() {
    HMODULE user = GetModuleHandleW(L"user32.dll");
    if (!user) {
        return;
    }
    using Fn = BOOL(WINAPI*)(HANDLE);
    auto set = reinterpret_cast<Fn>(GetProcAddress(user, "SetProcessDpiAwarenessContext"));
    if (set) {
        set(reinterpret_cast<HANDLE>(static_cast<intptr_t>(-4)));
    }
}

} // namespace

int main(int argc, char** argv) {
    crd::log_set_prefix("agent");
    crd::AppConfig file_cfg;
    crd::load_app_config(crd::default_config_path(), file_cfg);

    crd::AgentConfig cfg;
    cfg.hub_host = file_cfg.hub_host;
    cfg.hub_port = file_cfg.hub_port;
    cfg.identity_path = crd::default_identity_path();
    int quality = crd::kDefaultJpegQuality;
    int fps = crd::kDefaultFps;
    int scale = 100;
    bool help = false;
    for (int i = 1; i < argc; ++i) {
        if (crd::arg_eq(argv[i], "--help") || crd::arg_eq(argv[i], "-h")) {
            help = true;
        } else if ((crd::arg_eq(argv[i], "--hub") || crd::arg_eq(argv[i], "--host")) && i + 1 < argc) {
            cfg.hub_host = argv[++i];
        } else if ((crd::arg_eq(argv[i], "--hub-port") || crd::arg_eq(argv[i], "--port")) && i + 1 < argc) {
            crd::parse_u16(argv[++i], cfg.hub_port);
        } else if (crd::arg_eq(argv[i], "--password") && i + 1 < argc) {
            cfg.access_password = argv[++i];
        } else if (crd::arg_eq(argv[i], "--identity") && i + 1 < argc) {
            cfg.identity_path = argv[++i];
        } else if (crd::arg_eq(argv[i], "--quality") && i + 1 < argc) {
            crd::parse_int(argv[++i], quality, 1, 100);
        } else if (crd::arg_eq(argv[i], "--fps") && i + 1 < argc) {
            crd::parse_int(argv[++i], fps, 1, 60);
        } else if (crd::arg_eq(argv[i], "--scale") && i + 1 < argc) {
            crd::parse_int(argv[++i], scale, 25, 100);
        }
    }
    cfg.jpeg_quality = quality;
    cfg.fps = fps;
    if (help) {
        std::printf("Company Remote Desktop — Agent\n\n");
        std::printf("  agent.exe [--hub hdd-land.com] [--hub-port 5938] [--password SECRET]\n");
        std::printf("            [--quality 62] [--fps 15] [--scale 100]\n\n");
        std::printf("Registers with the company Hub and shows a unique ID.\n");
        return 0;
    }

    enable_dpi();
    CoInitializeEx(nullptr, COINIT_MULTITHREADED);
    std::string sock_err;
    if (!crd::TcpSocket::startup(&sock_err)) {
        crd::log_error("%s", sock_err.c_str());
        return 1;
    }

    crd::WinJpegFrameSource source(quality, fps, scale);
    std::string cap_err;
    if (!source.start(&cap_err)) {
        crd::log_error("capture failed: %s", cap_err.c_str());
        crd::TcpSocket::cleanup();
        return 1;
    }
    crd::WinInputSink input;
    int fw = 0, fh = 0;
    source.desktop_size(fw, fh);
    input.set_frame_size(fw, fh);

    crd::AgentStats stats;
    crd::AgentRuntime runtime(cfg, source, input, stats);
    std::atomic<bool> stop{false};
    std::thread net([&] { runtime.run(stop); });

    crd::AgentWindow window;
    if (!window.create(runtime, stats, source.backend(), cfg.hub_host + ":" + std::to_string(cfg.hub_port))) {
        stop.store(true);
        net.join();
        source.stop();
        crd::TcpSocket::cleanup();
        return 1;
    }
    window.message_loop(stop);
    stop.store(true);
    if (net.joinable()) {
        net.join();
    }
    source.stop();
    crd::TcpSocket::cleanup();
    CoUninitialize();
    return 0;
}

#else
#include "agent/runtime.hpp"
#include "crd/cli.hpp"
#include "crd/config.hpp"
#include "crd/log.hpp"
#include "crd/random.hpp"
#include "crd/tcp_socket.hpp"
#include "crd/util.hpp"

#include <atomic>
#include <chrono>
#include <cstdio>
#include <thread>
#include <vector>

class NullFrames : public crd::IFrameSource {
public:
    bool desktop_size(int& width, int& height) override {
        width = 64;
        height = 48;
        return true;
    }
    bool next_jpeg(std::vector<std::uint8_t>& jpeg, int& width, int& height, std::uint32_t timeout_ms) override {
        width = 64;
        height = 48;
        jpeg = {0xFF, 0xD8, 0xFF, 0xD9};
        if (timeout_ms > 0) {
            std::this_thread::sleep_for(std::chrono::milliseconds(timeout_ms));
        }
        return true;
    }
};

class NullInput : public crd::IInputSink {
public:
    void on_mouse(const crd::MouseEvent&) override {}
    void on_key(const crd::KeyEvent&) override {}
};

int main(int argc, char** argv) {
    crd::log_set_prefix("agent");
    crd::AppConfig file_cfg;
    crd::load_app_config(crd::default_config_path(), file_cfg);
    crd::AgentConfig cfg;
    cfg.hub_host = file_cfg.hub_host;
    cfg.hub_port = file_cfg.hub_port;
    cfg.identity_path = crd::default_identity_path();
    for (int i = 1; i < argc; ++i) {
        if ((crd::arg_eq(argv[i], "--hub") || crd::arg_eq(argv[i], "--host")) && i + 1 < argc) {
            cfg.hub_host = argv[++i];
        } else if ((crd::arg_eq(argv[i], "--hub-port") || crd::arg_eq(argv[i], "--port")) && i + 1 < argc) {
            crd::parse_u16(argv[++i], cfg.hub_port);
        } else if (crd::arg_eq(argv[i], "--password") && i + 1 < argc) {
            cfg.access_password = argv[++i];
        } else if (crd::arg_eq(argv[i], "--identity") && i + 1 < argc) {
            cfg.identity_path = argv[++i];
        }
    }
    if (cfg.access_password.empty()) {
        cfg.access_password = crd::random_password(8);
    }
    std::string err;
    if (!crd::TcpSocket::startup(&err)) {
        crd::log_error("%s", err.c_str());
        return 1;
    }
    NullFrames frames;
    NullInput input;
    crd::AgentStats stats;
    crd::AgentRuntime runtime(cfg, frames, input, stats);
    std::atomic<bool> stop{false};
    std::thread t([&] { runtime.run(stop); });
    for (int i = 0; i < 50 && stats.id.empty(); ++i) {
        std::this_thread::sleep_for(std::chrono::milliseconds(100));
        std::lock_guard<std::mutex> lock(stats.mu);
        if (!stats.id.empty()) {
            break;
        }
    }
    {
        std::lock_guard<std::mutex> lock(stats.mu);
        std::printf("Agent ID: %s\nAccess password: %s\nHub: %s:%u\n", crd::format_id(stats.id).c_str(),
                    runtime.access_password().c_str(), cfg.hub_host.c_str(), cfg.hub_port);
    }
    t.join();
    crd::TcpSocket::cleanup();
    return 0;
}
#endif
