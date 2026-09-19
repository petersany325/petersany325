#pragma once

#include "crd/protocol.hpp"

#include <atomic>
#include <cstdint>
#include <memory>
#include <mutex>
#include <string>

namespace crd {

struct HostConfig {
    std::string bind_ip = "0.0.0.0";
    std::uint16_t port = kDefaultPort;
    std::string password;
    int jpeg_quality = kDefaultJpegQuality;
    int fps = kDefaultFps;
};

struct HostStats {
    std::atomic<int> listening{0};
    std::atomic<int> connected{0};
    std::atomic<std::uint64_t> frames_sent{0};
    std::atomic<std::uint64_t> bytes_sent{0};
    std::atomic<std::uint32_t> desktop_w{0};
    std::atomic<std::uint32_t> desktop_h{0};
    std::mutex ip_mu;
    std::string viewer_ip;
};

class IFrameSource {
public:
    virtual ~IFrameSource() = default;
    virtual bool desktop_size(int& width, int& height) = 0;
    // Returns true when a new JPEG is available. May block up to timeout_ms.
    virtual bool next_jpeg(std::vector<std::uint8_t>& jpeg, int& width, int& height, std::uint32_t timeout_ms) = 0;
};

class IInputSink {
public:
    virtual ~IInputSink() = default;
    virtual void on_mouse(const MouseEvent& ev) = 0;
    virtual void on_key(const KeyEvent& ev) = 0;
};

class HostServer {
public:
    HostServer(HostConfig cfg, IFrameSource& source, IInputSink& sink, HostStats& stats);
    void run(std::atomic<bool>& stop);

private:
    void handle_session(FramedConnection& conn, TcpSocket& listener, std::atomic<bool>& stop);
    bool handshake(FramedConnection& conn, std::atomic<bool>& stop);

    HostConfig cfg_;
    IFrameSource& source_;
    IInputSink& sink_;
    HostStats& stats_;
};

struct IncomingFrame {
    FrameMsg frame;
    bool has_frame = false;
    bool disconnected = false;
    DisconnectReason reason = DisconnectReason::User;
};

class ViewerClient {
public:
    bool connect(const std::string& host, std::uint16_t port, const std::string& password, HelloServer& info,
                 std::string* err);
    bool connect_via_hub(const std::string& hub_host, std::uint16_t hub_port, const std::string& target_id,
                         const std::string& password, HelloServer& info, std::string* err);
    bool poll(IncomingFrame& incoming, int timeout_ms);
    bool send_mouse(const MouseEvent& ev);
    bool send_key(const KeyEvent& ev);
    bool send_heartbeat(std::uint32_t tick_ms);
    void disconnect();
    bool connected() const { return conn_ && conn_->valid(); }
    std::string peer_ip() const { return conn_ ? conn_->peer_ip() : std::string(); }

private:
    std::unique_ptr<FramedConnection> conn_;
};

} // namespace crd
