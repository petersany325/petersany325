#pragma once

#ifdef _WIN32

#include "crd/cli.hpp"
#include "crd/platform.hpp"
#include "crd/session.hpp"

#include <atomic>
#include <string>

namespace crd {

class HostWindow {
public:
    bool create(const HostCli& cli, const std::string& password, HostStats& stats, const std::string& backend);
    int message_loop(std::atomic<bool>& stop);
    void* hwnd() const { return hwnd_; }

private:
    static LRESULT CALLBACK wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam);
    void paint(HDC hdc);

    HWND hwnd_ = nullptr;
    HostStats* stats_ = nullptr;
    HostCli cli_{};
    std::string password_;
    std::string backend_;
};

} // namespace crd

#endif
