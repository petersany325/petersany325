#ifdef _WIN32

#include "agent/agent_window.hpp"

#include "crd/util.hpp"
#include "crd/version.hpp"

#include <cstdio>
#include <cstring>

#pragma comment(lib, "user32.lib")
#pragma comment(lib, "gdi32.lib")

namespace crd {
namespace {

constexpr wchar_t kClass[] = L"CrdAgentWindow";
constexpr UINT kTimerId = 1;
constexpr int IDC_PASS = 2001;
constexpr int IDC_APPLY = 2002;
constexpr int IDC_COPY = 2003;

std::wstring utf8_to_wide(const std::string& s) {
    if (s.empty()) {
        return {};
    }
    const int n = MultiByteToWideChar(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), nullptr, 0);
    std::wstring out(n, L'\0');
    MultiByteToWideChar(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), out.data(), n);
    return out;
}

std::string wide_to_utf8(const std::wstring& s) {
    if (s.empty()) {
        return {};
    }
    const int n = WideCharToMultiByte(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), nullptr, 0, nullptr, nullptr);
    std::string out(n, '\0');
    WideCharToMultiByte(CP_UTF8, 0, s.c_str(), static_cast<int>(s.size()), out.data(), n, nullptr, nullptr);
    return out;
}

} // namespace

bool AgentWindow::create(AgentRuntime& runtime, AgentStats& stats, const std::string& backend, const std::string& hub) {
    runtime_ = &runtime;
    stats_ = &stats;
    backend_ = backend;
    hub_ = hub;

    WNDCLASSEXW wc{};
    wc.cbSize = sizeof(wc);
    wc.lpfnWndProc = wnd_proc;
    wc.hInstance = GetModuleHandleW(nullptr);
    wc.hCursor = LoadCursor(nullptr, IDC_ARROW);
    wc.hbrBackground = reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1);
    wc.lpszClassName = kClass;
    RegisterClassExW(&wc);

    const std::wstring title = utf8_to_wide(std::string(kProductName) + " — Agent");
    hwnd_ = CreateWindowExW(0, kClass, title.c_str(), WS_OVERLAPPED | WS_CAPTION | WS_SYSMENU | WS_MINIMIZEBOX,
                            CW_USEDEFAULT, CW_USEDEFAULT, 560, 420, nullptr, nullptr, wc.hInstance, this);
    if (!hwnd_) {
        return false;
    }

    CreateWindowExW(0, L"STATIC", L"Access password", WS_CHILD | WS_VISIBLE, 24, 250, 120, 20, hwnd_, nullptr,
                    wc.hInstance, nullptr);
    pass_edit_ = CreateWindowExW(WS_EX_CLIENTEDGE, L"EDIT", utf8_to_wide(runtime.access_password()).c_str(),
                                 WS_CHILD | WS_VISIBLE | ES_AUTOHSCROLL, 24, 272, 280, 26, hwnd_,
                                 reinterpret_cast<HMENU>(static_cast<INT_PTR>(IDC_PASS)), wc.hInstance, nullptr);
    apply_btn_ = CreateWindowExW(0, L"BUTTON", L"Save password", WS_CHILD | WS_VISIBLE, 316, 270, 120, 28, hwnd_,
                                 reinterpret_cast<HMENU>(static_cast<INT_PTR>(IDC_APPLY)), wc.hInstance, nullptr);
    copy_btn_ = CreateWindowExW(0, L"BUTTON", L"Copy ID", WS_CHILD | WS_VISIBLE, 400, 88, 100, 32, hwnd_,
                                reinterpret_cast<HMENU>(static_cast<INT_PTR>(IDC_COPY)), wc.hInstance, nullptr);
    SendMessageW(pass_edit_, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);
    SendMessageW(apply_btn_, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);
    SendMessageW(copy_btn_, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);

    ShowWindow(hwnd_, SW_SHOW);
    UpdateWindow(hwnd_);
    SetTimer(hwnd_, kTimerId, 400, nullptr);
    return true;
}

