#ifdef _WIN32

#include "viewer/viewer_window.hpp"

#include "crd/log.hpp"
#include "crd/version.hpp"
#include "win/jpeg_wic.hpp"

#include <commctrl.h>
#include <cstdio>
#include <windowsx.h>

#pragma comment(lib, "comctl32.lib")
#pragma comment(lib, "gdi32.lib")
#pragma comment(lib, "user32.lib")

namespace crd {
namespace {

constexpr wchar_t kViewerClass[] = L"CrdViewerWindow";
constexpr int kToolbarH = 44;
constexpr int kStatusH = 24;
constexpr UINT WM_CRD_FRAME = WM_APP + 1;
constexpr UINT WM_CRD_STATUS = WM_APP + 2;
constexpr int IDC_HOST = 1001;
constexpr int IDC_PORT = 1002;
constexpr int IDC_PASS = 1003;
constexpr int IDC_CONNECT = 1004;

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

std::string edit_text(HWND hwnd) {
    const int n = GetWindowTextLengthW(hwnd);
    std::wstring w(n, L'\0');
    GetWindowTextW(hwnd, w.data(), n + 1);
    return wide_to_utf8(w);
}

void set_edit(HWND hwnd, const std::string& s) { SetWindowTextW(hwnd, utf8_to_wide(s).c_str()); }

} // namespace

int ViewerWindow::run(const ViewerCli& cli) {
    cli_ = cli;
    INITCOMMONCONTROLSEX icc{sizeof(icc), ICC_STANDARD_CLASSES};
    InitCommonControlsEx(&icc);
    if (!create()) {
        return 1;
    }
    if (!cli_.host.empty() && !cli_.password.empty()) {
        on_connect_clicked();
    }
    MSG msg{};
    while (GetMessageW(&msg, nullptr, 0, 0) > 0) {
        TranslateMessage(&msg);
        DispatchMessageW(&msg);
    }
    stop_session();
    return 0;
}

bool ViewerWindow::create() {
    WNDCLASSEXW wc{};
    wc.cbSize = sizeof(wc);
    wc.style = CS_HREDRAW | CS_VREDRAW;
    wc.lpfnWndProc = wnd_proc;
    wc.hInstance = GetModuleHandleW(nullptr);
    wc.hCursor = LoadCursor(nullptr, IDC_ARROW);
    wc.hbrBackground = reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1);
    wc.lpszClassName = kViewerClass;
    RegisterClassExW(&wc);

    const std::wstring title = utf8_to_wide(std::string(kProductName) + " — Viewer");
    hwnd_ = CreateWindowExW(0, kViewerClass, title.c_str(), WS_OVERLAPPEDWINDOW | WS_CLIPCHILDREN, CW_USEDEFAULT,
                            CW_USEDEFAULT, 1280, 800, nullptr, nullptr, wc.hInstance, this);
    if (!hwnd_) {
        return false;
    }

    auto make_edit = [&](int id, int x, int w, const wchar_t* text, bool password) {
        DWORD style = WS_CHILD | WS_VISIBLE | WS_BORDER | ES_AUTOHSCROLL | ES_LEFT;
        if (password) {
            style |= ES_PASSWORD;
        }
        HWND h = CreateWindowExW(WS_EX_CLIENTEDGE, L"EDIT", text, style, x, 8, w, 26, hwnd_,
                                 reinterpret_cast<HMENU>(static_cast<INT_PTR>(id)), wc.hInstance, nullptr);
        SendMessageW(h, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);
        return h;
    };

    CreateWindowExW(0, L"STATIC", L"Host", WS_CHILD | WS_VISIBLE, 12, 12, 36, 20, hwnd_, nullptr, wc.hInstance, nullptr);
    host_edit_ = make_edit(IDC_HOST, 50, 180, L"192.168.1.10", false);
    CreateWindowExW(0, L"STATIC", L"Port", WS_CHILD | WS_VISIBLE, 240, 12, 32, 20, hwnd_, nullptr, wc.hInstance,
                    nullptr);
    port_edit_ = make_edit(IDC_PORT, 274, 60, L"5938", false);
    CreateWindowExW(0, L"STATIC", L"Password", WS_CHILD | WS_VISIBLE, 344, 12, 64, 20, hwnd_, nullptr, wc.hInstance,
                    nullptr);
    pass_edit_ = make_edit(IDC_PASS, 410, 160, L"", true);
    connect_btn_ = CreateWindowExW(0, L"BUTTON", L"Connect", WS_CHILD | WS_VISIBLE | BS_DEFPUSHBUTTON, 584, 8, 100, 28,
                                   hwnd_, reinterpret_cast<HMENU>(static_cast<INT_PTR>(IDC_CONNECT)), wc.hInstance,
                                   nullptr);
    SendMessageW(connect_btn_, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);

