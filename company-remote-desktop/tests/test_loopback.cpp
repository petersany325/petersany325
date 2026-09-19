#include "crd/log.hpp"
#include "crd/session.hpp"
#include "crd/tcp_socket.hpp"

#include <atomic>
#include <chrono>
#include <cstdio>
#include <mutex>
#include <thread>
#include <vector>

namespace {

int g_failed = 0;

void expect(bool cond, const char* name) {
    if (cond) {
        std::printf("  PASS  %s\n", name);
    } else {
        std::printf("  FAIL  %s\n", name);
        ++g_failed;
    }
}

class FakeFrames : public crd::IFrameSource {
public:
    bool desktop_size(int& width, int& height) override {
        width = 64;
        height = 48;
        return true;
    }
    bool next_jpeg(std::vector<std::uint8_t>& jpeg, int& width, int& height, std::uint32_t timeout_ms) override {
        width = 64;
        height = 48;
        if (sent_ >= 8) {
            std::this_thread::sleep_for(std::chrono::milliseconds(timeout_ms));
            return false;
        }
        jpeg = {0xFF, 0xD8, 0x00, 0x10, 0xFF, 0xD9};
        ++sent_;
        return true;
    }

private:
    int sent_ = 0;
};

class RecordingSink : public crd::IInputSink {
public:
    void on_mouse(const crd::MouseEvent& ev) override {
        std::lock_guard<std::mutex> lock(mu);
        mice.push_back(ev);
    }
    void on_key(const crd::KeyEvent& ev) override {
        std::lock_guard<std::mutex> lock(mu);
        keys.push_back(ev);
    }
    std::mutex mu;
    std::vector<crd::MouseEvent> mice;
    std::vector<crd::KeyEvent> keys;
};

} // namespace

int main() {
    std::printf("test_loopback\n");
    crd::log_set_prefix("loopback");

    std::string err;
    if (!crd::TcpSocket::startup(&err)) {
        std::printf("socket startup failed: %s\n", err.c_str());
        return 1;
    }

    const std::uint16_t port = 15938;
    const std::string password = "train-room-1";

    FakeFrames frames;
    RecordingSink sink;
    crd::HostStats stats;
    crd::HostConfig cfg;
    cfg.bind_ip = "127.0.0.1";
    cfg.port = port;
    cfg.password = password;
    cfg.fps = 30;

    crd::HostServer server(cfg, frames, sink, stats);
    std::atomic<bool> stop{false};
    std::thread host([&] { server.run(stop); });

    bool listening = false;
    for (int i = 0; i < 50; ++i) {
        if (stats.listening.load()) {
            listening = true;
            break;
        }
        std::this_thread::sleep_for(std::chrono::milliseconds(20));
    }
    expect(listening, "host listening");

    crd::ViewerClient viewer;
    crd::HelloServer info{};
    const bool connected = viewer.connect("127.0.0.1", port, password, info, &err);
    expect(connected, "viewer auth connect");
    if (!connected) {
        std::printf("    connect error: %s\n", err.c_str());
    }
    expect(info.desktop_width == 64 && info.desktop_height == 48, "desktop size in hello");

    bool got_frame = false;
    for (int i = 0; i < 40 && connected; ++i) {
        crd::IncomingFrame incoming;
        viewer.poll(incoming, 100);
        if (incoming.has_frame && incoming.frame.width == 64 && !incoming.frame.bytes.empty()) {
            got_frame = true;
            break;
        }
    }
    expect(got_frame, "received jpeg frame");

    crd::MouseEvent mouse{};
    mouse.flags = static_cast<std::uint8_t>(crd::MouseFlags::Move) |
                  static_cast<std::uint8_t>(crd::MouseFlags::LeftDown);
    mouse.x = 12;
    mouse.y = 34;
    expect(viewer.send_mouse(mouse), "send mouse");

    crd::KeyEvent key{};
    key.vk = 0x41;
    key.down = 1;
    expect(viewer.send_key(key), "send key");

    bool got_mouse = false;
    bool got_key = false;
    for (int i = 0; i < 40; ++i) {
        {
            std::lock_guard<std::mutex> lock(sink.mu);
            got_mouse = !sink.mice.empty();
            got_key = !sink.keys.empty();
        }
        if (got_mouse && got_key) {
            break;
        }
        std::this_thread::sleep_for(std::chrono::milliseconds(25));
    }
    expect(got_mouse, "host injected mouse");
    expect(got_key, "host injected key");
    if (got_mouse) {
        std::lock_guard<std::mutex> lock(sink.mu);
        expect(sink.mice[0].x == 12 && sink.mice[0].y == 34, "mouse coordinates");
    }
    if (got_key) {
        std::lock_guard<std::mutex> lock(sink.mu);
        expect(sink.keys[0].vk == 0x41 && sink.keys[0].down == 1, "key vk");
    }

    crd::ViewerClient busy;
    crd::HelloServer info_busy{};
    std::string err_busy;
    const bool busy_rejected = !busy.connect("127.0.0.1", port, password, info_busy, &err_busy);
    expect(busy_rejected, "second viewer rejected while busy");

    viewer.disconnect();
    std::this_thread::sleep_for(std::chrono::milliseconds(150));

    crd::ViewerClient bad;
    crd::HelloServer info2{};
    std::string err2;
    const bool should_fail = !bad.connect("127.0.0.1", port, "wrong-password", info2, &err2);
    expect(should_fail, "wrong password rejected");
    stop.store(true);
    // Unblock accept()
    {
        std::string ignored;
        auto poke = crd::TcpSocket::connect_to("127.0.0.1", port, 200, &ignored);
        (void)poke;
    }
    host.join();
    crd::TcpSocket::cleanup();

    if (g_failed) {
        std::printf("%d test(s) failed\n", g_failed);
        return 1;
    }
    std::printf("all loopback tests passed\n");
    return 0;
}
