#pragma once

#ifdef _WIN32

#include "crd/cli.hpp"
#include "crd/platform.hpp"
#include "crd/session.hpp"

#include <atomic>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

namespace crd {

class ViewerWindow {
public:
    int run(const ViewerCli& cli);

private:
    static LRESULT CALLBACK wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam);
    bool create();
    void layout_controls();
    void on_connect_clicked();
    void start_session();
    void stop_session();
    void net_loop();
    void paint_remote(HDC hdc);
    void forward_mouse(UINT msg, WPARAM wparam, LPARAM lparam);
    void forward_key(UINT msg, WPARAM wparam, LPARAM lparam);
    bool map_to_remote(int client_x, int client_y, int& rx, int& ry) const;
    RECT view_rect() const;
    void set_status(const std::string& text);

    HWND hwnd_ = nullptr;
    HWND host_edit_ = nullptr;
    HWND port_edit_ = nullptr;
    HWND pass_edit_ = nullptr;
    HWND connect_btn_ = nullptr;
    HWND status_ = nullptr;

    ViewerCli cli_{};
    ViewerClient client_;
    std::atomic<bool> running_{false};
    std::atomic<bool> connected_{false};
    std::thread net_thread_;

    mutable std::mutex frame_mu_;
    std::vector<std::uint8_t> bgra_;
    int frame_w_ = 0;
    int frame_h_ = 0;
    int remote_w_ = 0;
    int remote_h_ = 0;
    std::string status_text_ = "Disconnected";
};

} // namespace crd

#endif
