#ifdef _WIN32

#include "host/input_inject.hpp"

#include "crd/log.hpp"
#include "crd/platform.hpp"

#include <algorithm>

#pragma comment(lib, "user32.lib")

namespace crd {
namespace {

void send_mouse_input(LONG dx, LONG dy, DWORD flags, DWORD extra = 0) {
    INPUT in{};
    in.type = INPUT_MOUSE;
    in.mi.dx = dx;
    in.mi.dy = dy;
    in.mi.dwFlags = flags;
    in.mi.mouseData = extra;
    SendInput(1, &in, sizeof(INPUT));
}

void desktop_to_absolute(int x, int y, LONG& ax, LONG& ay) {
    const int w = GetSystemMetrics(SM_CXSCREEN);
    const int h = GetSystemMetrics(SM_CYSCREEN);
    const int sw = std::max(1, w - 1);
    const int sh = std::max(1, h - 1);
    x = std::clamp(x, 0, w > 0 ? w - 1 : 0);
    y = std::clamp(y, 0, h > 0 ? h - 1 : 0);
    ax = static_cast<LONG>((static_cast<std::int64_t>(x) * 65535) / sw);
    ay = static_cast<LONG>((static_cast<std::int64_t>(y) * 65535) / sh);
}

} // namespace

void WinInputSink::set_frame_size(int width, int height) {
    frame_w_ = width;
    frame_h_ = height;
}

void WinInputSink::on_mouse(const MouseEvent& ev) {
    const int desk_w = GetSystemMetrics(SM_CXSCREEN);
    const int desk_h = GetSystemMetrics(SM_CYSCREEN);
    int x = ev.x;
    int y = ev.y;
    if (frame_w_ > 0 && frame_h_ > 0 && desk_w > 0 && desk_h > 0) {
        x = ev.x * desk_w / frame_w_;
        y = ev.y * desk_h / frame_h_;
    }
    LONG ax = 0, ay = 0;
    desktop_to_absolute(x, y, ax, ay);

    DWORD flags = MOUSEEVENTF_ABSOLUTE | MOUSEEVENTF_VIRTUALDESK | MOUSEEVENTF_MOVE;
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::LeftDown)) {
        flags |= MOUSEEVENTF_LEFTDOWN;
    }
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::LeftUp)) {
        flags |= MOUSEEVENTF_LEFTUP;
    }
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::RightDown)) {
        flags |= MOUSEEVENTF_RIGHTDOWN;
    }
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::RightUp)) {
        flags |= MOUSEEVENTF_RIGHTUP;
    }
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::MiddleDown)) {
        flags |= MOUSEEVENTF_MIDDLEDOWN;
    }
    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::MiddleUp)) {
        flags |= MOUSEEVENTF_MIDDLEUP;
    }

    if (ev.flags & static_cast<std::uint8_t>(MouseFlags::Wheel)) {
        send_mouse_input(ax, ay, flags | MOUSEEVENTF_WHEEL, static_cast<DWORD>(ev.wheel));
    } else {
        send_mouse_input(ax, ay, flags);
    }
}

void WinInputSink::on_key(const KeyEvent& ev) {
    INPUT in{};
    in.type = INPUT_KEYBOARD;
    in.ki.wVk = ev.vk;
    in.ki.wScan = static_cast<WORD>(MapVirtualKeyW(ev.vk, MAPVK_VK_TO_VSC));
    in.ki.dwFlags = 0;
    if (!ev.down) {
        in.ki.dwFlags |= KEYEVENTF_KEYUP;
    }
    if (ev.extended) {
        in.ki.dwFlags |= KEYEVENTF_EXTENDEDKEY;
    }
    SendInput(1, &in, sizeof(INPUT));
}

} // namespace crd

#endif
