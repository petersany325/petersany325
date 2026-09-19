#pragma once

#ifdef _WIN32

#include "agent/runtime.hpp"
#include "crd/cli.hpp"
#include "crd/platform.hpp"
#include "crd/session.hpp"

#include <atomic>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

namespace crd {

class DesktopWindow {
public:
    int run(AgentRuntime& runtime, AgentStats& stats, const ViewerCli& cli);

private:
    static LRESULT CALLBACK wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam);
    bool create();
    void layout_controls();
    void refresh_my_id();
    void copy_id();
    void apply_password();
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
    HWND my_id_ = nullptr;
    HWND copy_btn_ = nullptr;
    HWND unattended_edit_ = nullptr;
    HWND save_pw_btn_ = nullptr;
    HWND remote_id_edit_ = nullptr;
    HWND remote_pw_edit_ = nullptr;
    HWND connect_btn_ = nullptr;
    HWND status_ = nullptr;

    AgentRuntime* runtime_ = nullptr;
    AgentStats* stats_ = nullptr;
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
};

} // namespace crd

#endif
