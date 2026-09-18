#ifdef _WIN32

#include "crd/cli.hpp"
#include "crd/log.hpp"
#include "crd/platform.hpp"
#include "crd/random.hpp"
#include "crd/session.hpp"
#include "crd/tcp_socket.hpp"
#include "host/desktop_capture.hpp"
#include "host/host_window.hpp"
#include "host/input_inject.hpp"

#include <objbase.h>

#include <atomic>
#include <cstdio>
#include <thread>

#pragma comment(lib, "user32.lib")

namespace {

void enable_dpi() {
    HMODULE user = GetModuleHandleW(L"user32.dll");
    if (!user) {
        return;
    }
    using Fn = BOOL(WINAPI*)(HANDLE);
    auto set = reinterpret_cast<Fn>(GetProcAddress(user, "SetProcessDpiAwarenessContext"));
    if (set) {
        set(reinterpret_cast<HANDLE>(static_cast<intptr_t>(-4))); // PER_MONITOR_AWARE_V2
    }
}

} // namespace

int main(int argc, char** argv) {
    crd::log_set_prefix("host");
    const crd::HostCli cli = crd::parse_host_cli(argc, argv);
    if (cli.show_help) {
        std::printf("Company Remote Desktop — Host\n\n");
        std::printf("  host.exe [--port 5938] [--bind 0.0.0.0] [--password SECRET]\n");
        std::printf("           [--quality 62] [--fps 15] [--scale 100]\n\n");
        std::printf("Run on the training PC. Accepts one viewer at a time.\n");
        return 0;
    }

    enable_dpi();
    CoInitializeEx(nullptr, COINIT_MULTITHREADED);

    std::string sock_err;
    if (!crd::TcpSocket::startup(&sock_err)) {
        crd::log_error("%s", sock_err.c_str());
        return 1;
    }

    std::string password = cli.password;
    if (password.empty()) {
        password = crd::random_password(8);
        crd::log_info("generated session password: %s", password.c_str());
    }

    crd::WinJpegFrameSource source(cli.quality, cli.fps, cli.scale_percent);
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
    crd::HostStats stats;
    crd::HostConfig cfg;
    cfg.bind_ip = cli.bind_ip;
    cfg.port = cli.port;
    cfg.password = password;
    cfg.jpeg_quality = cli.quality;
    cfg.fps = cli.fps;

    std::atomic<bool> stop{false};
    crd::HostServer server(cfg, source, input, stats);
    std::thread net([&] { server.run(stop); });

    crd::HostWindow window;
    if (!window.create(cli, password, stats, source.backend())) {
        crd::log_error("failed to create status window");
        stop.store(true);
        net.join();
        source.stop();
        crd::TcpSocket::cleanup();
        return 1;
    }

    crd::log_info("host ready — port %u password '%s'", cli.port, password.c_str());
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
#include <cstdio>
int main() {
    std::fprintf(stderr, "The host app is Windows-only (DXGI / SendInput).\n");
    return 1;
}
#endif