int AgentWindow::message_loop(std::atomic<bool>& stop) {
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

void AgentWindow::copy_id() {
    if (!stats_) {
        return;
    }
    std::string id;
    {
        std::lock_guard<std::mutex> lock(stats_->mu);
        id = format_id(stats_->id);
    }
    if (id.empty()) {
        return;
    }
    const std::wstring w = utf8_to_wide(id);
    if (!OpenClipboard(hwnd_)) {
        return;
    }
    EmptyClipboard();
    const HGLOBAL mem = GlobalAlloc(GMEM_MOVEABLE, (w.size() + 1) * sizeof(wchar_t));
    if (mem) {
        void* p = GlobalLock(mem);
        if (p) {
            std::memcpy(p, w.c_str(), (w.size() + 1) * sizeof(wchar_t));
            GlobalUnlock(mem);
            SetClipboardData(CF_UNICODETEXT, mem);
        }
    }
    CloseClipboard();
}

void AgentWindow::apply_password() {
    if (!runtime_ || !pass_edit_) {
        return;
    }
    const int n = GetWindowTextLengthW(pass_edit_);
    std::wstring w(n, L'\0');
    GetWindowTextW(pass_edit_, w.data(), n + 1);
    runtime_->set_access_password(wide_to_utf8(w));
}

void AgentWindow::paint(HDC hdc) {
    RECT rc{};
    GetClientRect(hwnd_, &rc);
    FillRect(hdc, &rc, reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1));

    HFONT title = CreateFontW(22, 0, 0, 0, FW_SEMIBOLD, FALSE, FALSE, FALSE, DEFAULT_CHARSET, OUT_DEFAULT_PRECIS,
                              CLIP_DEFAULT_PRECIS, CLEARTYPE_QUALITY, DEFAULT_PITCH, L"Segoe UI");
    HFONT huge = CreateFontW(40, 0, 0, 0, FW_BOLD, FALSE, FALSE, FALSE, DEFAULT_CHARSET, OUT_DEFAULT_PRECIS,
                             CLIP_DEFAULT_PRECIS, CLEARTYPE_QUALITY, DEFAULT_PITCH, L"Segoe UI");
    HFONT body = CreateFontW(18, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE, DEFAULT_CHARSET, OUT_DEFAULT_PRECIS,
                             CLIP_DEFAULT_PRECIS, CLEARTYPE_QUALITY, DEFAULT_PITCH, L"Segoe UI");

    SetBkMode(hdc, TRANSPARENT);
    HGDIOBJ old = SelectObject(hdc, title);
    SetTextColor(hdc, RGB(20, 40, 70));
    const std::wstring header = utf8_to_wide(std::string(kProductName) + "  v" + kProductVersion);
    TextOutW(hdc, 24, 16, header.c_str(), static_cast<int>(header.size()));

    std::string id = "(registering…)";
    std::string err;
    if (stats_) {
        std::lock_guard<std::mutex> lock(stats_->mu);
        if (!stats_->id.empty()) {
            id = format_id(stats_->id);
        }
        err = stats_->last_error;
    }
    SelectObject(hdc, huge);
    SetTextColor(hdc, RGB(10, 80, 160));
    const std::wstring wid = utf8_to_wide("This PC:  " + id);
    TextOutW(hdc, 24, 80, wid.c_str(), static_cast<int>(wid.size()));

    SelectObject(hdc, body);
    SetTextColor(hdc, RGB(30, 30, 30));
    const char* state = "Connecting to Hub…";
    if (stats_ && stats_->in_session.load()) {
        state = "In session — a viewer is connected";
    } else if (stats_ && stats_->online.load()) {
        state = "Online — waiting for a viewer";
    }
    char lines[6][200];
    std::snprintf(lines[0], sizeof(lines[0]), "Status:    %s", state);
    std::snprintf(lines[1], sizeof(lines[1]), "Hub:       %s", hub_.c_str());
    std::snprintf(lines[2], sizeof(lines[2]), "Capture:   %s", backend_.c_str());
    std::snprintf(lines[3], sizeof(lines[3]), "Desktop:   %ux%u   streamed %llu frames",
                  stats_ ? stats_->desktop_w.load() : 0, stats_ ? stats_->desktop_h.load() : 0,
                  stats_ ? static_cast<unsigned long long>(stats_->frames_sent.load()) : 0ull);
    std::snprintf(lines[4], sizeof(lines[4]), "Give this ID + access password to the instructor. They connect in Viewer.");
    if (!err.empty()) {
        std::snprintf(lines[5], sizeof(lines[5]), "Note:      %s", err.c_str());
    } else {
        lines[5][0] = 0;
    }
    int y = 140;
    for (int i = 0; i < 6; ++i) {
        if (!lines[i][0]) {
            continue;
        }
        const std::wstring w = utf8_to_wide(lines[i]);
        TextOutW(hdc, 24, y, w.c_str(), static_cast<int>(w.size()));
        y += 22;
    }

    SelectObject(hdc, old);
    DeleteObject(title);
    DeleteObject(huge);
    DeleteObject(body);
}

LRESULT CALLBACK AgentWindow::wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam) {
    AgentWindow* self = nullptr;
    if (msg == WM_NCCREATE) {
        auto* cs = reinterpret_cast<CREATESTRUCTW*>(lparam);
        self = static_cast<AgentWindow*>(cs->lpCreateParams);
        SetWindowLongPtrW(hwnd, GWLP_USERDATA, reinterpret_cast<LONG_PTR>(self));
        self->hwnd_ = hwnd;
    } else {
        self = reinterpret_cast<AgentWindow*>(GetWindowLongPtrW(hwnd, GWLP_USERDATA));
    }
    if (!self) {
        return DefWindowProcW(hwnd, msg, wparam, lparam);
    }
    switch (msg) {
    case WM_COMMAND:
        if (LOWORD(wparam) == IDC_COPY) {
            self->copy_id();
            return 0;
        }
        if (LOWORD(wparam) == IDC_APPLY) {
            self->apply_password();
            return 0;
        }
        break;
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

} // namespace crd

#endif