    status_ = CreateWindowExW(0, L"STATIC", L"Disconnected", WS_CHILD | WS_VISIBLE | SS_LEFT, 12, 0, 400, 20, hwnd_,
                              nullptr, wc.hInstance, nullptr);
    SendMessageW(status_, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);

    if (!cli_.host.empty()) {
        set_edit(host_edit_, cli_.host);
    }
    set_edit(port_edit_, std::to_string(cli_.port));
    if (!cli_.password.empty()) {
        set_edit(pass_edit_, cli_.password);
    }

    layout_controls();
    ShowWindow(hwnd_, SW_SHOW);
    UpdateWindow(hwnd_);
    return true;
}

void ViewerWindow::layout_controls() {
    RECT rc{};
    GetClientRect(hwnd_, &rc);
    if (status_) {
        SetWindowPos(status_, nullptr, 12, rc.bottom - kStatusH + 2, rc.right - 24, 20, SWP_NOZORDER);
    }
}

RECT ViewerWindow::view_rect() const {
    RECT rc{};
    GetClientRect(hwnd_, &rc);
    rc.top = kToolbarH;
    rc.bottom -= kStatusH;
    if (rc.bottom < rc.top) {
        rc.bottom = rc.top;
    }
    return rc;
}

void ViewerWindow::set_status(const std::string& text) {
    status_text_ = text;
    if (hwnd_) {
        SetWindowTextW(status_, utf8_to_wide(text).c_str());
    }
}

void ViewerWindow::on_connect_clicked() {
    if (connected_.load() || running_.load()) {
        stop_session();
        SetWindowTextW(connect_btn_, L"Connect");
        set_status("Disconnected");
        return;
    }
    cli_.host = edit_text(host_edit_);
    const std::string port_s = edit_text(port_edit_);
    parse_u16(port_s.c_str(), cli_.port);
    cli_.password = edit_text(pass_edit_);
    if (cli_.host.empty() || cli_.password.empty()) {
        set_status("Enter host IP and password");
        return;
    }
    start_session();
}

void ViewerWindow::start_session() {
    stop_session();
    running_.store(true);
    SetWindowTextW(connect_btn_, L"Disconnect");
    set_status("Connecting to " + cli_.host + ":" + std::to_string(cli_.port) + " ...");
    net_thread_ = std::thread(&ViewerWindow::net_loop, this);
}

void ViewerWindow::stop_session() {
    running_.store(false);
    client_.disconnect();
    connected_.store(false);
    if (net_thread_.joinable()) {
        if (std::this_thread::get_id() != net_thread_.get_id()) {
            net_thread_.join();
        }
    }
}

void ViewerWindow::net_loop() {
    HelloServer info{};
    std::string err;
    if (!client_.connect(cli_.host, cli_.port, cli_.password, info, &err)) {
        running_.store(false);
        connected_.store(false);
        if (hwnd_) {
            auto* text = new std::string("Connect failed: " + err);
            PostMessageW(hwnd_, WM_CRD_STATUS, 0, reinterpret_cast<LPARAM>(text));
        }
        return;
    }
    connected_.store(true);
    remote_w_ = static_cast<int>(info.desktop_width);
    remote_h_ = static_cast<int>(info.desktop_height);
    if (hwnd_) {
        auto* text = new std::string("Connected — " + std::to_string(remote_w_) + "x" + std::to_string(remote_h_));
        PostMessageW(hwnd_, WM_CRD_STATUS, 1, reinterpret_cast<LPARAM>(text));
    }

    while (running_.load() && client_.connected()) {
        IncomingFrame incoming;
        if (!client_.poll(incoming, 50)) {
            if (incoming.disconnected) {
                break;
            }
            continue;
        }
        if (incoming.has_frame) {
            std::vector<std::uint8_t> bgra;
            int w = 0, h = 0;
            if (jpeg_decode_bgra(incoming.frame.bytes.data(), incoming.frame.bytes.size(), bgra, w, h)) {
                {
                    std::lock_guard<std::mutex> lock(frame_mu_);
                    bgra_ = std::move(bgra);
                    frame_w_ = w;
                    frame_h_ = h;
                    remote_w_ = w;
                    remote_h_ = h;
                }
                if (hwnd_) {
                    PostMessageW(hwnd_, WM_CRD_FRAME, 0, 0);
                }
            }
        }
    }

    connected_.store(false);
    running_.store(false);
    if (hwnd_) {
        auto* text = new std::string("Disconnected");
        PostMessageW(hwnd_, WM_CRD_STATUS, 0, reinterpret_cast<LPARAM>(text));
    }
}

