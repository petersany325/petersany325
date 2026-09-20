#include "app.hpp"

#include <cstring>
#include <string>
#include <vector>

#ifdef _WIN32
#ifndef NOMINMAX
#define NOMINMAX
#endif
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#include <shellapi.h>
#include <io.h>
#include <fcntl.h>
#include <stdio.h>
#endif

namespace {

bool wants_console(int argc, char** argv) {
    for (int i = 1; i < argc; ++i) {
        if (std::strcmp(argv[i], "--cli") == 0 || std::strcmp(argv[i], "--self-test") == 0 ||
            std::strcmp(argv[i], "--help") == 0 || std::strcmp(argv[i], "-h") == 0 ||
            std::strcmp(argv[i], "--script") == 0)
            return true;
    }
    return false;
}

int app_main(int argc, char** argv) {
    if (wants_console(argc, argv)) return hsc::run_cli(argc, argv);
    return hsc::run_gui(argc, argv);
}

#ifdef _WIN32
void attach_cli_console() {
    if (!AttachConsole(ATTACH_PARENT_PROCESS)) AllocConsole();
#ifdef _MSC_VER
    FILE* fp = nullptr;
    freopen_s(&fp, "CONOUT$", "w", stdout);
    freopen_s(&fp, "CONOUT$", "w", stderr);
    freopen_s(&fp, "CONIN$", "r", stdin);
#else
    (void)freopen("CONOUT$", "w", stdout);
    (void)freopen("CONOUT$", "w", stderr);
    (void)freopen("CONIN$", "r", stdin);
#endif
}

std::vector<std::string> utf8_args() {
    int argc = 0;
    LPWSTR* wargv = CommandLineToArgvW(GetCommandLineW(), &argc);
    std::vector<std::string> out;
    if (!wargv) return out;
    for (int i = 0; i < argc; ++i) {
        int n = WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, nullptr, 0, nullptr, nullptr);
        std::string s(static_cast<size_t>(n > 0 ? n - 1 : 0), '\0');
        if (n > 1) WideCharToMultiByte(CP_UTF8, 0, wargv[i], -1, s.data(), n, nullptr, nullptr);
        out.push_back(std::move(s));
    }
    LocalFree(wargv);
    return out;
}
#endif

}  // namespace

#ifdef _WIN32
int WINAPI WinMain(HINSTANCE, HINSTANCE, LPSTR, int) {
    auto args = utf8_args();
    std::vector<char*> argv;
    argv.reserve(args.size());
    for (auto& s : args) argv.push_back(s.data());
    int argc = static_cast<int>(argv.size());
    if (wants_console(argc, argv.data())) attach_cli_console();
    return app_main(argc, argv.data());
}

// MinGW/MSVC console fallback when the linker keeps a main CRT entry.
int main(int argc, char** argv) { return app_main(argc, argv); }
#else
int main(int argc, char** argv) { return app_main(argc, argv); }
#endif
