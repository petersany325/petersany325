#ifdef _WIN32

#include "host/host_window.hpp"

#include "crd/version.hpp"

#include <cstdio>
#include <string>

namespace crd {
namespace {

constexpr wchar_t kHostClass[] = L"CrdHostStatusWindow";
constexpr UINT kTimerId = 1;

std::wstring utf8_to_wide(const std::string& s) {
    if (s.empty()) {
        return {};
    }
    const int n = MultiByteToWideChar(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), nullptr, 0);
    std::wstring out(n, L'\0');
    MultiByteToWideChar(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), out.data(), n);
    return out;
}

} // namespace

bool HostWindow::create(const HostCli& cli, const std::string& password, HostStats& stats, const std::string& backend) {
    cli_ = cli;
    password_ = password;
    stats_ = &stats;
    backend_ = backend;

    WNDCLASSEXW wc{};
    wc.cbSize = sizeof(wc);
    wc.lpfnWndProc = wnd_proc;
    wc.hInstance = GetModuleHandleW(nullptr);
    wc.hCursor = LoadCursor(nullptr, IDC_ARROW);
    wc.hbrBackground = reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1);
    wc.lpszClassName = kHostClass;
    RegisterClassExW(&wc);

    const std::wstring title = utf8_to_wide(std::string(kProductName) + " — Host");
    hwnd_ = CreateWindowExW(0, kHostClass, title.c_str(), WS_OVERLAPPED | WS_CAPTION | WS_SYSMENU | WS_MINIMIZEBOX,
                            CW_USEDEFAULT, CW_USEDEFAULT, 520, 280, nullptr, nullptr, wc.hInstance, this);
    if (!hwnd_) {
        return false;
    }
    ShowWindow(hwnd_, SW_SHOW);
    UpdateWindow(hwnd_);
    SetTimer(hwnd_, kTimerId, 400, nullptr);
    return true;
}

int HostWindow::message_loop(std::atomic<bool>& stop) {
    MSG msg{};
    while (!stop.load()) {
        const BOOL rc = GetMessageW(&msg, nullptr, 0, 0);
        if (rc <= 0) {
            stop.store(true);
            break;
        }
        TranslateMessage(&msg);
        DispatchMessageW(&msg);
    }
    return 0;
}

LRESULT CALLBACK HostWindow::wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam) {
    HostWindow* self = nullptr;
    if (msg == WM_NCCREATE) {
        auto* cs = reinterpret_cast<CREATESTRUCTW*>(lparam);
        self = static_cast<HostWindow*>(cs->lpCreateParams);
        SetWindowLongPtrW(hwnd, GWLP_USERDATA, reinterpret_cast<LONG_PTR>(self));
        self->hwnd_ = hwnd;
    } else {
        self = reinterpret_cast<HostWindow*>(GetWindowLongPtrW(hwnd, GWLP_USERDATA));
    }

    if (!self) {
        return DefWindowProcW(hwnd, msg, wparam, lparam);
    }

    switch (msg) {
    case WM_TIMER:
        InvalidateRect(hwnd, nullptr, FALSE);
        return 0;
    case WM_PAINT: {
        PAINTSTRUCT ps{};
        HDC hdc = BeginPaint(hwnd, &ps);
        self->paint(hdc);
        EndPaint(hwnd, &ps);
        return 0;
    }
    case WM_DESTROY:
        KillTimer(hwnd, kTimerId);
        PostQuitMessage(0);
        return 0;
    default:
        break;
    }
    return DefWindowProcW(hwnd, msg, wparam, lparam);
}

void HostWindow::paint(HDC hdc) {
    RECT rc{};
    GetClientRect(hwnd_, &rc);
    FillRect(hdc, &rc, reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1));

    HFONT font = CreateFontW(18, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE, DEFAULT_CHARSET, OUT_DEFAULT_PRECIS,
                             CLIP_DEFAULT_PRECIS, CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_DONTCARE, L"Segoe UI");
    HFONT bold = CreateFontW(22, 0, 0, 0, FW_SEMIBOLD, FALSE, FALSE, FALSE, DEFAULT_CHARSET, OUT_DEFAULT_PRECIS,
                             CLIP_DEFAULT_PRECIS, CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_DONTCARE, L"Segoe UI");
    HGDIOBJ old = SelectObject(hdc, bold);
    SetBkMode(hdc, TRANSPARENT);
    SetTextColor(hdc, RGB(20, 40, 70));

    const std::wstring title = utf8_to_wide(std::string(kProductName) + "  v" + kProductVersion);
    TextOutW(hdc, 20, 16, title.c_str(), static_cast<int>(title.size()));

    SelectObject(hdc, font);
    SetTextColor(hdc, RGB(30, 30, 30));

    std::string viewer = "(waiting)";
    if (stats_) {
        std::lock_guard<std::mutex> lock(stats_->ip_mu);
        if (!stats_->viewer_ip.empty()) {
            viewer = stats_->viewer_ip;
        }
    }

    char lines[8][160];
    std::snprintf(lines[0], sizeof(lines[0]), "Listen:    %s:%u", cli_.bind_ip.c_str(), cli_.port);
    std::snprintf(lines[1], sizeof(lines[1]), "Password:  %s", password_.c_str());
    std::snprintf(lines[2], sizeof(lines[2]), "Capture:   %s   JPEG q=%d  fps=%d  scale=%d%%", backend_.c_str(),
                  cli_.quality, cli_.fps, cli_.scale_percent);
    std::snprintf(lines[3], sizeof(lines[3]), "Viewer:    %s",
                  (stats_ && stats_->connected.load()) ? viewer.c_str() : "waiting for one trainee/instructor");
    std::snprintf(lines[4], sizeof(lines[4]), "Desktop:   %ux%u", stats_ ? stats_->desktop_w.load() : 0,
                  stats_ ? stats_->desktop_h.load() : 0);
    std::snprintf(lines[5], sizeof(lines[5]), "Streamed:  %llu frames / %llu KB",
                  stats_ ? static_cast<unsigned long long>(stats_->frames_sent.load()) : 0ull,
                  stats_ ? static_cast<unsigned long long>(stats_->bytes_sent.load() / 1024) : 0ull);
    std::snprintf(lines[6], sizeof(lines[6]), "Close this window to stop the host.");

    int y = 56;
    for (int i = 0; i < 7; ++i) {
        const std::wstring w = utf8_to_wide(lines[i]);
        TextOutW(hdc, 20, y, w.c_str(), static_cast<int>(w.size()));
        y += 26;
    }

    SelectObject(hdc, old);
    DeleteObject(font);
    DeleteObject(bold);
}

} // namespace crd

#endif
