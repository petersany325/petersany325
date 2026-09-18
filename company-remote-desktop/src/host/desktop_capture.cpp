#ifdef _WIN32

#include "host/desktop_capture.hpp"

#include "crd/log.hpp"
#include "crd/platform.hpp"
#include "win/jpeg_wic.hpp"

#include <d3d11.h>
#include <dxgi1_2.h>
#include <wrl/client.h>

#include <algorithm>
#include <chrono>
#include <cstring>

#pragma comment(lib, "d3d11.lib")
#pragma comment(lib, "dxgi.lib")
#pragma comment(lib, "gdi32.lib")

using Microsoft::WRL::ComPtr;

namespace crd {
namespace {

void copy_rows(std::vector<std::uint8_t>& dest, int width, int height, const std::uint8_t* src, int src_pitch) {
    const int dst_pitch = width * 4;
    dest.resize(static_cast<std::size_t>(dst_pitch) * height);
    for (int y = 0; y < height; ++y) {
        std::memcpy(dest.data() + static_cast<std::size_t>(y) * dst_pitch, src + static_cast<std::size_t>(y) * src_pitch,
                    static_cast<std::size_t>(dst_pitch));
    }
}

bool scale_bgra_nearest(const BgraFrame& in, int scale_percent, BgraFrame& out) {
    if (scale_percent >= 100 || scale_percent <= 0) {
        out = in;
        return true;
    }
    const int nw = std::max(1, in.width * scale_percent / 100);
    const int nh = std::max(1, in.height * scale_percent / 100);
    out.width = nw;
    out.height = nh;
    out.bgra.resize(static_cast<std::size_t>(nw) * nh * 4);
    for (int y = 0; y < nh; ++y) {
        const int sy = y * in.height / nh;
        for (int x = 0; x < nw; ++x) {
            const int sx = x * in.width / nw;
            const std::uint8_t* s = in.bgra.data() + (static_cast<std::size_t>(sy) * in.width + sx) * 4;
            std::uint8_t* d = out.bgra.data() + (static_cast<std::size_t>(y) * nw + x) * 4;
            d[0] = s[0];
            d[1] = s[1];
            d[2] = s[2];
            d[3] = s[3];
        }
    }
    return true;
}

} // namespace

struct DesktopCapture::DxgiState {
    ComPtr<ID3D11Device> device;
    ComPtr<ID3D11DeviceContext> context;
    ComPtr<IDXGIOutputDuplication> dupl;
    ComPtr<ID3D11Texture2D> staging;
    int tex_w = 0;
    int tex_h = 0;
};

DesktopCapture::~DesktopCapture() { release_dxgi(); }

void DesktopCapture::release_dxgi() {
    delete dxgi_;
    dxgi_ = nullptr;
    have_dxgi_frame_ = false;
}

bool DesktopCapture::init(std::string* err) {
    width_ = GetSystemMetrics(SM_CXSCREEN);
    height_ = GetSystemMetrics(SM_CYSCREEN);
    if (init_dxgi(err)) {
        backend_ = "dxgi";
        log_info("capture backend: DXGI Desktop Duplication (%dx%d)", width_, height_);
        return true;
    }
    log_warn("DXGI init failed (%s) — falling back to GDI BitBlt", err ? err->c_str() : "unknown");
    backend_ = "gdi";
    if (width_ <= 0 || height_ <= 0) {
        if (err) {
            *err = "cannot read desktop size";
        }
        return false;
    }
    if (err) {
        err->clear();
    }
    log_info("capture backend: GDI BitBlt (%dx%d)", width_, height_);
    return true;
}

bool DesktopCapture::init_dxgi(std::string* err) {
    release_dxgi();
    dxgi_ = new DxgiState();

    D3D_FEATURE_LEVEL level{};
    HRESULT hr = D3D11CreateDevice(nullptr, D3D_DRIVER_TYPE_HARDWARE, nullptr, 0, nullptr, 0, D3D11_SDK_VERSION,
                                   &dxgi_->device, &level, &dxgi_->context);
    if (FAILED(hr)) {
        hr = D3D11CreateDevice(nullptr, D3D_DRIVER_TYPE_WARP, nullptr, 0, nullptr, 0, D3D11_SDK_VERSION, &dxgi_->device,
                               &level, &dxgi_->context);
    }
    if (FAILED(hr)) {
        if (err) {
            *err = "D3D11CreateDevice failed";
        }
        release_dxgi();
        return false;
    }

    ComPtr<IDXGIDevice> dxgi_device;
    hr = dxgi_->device.As(&dxgi_device);
    if (FAILED(hr)) {
        release_dxgi();
        return false;
    }
    ComPtr<IDXGIAdapter> adapter;
    hr = dxgi_device->GetAdapter(&adapter);
    if (FAILED(hr)) {
        release_dxgi();
        return false;
    }

    ComPtr<IDXGIOutput> output;
    hr = adapter->EnumOutputs(0, &output);
    if (FAILED(hr)) {
        if (err) {
            *err = "no DXGI output 0 (primary display)";
        }
        release_dxgi();
        return false;
    }

    DXGI_OUTPUT_DESC desc{};
    output->GetDesc(&desc);
    width_ = desc.DesktopCoordinates.right - desc.DesktopCoordinates.left;
    height_ = desc.DesktopCoordinates.bottom - desc.DesktopCoordinates.top;

    ComPtr<IDXGIOutput1> output1;
    hr = output.As(&output1);
    if (FAILED(hr)) {
        if (err) {
            *err = "IDXGIOutput1 not available";
        }
        release_dxgi();
        return false;
    }

    hr = output1->DuplicateOutput(dxgi_->device.Get(), &dxgi_->dupl);
    if (FAILED(hr)) {
        if (err) {
            *err = (hr == E_ACCESSDENIED)
                       ? "DuplicateOutput access denied (use an interactive desktop session)"
                       : "DuplicateOutput failed";
        }
        release_dxgi();
        return false;
    }
    return true;
}

bool DesktopCapture::desktop_size(int& width, int& height) const {
    width = width_;
    height = height_;
    return width > 0 && height > 0;
}

bool DesktopCapture::grab_gdi(BgraFrame& out) {
    const int w = GetSystemMetrics(SM_CXSCREEN);
    const int h = GetSystemMetrics(SM_CYSCREEN);
    if (w <= 0 || h <= 0) {
        return false;
    }
    width_ = w;
    height_ = h;

    HDC screen = GetDC(nullptr);
    if (!screen) {
        return false;
    }
    HDC mem = CreateCompatibleDC(screen);
    HBITMAP bmp = CreateCompatibleBitmap(screen, w, h);
    HGDIOBJ old = SelectObject(mem, bmp);
    const BOOL ok = BitBlt(mem, 0, 0, w, h, screen, 0, 0, SRCCOPY | CAPTUREBLT);

    BITMAPINFO bmi{};
    bmi.bmiHeader.biSize = sizeof(BITMAPINFOHEADER);
    bmi.bmiHeader.biWidth = w;
    bmi.bmiHeader.biHeight = -h; // top-down
    bmi.bmiHeader.biPlanes = 1;
    bmi.bmiHeader.biBitCount = 32;
    bmi.bmiHeader.biCompression = BI_RGB;

    out.width = w;
    out.height = h;
    out.bgra.resize(static_cast<std::size_t>(w) * h * 4);
    const int got = GetDIBits(mem, bmp, 0, h, out.bgra.data(), &bmi, DIB_RGB_COLORS);

    SelectObject(mem, old);
    DeleteObject(bmp);
    DeleteDC(mem);
    ReleaseDC(nullptr, screen);
    return ok && got > 0;
}

bool DesktopCapture::grab_dxgi(BgraFrame& out, std::uint32_t timeout_ms) {
    if (!dxgi_ || !dxgi_->dupl) {
        return false;
    }

    DXGI_OUTDUPL_FRAME_INFO info{};
    ComPtr<IDXGIResource> resource;
    HRESULT hr = dxgi_->dupl->AcquireNextFrame(timeout_ms, &info, &resource);
    if (hr == DXGI_ERROR_WAIT_TIMEOUT) {
        return false;
    }
    if (hr == DXGI_ERROR_ACCESS_LOST || hr == DXGI_ERROR_INVALID_CALL) {
        log_warn("DXGI access lost — reinitializing");
        std::string err;
        if (!init_dxgi(&err)) {
            backend_ = "gdi";
            return grab_gdi(out);
        }
        return false;
    }
    if (FAILED(hr)) {
        return false;
    }

    ComPtr<ID3D11Texture2D> tex;
    hr = resource.As(&tex);
    if (FAILED(hr)) {
        dxgi_->dupl->ReleaseFrame();
        return false;
    }

    D3D11_TEXTURE2D_DESC desc{};
    tex->GetDesc(&desc);
    if (!dxgi_->staging || dxgi_->tex_w != static_cast<int>(desc.Width) ||
        dxgi_->tex_h != static_cast<int>(desc.Height)) {
        D3D11_TEXTURE2D_DESC staging = desc;
        staging.Usage = D3D11_USAGE_STAGING;
        staging.BindFlags = 0;
        staging.CPUAccessFlags = D3D11_CPU_ACCESS_READ;
        staging.MiscFlags = 0;
        dxgi_->staging.Reset();
        hr = dxgi_->device->CreateTexture2D(&staging, nullptr, &dxgi_->staging);
        if (FAILED(hr)) {
            dxgi_->dupl->ReleaseFrame();
            return false;
        }
        dxgi_->tex_w = static_cast<int>(desc.Width);
        dxgi_->tex_h = static_cast<int>(desc.Height);
        width_ = dxgi_->tex_w;
        height_ = dxgi_->tex_h;
    }

    dxgi_->context->CopyResource(dxgi_->staging.Get(), tex.Get());
    D3D11_MAPPED_SUBRESOURCE mapped{};
    hr = dxgi_->context->Map(dxgi_->staging.Get(), 0, D3D11_MAP_READ, 0, &mapped);
    bool ok = false;
    if (SUCCEEDED(hr)) {
        out.width = dxgi_->tex_w;
        out.height = dxgi_->tex_h;
        copy_rows(out.bgra, out.width, out.height, static_cast<const std::uint8_t*>(mapped.pData),
                  static_cast<int>(mapped.RowPitch));
        dxgi_->context->Unmap(dxgi_->staging.Get(), 0);
        ok = true;
    }
    dxgi_->dupl->ReleaseFrame();
    return ok;
}

bool DesktopCapture::grab_bgra(BgraFrame& out, std::uint32_t timeout_ms) {
    if (backend_ == "dxgi") {
        if (grab_dxgi(out, timeout_ms)) {
            have_dxgi_frame_ = true;
            return true;
        }
        // Seed the first picture with GDI so the viewer is not blank before DXGI changes.
        if (!have_dxgi_frame_) {
            return grab_gdi(out);
        }
        return false;
    }
    (void)timeout_ms;
    return grab_gdi(out);
}

WinJpegFrameSource::WinJpegFrameSource(int quality, int fps, int scale_percent)
    : quality_(quality), fps_(fps), scale_percent_(scale_percent) {}

WinJpegFrameSource::~WinJpegFrameSource() { stop(); }

bool WinJpegFrameSource::start(std::string* err) {
    if (!capture_.init(err)) {
        return false;
    }
    running_.store(true);
    thread_ = std::thread(&WinJpegFrameSource::capture_loop, this);
    return true;
}

void WinJpegFrameSource::stop() {
    running_.store(false);
    cv_.notify_all();
    if (thread_.joinable()) {
        thread_.join();
    }
}

bool WinJpegFrameSource::desktop_size(int& width, int& height) {
    if (capture_.desktop_size(width, height) && scale_percent_ > 0 && scale_percent_ < 100) {
        width = std::max(1, width * scale_percent_ / 100);
        height = std::max(1, height * scale_percent_ / 100);
    }
    return width > 0 && height > 0;
}

bool WinJpegFrameSource::next_jpeg(std::vector<std::uint8_t>& jpeg, int& width, int& height, std::uint32_t timeout_ms) {
    std::unique_lock<std::mutex> lock(mu_);
    if (seq_ == consumed_seq_) {
        cv_.wait_for(lock, std::chrono::milliseconds(timeout_ms),
                     [&] { return seq_ != consumed_seq_ || !running_.load(); });
    }
    if (seq_ == consumed_seq_) {
        return false;
    }
    jpeg = jpeg_;
    width = jpeg_w_;
    height = jpeg_h_;
    consumed_seq_ = seq_;
    return !jpeg.empty();
}

std::string WinJpegFrameSource::backend() const { return capture_.backend(); }

void WinJpegFrameSource::capture_loop() {
    const int interval_ms = fps_ > 0 ? (1000 / fps_) : 66;
    std::uint32_t last_force_ms = 0;
    while (running_.load()) {
        const auto t0 = std::chrono::steady_clock::now();
        BgraFrame raw;
        if (capture_.grab_bgra(raw, static_cast<std::uint32_t>(interval_ms))) {
            BgraFrame scaled;
            scale_bgra_nearest(raw, scale_percent_, scaled);
            std::vector<std::uint8_t> jpeg;
            if (jpeg_encode_bgra(scaled.bgra.data(), scaled.width, scaled.height, quality_, jpeg)) {
                std::lock_guard<std::mutex> lock(mu_);
                jpeg_ = std::move(jpeg);
                jpeg_w_ = scaled.width;
                jpeg_h_ = scaled.height;
                ++seq_;
                cv_.notify_all();
                last_force_ms = static_cast<std::uint32_t>(
                    std::chrono::duration_cast<std::chrono::milliseconds>(t0.time_since_epoch()).count());
            }
        } else {
            // Refresh at least once per second so a static desktop still appears.
            const auto now = static_cast<std::uint32_t>(
                std::chrono::duration_cast<std::chrono::milliseconds>(t0.time_since_epoch()).count());
            if (now - last_force_ms > 1000) {
                BgraFrame raw2;
                if (capture_.grab_bgra(raw2, 0) && !raw2.bgra.empty()) {
                    BgraFrame scaled;
                    scale_bgra_nearest(raw2, scale_percent_, scaled);
                    std::vector<std::uint8_t> jpeg;
                    if (jpeg_encode_bgra(scaled.bgra.data(), scaled.width, scaled.height, quality_, jpeg)) {
                        std::lock_guard<std::mutex> lock(mu_);
                        jpeg_ = std::move(jpeg);
                        jpeg_w_ = scaled.width;
                        jpeg_h_ = scaled.height;
                        ++seq_;
                        cv_.notify_all();
                        last_force_ms = now;
                    }
                }
            }
        }

        const auto elapsed = std::chrono::duration_cast<std::chrono::milliseconds>(std::chrono::steady_clock::now() - t0)
                                 .count();
        if (elapsed < interval_ms) {
            std::this_thread::sleep_for(std::chrono::milliseconds(interval_ms - elapsed));
        }
    }
}

} // namespace crd

#endif
