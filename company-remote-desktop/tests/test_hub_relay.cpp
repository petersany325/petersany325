#include "agent/runtime.hpp"
#include "crd/log.hpp"
#include "crd/session.hpp"
#include "crd/tcp_socket.hpp"
#include "crd/util.hpp"
#include "hub/server.hpp"

#include <atomic>
#include <chrono>
#include <cstdio>
#include <fstream>
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
        if (sent_ >= 12) {
            std::this_thread::sleep_for(std::chrono::milliseconds(timeout_ms ? timeout_ms : 5));
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
    std::printf("test_hub_relay\n");
    crd::log_set_prefix("hubtest");

    std::string err;
    if (!crd::TcpSocket::startup(&err)) {
        std::printf("socket startup failed: %s\n", err.c_str());
        return 1;
    }

    const std::uint16_t port = 15941;
    const std::string password = "train-room-1";
    const std::string ident = "/tmp/crd-agent-ident-test.json";
    const std::string store = "/tmp/crd-hub-state-test.db";
    std::remove(ident.c_str());
    std::remove(store.c_str());

    crd::HubConfig hcfg;
    hcfg.bind_ip = "127.0.0.1";
    hcfg.port = port;
    hcfg.data_path = store;
    crd::HubServer hub(hcfg);
    std::atomic<bool> stop_hub{false};
    std::thread hub_thr([&] { hub.run(stop_hub); });
    std::this_thread::sleep_for(std::chrono::milliseconds(150));

    FakeFrames frames;
    RecordingSink sink;
    crd::AgentStats stats;
    crd::AgentConfig acfg;
    acfg.hub_host = "127.0.0.1";
    acfg.hub_port = port;
    acfg.identity_path = ident;
    acfg.access_password = password;
    acfg.fps = 30;
    crd::AgentRuntime agent(acfg, frames, sink, stats);
    std::atomic<bool> stop_agent{false};
    std::thread agent_thr([&] { agent.run(stop_agent); });

    std::string id;
    for (int i = 0; i < 80; ++i) {
        {
            std::lock_guard<std::mutex> lock(stats.mu);
            id = stats.id;
        }
        if (!id.empty() && stats.online.load()) {
            break;
        }
        std::this_thread::sleep_for(std::chrono::milliseconds(50));
    }
    expect(crd::valid_id(id), "agent received unique ID");
    expect(stats.online.load() == 1, "agent online on hub");

    crd::ViewerClient viewer;
    crd::HelloServer info{};
    const bool connected = viewer.connect_via_hub("127.0.0.1", port, id, password, info, &err);
    expect(connected, "viewer connected by ID");
    if (!connected) {
        std::printf("    connect error: %s\n", err.c_str());
    }
    expect(info.desktop_width == 64 && info.desktop_height == 48, "desktop size via relay");

    bool got_frame = false;
    for (int i = 0; i < 40 && connected; ++i) {
        crd::IncomingFrame incoming;
        viewer.poll(incoming, 100);
        if (incoming.has_frame && incoming.frame.width == 64 && !incoming.frame.bytes.empty()) {
            got_frame = true;
            break;
        }
    }
    expect(got_frame, "relayed jpeg frame");

    crd::MouseEvent mouse{};
    mouse.flags = static_cast<std::uint8_t>(crd::MouseFlags::Move) |
                  static_cast<std::uint8_t>(crd::MouseFlags::LeftDown);
    mouse.x = 12;
    mouse.y = 34;
    expect(viewer.send_mouse(mouse), "send mouse through hub");
    crd::KeyEvent key{};
    key.vk = 0x41;
    key.down = 1;
    expect(viewer.send_key(key), "send key through hub");

    bool got_mouse = false, got_key = false;
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
    expect(got_mouse, "agent received mouse via relay");
    expect(got_key, "agent received key via relay");

    crd::ViewerClient busy;
    crd::HelloServer info_busy{};
    std::string err_busy;
    expect(!busy.connect_via_hub("127.0.0.1", port, id, password, info_busy, &err_busy),
           "second viewer rejected (busy)");

    viewer.disconnect();
    std::this_thread::sleep_for(std::chrono::milliseconds(200));

    crd::ViewerClient bad;
    crd::HelloServer info2{};
    std::string err2;
    expect(!bad.connect_via_hub("127.0.0.1", port, id, "wrong-password", info2, &err2), "wrong password rejected");

    crd::ViewerClient ghost;
    crd::HelloServer info3{};
    std::string err3;
    expect(!ghost.connect_via_hub("127.0.0.1", port, "111222333", password, info3, &err3),
           "unknown / offline ID rejected");

    stop_agent.store(true);
    stop_hub.store(true);
    {
        std::string ignored;
        auto poke = crd::TcpSocket::connect_to("127.0.0.1", port, 200, &ignored);
        (void)poke;
    }
    agent_thr.join();
    hub_thr.join();
    crd::TcpSocket::cleanup();

    if (g_failed) {
        std::printf("%d test(s) failed\n", g_failed);
        return 1;
    }
    std::printf("all hub relay tests passed (ID %s)\n", crd::format_id(id).c_str());
    return 0;
}
