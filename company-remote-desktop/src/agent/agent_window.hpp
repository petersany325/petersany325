#pragma once

#ifdef _WIN32

#include "agent/runtime.hpp"
#include "crd/platform.hpp"

#include <atomic>
#include <string>

namespace crd {

class AgentWindow {
public:
    bool create(AgentRuntime& runtime, AgentStats& stats, const std::string& backend, const std::string& hub);
    int message_loop(std::atomic<bool>& stop);

private:
    static LRESULT CALLBACK wnd_proc(HWND hwnd, UINT msg, WPARAM wparam, LPARAM lparam);
    void paint(HDC hdc);
    void copy_id();
    void apply_password();

    HWND hwnd_ = nullptr;
    HWND pass_edit_ = nullptr;
    HWND apply_btn_ = nullptr;
    HWND copy_btn_ = nullptr;
    AgentRuntime* runtime_ = nullptr;
    AgentStats* stats_ = nullptr;
    std::string backend_;
    std::string hub_;
};

} // namespace crd

#endif