bool ViewerWindow::map_to_remote(int client_x, int client_y, int& rx, int& ry) const {
    const RECT vr = view_rect();
    if (client_x < vr.left || client_y < vr.top || client_x >= vr.right || client_y >= vr.bottom) {
        return false;
    }
    int fw = 0, fh = 0;
    {
        std::lock_guard<std::mutex> lock(frame_mu_);
        fw = frame_w_;
        fh = frame_h_;
    }
    if (fw <= 0 || fh <= 0) {
        fw = remote_w_;
        fh = remote_h_;
    }
    if (fw <= 0 || fh <= 0) {
        return false;
    }
    const int vw = vr.right - vr.left;
    const int vh = vr.bottom - vr.top;
    if (vw <= 0 || vh <= 0) {
        return false;
    }
    rx = (client_x - vr.left) * fw / vw;
    ry = (client_y - vr.top) * fh / vh;
    if (rx < 0) {
        rx = 0;
    }
    if (ry < 0) {
        ry = 0;
    }
    if (rx >= fw) {
        rx = fw - 1;
    }
    if (ry >= fh) {
        ry = fh - 1;
    }
    return true;
}

void ViewerWindow::forward_mouse(UINT msg, WPARAM wparam, LPARAM lparam) {
    if (!connected_.load()) {
        return;
    }
    const int x = GET_X_LPARAM(lparam);
    const int y = GET_Y_LPARAM(lparam);
    int rx = 0, ry = 0;
    if (!map_to_remote(x, y, rx, ry) && msg == WM_MOUSEMOVE) {
        return;
    }
    if (rx == 0 && ry == 0 && !map_to_remote(x, y, rx, ry) && msg != WM_MOUSEWHEEL) {
        return;
    }

    MouseEvent ev{};
    ev.x = static_cast<std::int16_t>(rx);
    ev.y = static_cast<std::int16_t>(ry);
    ev.flags = static_cast<std::uint8_t>(MouseFlags::Move);

    switch (msg) {
    case WM_LBUTTONDOWN:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::LeftDown);
        SetCapture(hwnd_);
        SetFocus(hwnd_);
        break;
    case WM_LBUTTONUP:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::LeftUp);
        ReleaseCapture();
        break;
    case WM_RBUTTONDOWN:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::RightDown);
        break;
    case WM_RBUTTONUP:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::RightUp);
        break;
    case WM_MBUTTONDOWN:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::MiddleDown);
        break;
    case WM_MBUTTONUP:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::MiddleUp);
        break;
    case WM_MOUSEWHEEL:
        ev.flags |= static_cast<std::uint8_t>(MouseFlags::Wheel);
        ev.wheel = static_cast<std::int16_t>(GET_WHEEL_DELTA_WPARAM(wparam));
        break;
    default:
        break;
    }
    client_.send_mouse(ev);
}

void ViewerWindow::forward_key(UINT msg, WPARAM wparam, LPARAM lparam) {
    if (!connected_.load()) {
        return;
    }
    const HWND focus = GetFocus();
    if (focus == host_edit_ || focus == port_edit_ || focus == pass_edit_) {
        return;
    }
    KeyEvent ev{};
    ev.vk = static_cast<std::uint16_t>(wparam);
    ev.down = (msg == WM_KEYDOWN || msg == WM_SYSKEYDOWN) ? 1 : 0;
    ev.extended = (lparam & (1 << 24)) ? 1 : 0;
    client_.send_key(ev);
}

