#ifdef _WIN32

#include "crd/cli.hpp"
#include "crd/log.hpp"
#include "crd/platform.hpp"
#include "crd/tcp_socket.hpp"
#include "viewer/viewer_window.hpp"

#include <objbase.h>
#include <shellapi.h>

#include <cstdio>
#include <string>
#include <vector>

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

int WINAPI wWinMain(HINSTANCE, HINSTANCE, PWSTR, int) {
    crd::log_set_prefix("viewer");
    enable_dpi();
    CoInitializeEx(nullptr, COINIT_MULTITHREADED);

    int argc = 0;
    LPWSTR* wargv = CommandLineToArgvW(GetCommandLineW(), &argc);
    std::vector<std::string> args;
    std::vector<char*> argv;
    args.reserve(static_cast<std::size_t>(argc));
    argv.reserve(static_cast<std::size_t>(argc));
    for (int i = 0; i < argc; ++i) {
        const int n = WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, nullptr, 0, nullptr, nullptr);
        std::string s(n > 0 ? static_cast<std::size_t>(n - 1) : 0, '\0');
        if (n > 1) {
            WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, s.data(), n, nullptr, nullptr);
        }
        args.push_back(std::move(s));
    }
    LocalFree(wargv);
    for (auto& a : args) {
        argv.push_back(a.data());
    }

    const crd::ViewerCli cli = crd::parse_viewer_cli(static_cast<int>(argv.size()), argv.data());
    if (cli.show_help) {
        MessageBoxW(nullptr,
                    L"Company Remote Desktop — Viewer\n\n"
                    L"viewer.exe [--host 192.168.1.10] [--port 5938] [--password SECRET]\n\n"
                    L"Connect to a training PC running host.exe.",
                    L"Viewer help", MB_OK | MB_ICONINFORMATION);
        CoUninitialize();
        return 0;
    }

    std::string sock_err;
    if (!crd::TcpSocket::startup(&sock_err)) {
        MessageBoxA(nullptr, sock_err.c_str(), "Viewer", MB_OK | MB_ICONERROR);
        CoUninitialize();
        return 1;
    }

    crd::ViewerWindow window;
    const int rc = window.run(cli);
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
    std::fprintf(stderr, "The viewer app is Windows-only.\n");
    return 1;
}
#endif
