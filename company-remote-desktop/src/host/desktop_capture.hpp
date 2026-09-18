#pragma once

#ifdef _WIN32

#include "crd/session.hpp"

#include <atomic>
#include <condition_variable>
#include <cstdint>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

namespace crd {

struct BgraFrame {
    int width = 0;
    int height = 0;
    std::vector<std::uint8_t> bgra; // top-down, stride = width * 4
};

class DesktopCapture {
public:
    ~DesktopCapture();
    bool init(std::string* err = nullptr);
    bool desktop_size(int& width, int& height) const;
    bool grab_bgra(BgraFrame& out, std::uint32_t timeout_ms);
    const char* backend() const { return backend_.c_str(); }

private:
    bool init_dxgi(std::string* err);
    bool grab_dxgi(BgraFrame& out, std::uint32_t timeout_ms);
    bool grab_gdi(BgraFrame& out);
    void release_dxgi();

    std::string backend_ = "none";
    int width_ = 0;
    int height_ = 0;
    bool have_dxgi_frame_ = false;

    struct DxgiState;
    DxgiState* dxgi_ = nullptr;
};

// Background capture + JPEG encode. Thread-safe latest-frame source for HostServer.
class WinJpegFrameSource : public IFrameSource {
public:
    WinJpegFrameSource(int quality, int fps, int scale_percent);
    ~WinJpegFrameSource() override;

    bool start(std::string* err);
    void stop();

    bool desktop_size(int& width, int& height) override;
    bool next_jpeg(std::vector<std::uint8_t>& jpeg, int& width, int& height, std::uint32_t timeout_ms) override;
    std::string backend() const;

private:
    void capture_loop();

    int quality_;
    int fps_;
    int scale_percent_;
    DesktopCapture capture_;
    std::atomic<bool> running_{false};
    std::thread thread_;

    std::mutex mu_;
    std::condition_variable cv_;
    std::vector<std::uint8_t> jpeg_;
    int jpeg_w_ = 0;
    int jpeg_h_ = 0;
    std::uint32_t seq_ = 0;
    std::uint32_t consumed_seq_ = 0;
};

} // namespace crd

#endif