void ViewerWindow::paint_remote(HDC hdc) {
    RECT vr = view_rect();
    FillRect(hdc, &vr, reinterpret_cast<HBRUSH>(GetStockObject(DKGRAY_BRUSH)));

    std::vector<std::uint8_t> copy;
    int w = 0, h = 0;
    {
        std::lock_guard<std::mutex> lock(frame_mu_);
        copy = bgra_;
        w = frame_w_;
        h = frame_h_;
    }
    if (copy.empty() || w <= 0 || h <= 0) {
        SetBkMode(hdc, TRANSPARENT);
        SetTextColor(hdc, RGB(220, 220, 220));
        const wchar_t* hint = L"Connect to a training PC host to view the remote desktop.";
        DrawTextW(hdc, hint, -1, &vr, DT_CENTER | DT_VCENTER | DT_SINGLELINE);
        return;
    }

    BITMAPINFO bmi{};
    bmi.bmiHeader.biSize = sizeof(BITMAPINFOHEADER);
    bmi.bmiHeader.biWidth = w;
    bmi.bmiHeader.biHeight = -h;
    bmi.bmiHeader.biPlanes = 1;
    bmi.bmiHeader.biBitCount = 32;
    bmi.bmiHeader.biCompression = BI_RGB;
    StretchDIBits(hdc, vr.left, vr.top, vr.right - vr.left, vr.bottom - vr.top, 0, 0, w, h, copy.data(), &bmi,
                  DIB_RGB_COLORS, SRCCOPY);
}

LRESULT CALLBACK ViewerWindow::wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam) {
    ViewerWindow* self = nullptr;
    if (msg == WM_NCCREATE) {
        auto* cs = reinterpret_cast<CREATESTRUCTW*>(lparam);
        self = static_cast<ViewerWindow*>(cs->lpCreateParams);
        SetWindowLongPtrW(hwnd, GWLP_USERDATA, reinterpret_cast<LONG_PTR>(self));
        self->hwnd_ = hwnd;
    } else {
        self = reinterpret_cast<ViewerWindow*>(GetWindowLongPtrW(hwnd, GWLP_USERDATA));
    }
    if (!self) {
        return DefWindowProcW(hwnd, msg, wparam, lparam);
    }

    switch (msg) {
    case WM_COMMAND:
        if (LOWORD(wparam) == IDC_CONNECT) {
            self->on_connect_clicked();
            return 0;
        }
        break;
    case WM_SIZE:
        self->layout_controls();
        return 0;
    case WM_ERASEBKGND:
        return 1;
    case WM_PAINT: {
        PAINTSTRUCT ps{};
        HDC hdc = BeginPaint(hwnd, &ps);
        RECT rc{};
        GetClientRect(hwnd, &rc);
        RECT toolbar{0, 0, rc.right, kToolbarH};
        FillRect(hdc, &toolbar, reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1));
        RECT statusbar{0, rc.bottom - kStatusH, rc.right, rc.bottom};
        FillRect(hdc, &statusbar, reinterpret_cast<HBRUSH>(COLOR_BTNFACE + 1));
        self->paint_remote(hdc);
        EndPaint(hwnd, &ps);
        return 0;
    }
    case WM_CRD_FRAME:
        InvalidateRect(hwnd, nullptr, FALSE);
        return 0;
    case WM_CRD_STATUS: {
        auto* text = reinterpret_cast<std::string*>(lparam);
        if (text) {
            self->set_status(*text);
            if (wparam == 0) {
                SetWindowTextW(self->connect_btn_, L"Connect");
            }
            delete text;
        }
        return 0;
    }
    case WM_LBUTTONDOWN:
    case WM_LBUTTONUP:
    case WM_RBUTTONDOWN:
    case WM_RBUTTONUP:
    case WM_MBUTTONDOWN:
    case WM_MBUTTONUP:
    case WM_MOUSEMOVE:
        self->forward_mouse(msg, wparam, lparam);
        return 0;
    case WM_MOUSEWHEEL:
        self->forward_mouse(msg, wparam, lparam);
        return 0;
    case WM_KEYDOWN:
    case WM_KEYUP:
    case WM_SYSKEYDOWN:
    case WM_SYSKEYUP:
        if (wparam == VK_F4) {
            break;
        }
        self->forward_key(msg, wparam, lparam);
        return 0;
    case WM_DESTROY:
        self->stop_session();
        PostQuitMessage(0);
        return 0;
    default:
        break;
    }
    return DefWindowProcW(hwnd, msg, wparam, lparam);
}

} // namespace crd

#endif
