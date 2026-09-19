#ifdef _WIN32

#include "agent/runtime.hpp"
#include "app/desktop_window.hpp"
#include "crd/cli.hpp"
#include "crd/config.hpp"
#include "crd/log.hpp"
#include "crd/platform.hpp"
#include "crd/tcp_socket.hpp"
#include "crd/util.hpp"
#include "host/desktop_capture.hpp"
#include "host/input_inject.hpp"

#include <objbase.h>
#include <shellapi.h>

#include <atomic>
#include <string>
#include <thread>
#include <vector>

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

std::vector<std::string> utf8_args() {
    int argc = 0;
    LPWSTR* wargv = CommandLineToArgvW(GetCommandLineW(), &argc);
    std::vector<std::string> args;
    args.reserve(static_cast<std::size_t>(argc));
    for (int i = 0; i < argc; ++i) {
        const int n = WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, nullptr, 0, nullptr, nullptr);
        std::string s(n > 0 ? static_cast<std::size_t>(n - 1) : 0, '\0');
        if (n > 1) {
            WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, s.data(), n, nullptr, nullptr);
        }
        args.push_back(std::move(s));
    }
    LocalFree(wargv);
    return args;
}

} // namespace

int WINAPI wWinMain(HINSTANCE, HINSTANCE, PWSTR, int) {
    crd::log_set_prefix("desktop");
    enable_dpi();
    CoInitializeEx(nullptr, COINIT_MULTITHREADED);

    auto args = utf8_args();
    std::vector<char*> argv;
    argv.reserve(args.size());
    for (auto& a : args) {
        argv.push_back(a.data());
    }

    crd::AppConfig file_cfg;
    crd::load_app_config(crd::default_config_path(), file_cfg);

    crd::ViewerCli vcli = crd::parse_viewer_cli(static_cast<int>(argv.size()), argv.data());
    if (!vcli.hub_from_cli) {
        vcli.hub_host = file_cfg.hub_host;
    }
    if (!vcli.port_from_cli) {
        vcli.port = file_cfg.hub_port;
    }

    crd::AgentConfig acfg;
    acfg.hub_host = vcli.hub_host;
    acfg.hub_port = vcli.port;
    acfg.identity_path = crd::default_identity_path();
    int quality = crd::kDefaultJpegQuality;
    int fps = crd::kDefaultFps;
    int scale = 100;
    for (int i = 1; i < static_cast<int>(argv.size()); ++i) {
        if (crd::arg_eq(argv[static_cast<std::size_t>(i)], "--unattended-password") &&
            i + 1 < static_cast<int>(argv.size())) {
            acfg.access_password = argv[static_cast<std::size_t>(++i)];
        } else if (crd::arg_eq(argv[static_cast<std::size_t>(i)], "--identity") && i + 1 < static_cast<int>(argv.size())) {
            acfg.identity_path = argv[static_cast<std::size_t>(++i)];
        } else if (crd::arg_eq(argv[static_cast<std::size_t>(i)], "--quality") && i + 1 < static_cast<int>(argv.size())) {
            crd::parse_int(argv[static_cast<std::size_t>(++i)], quality, 1, 100);
        } else if (crd::arg_eq(argv[static_cast<std::size_t>(i)], "--fps") && i + 1 < static_cast<int>(argv.size())) {
            crd::parse_int(argv[static_cast<std::size_t>(++i)], fps, 1, 60);
        } else if (crd::arg_eq(argv[static_cast<std::size_t>(i)], "--scale") && i + 1 < static_cast<int>(argv.size())) {
            crd::parse_int(argv[static_cast<std::size_t>(++i)], scale, 25, 100);
        }
    }
    acfg.jpeg_quality = quality;
    acfg.fps = fps;

    if (vcli.show_help) {
        MessageBoxW(nullptr,
                    L"Company Remote Desktop\n\n"
                    L"Opens one window: your ID (this PC) and Connect to a remote ID.\n"
                    L"Hub is hdd-land.com:5938 — you do not type it for normal use.\n\n"
                    L"CompanyRemoteDesktop.exe [--id 123456789] [--password SECRET]\n"
                    L"  [--hub hdd-land.com] [--hub-port 5938]  (optional override)\n",
                    L"Company Remote Desktop", MB_OK | MB_ICONINFORMATION);
        CoUninitialize();
        return 0;
    }

    std::string sock_err;
    if (!crd::TcpSocket::startup(&sock_err)) {
        MessageBoxA(nullptr, sock_err.c_str(), "Company Remote Desktop", MB_OK | MB_ICONERROR);
        CoUninitialize();
        return 1;
    }

    crd::WinJpegFrameSource source(quality, fps, scale);
    std::string cap_err;
    if (!source.start(&cap_err)) {
        MessageBoxA(nullptr, cap_err.c_str(), "Company Remote Desktop", MB_OK | MB_ICONERROR);
        crd::TcpSocket::cleanup();
        CoUninitialize();
        return 1;
    }
    crd::WinInputSink input;
    int fw = 0, fh = 0;
    source.desktop_size(fw, fh);
    input.set_frame_size(fw, fh);

    crd::AgentStats stats;
    crd::AgentRuntime runtime(acfg, source, input, stats);
    std::atomic<bool> stop{false};
    std::thread net([&] { runtime.run(stop); });

    crd::DesktopWindow window;
    const int rc = window.run(runtime, stats, vcli);

    stop.store(true);
    if (net.joinable()) {
        net.join();
    }
    source.stop();
    crd::TcpSocket::cleanup();
    CoUninitialize();
    return rc;
}

int main(int argc, char** argv) {
    (void)argc;
    (void)argv;
    return wWinMain(GetModuleHandleW(nullptr), nullptr, GetCommandLineW(), SW_SHOW);
}

#else
#include <cstdio>
int main() {
    std::fprintf(stderr, "CompanyRemoteDesktop is Windows-only. Run hub on Linux.\n");
    return 1;
}
#endif
