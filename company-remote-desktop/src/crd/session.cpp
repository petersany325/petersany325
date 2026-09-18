#include "crd/session.hpp"

#include "crd/log.hpp"
#include "crd/random.hpp"
#include "crd/sha256.hpp"

#include <chrono>
#include <cstring>
#include <thread>
#include <vector>

namespace crd {
namespace {

std::uint32_t now_ms() {
    using clock = std::chrono::steady_clock;
    return static_cast<std::uint32_t>(
        std::chrono::duration_cast<std::chrono::milliseconds>(clock::now().time_since_epoch()).count());
}

template <typename T, typename EncodeFn>
bool send_msg(FramedConnection& conn, MsgType type, EncodeFn encode, const T& msg) {
    std::vector<std::uint8_t> payload;
    encode(payload, msg);
    return conn.send(type, payload);
}

void reject_busy(TcpSocket extra) {
    FramedConnection conn(std::move(extra));
    send_msg(conn, MsgType::AuthResult, encode_auth_result, AuthResult{AuthStatus::Busy});
    send_msg(conn, MsgType::Disconnect, encode_disconnect, DisconnectMsg{DisconnectReason::Busy});
    conn.close();
}

} // namespace

HostServer::HostServer(HostConfig cfg, IFrameSource& source, IInputSink& sink, HostStats& stats)
    : cfg_(std::move(cfg)), source_(source), sink_(sink), stats_(stats) {}

void HostServer::run(std::atomic<bool>& stop) {
    std::string err;
    TcpSocket listener = TcpSocket::listen_on(cfg_.bind_ip, cfg_.port, &err);
    if (!listener.valid()) {
        log_error("listen failed on %s:%u: %s", cfg_.bind_ip.c_str(), cfg_.port, err.c_str());
        return;
    }
    stats_.listening.store(1);
    log_info("listening on %s:%u", cfg_.bind_ip.c_str(), static_cast<unsigned>(cfg_.port));

    while (!stop.load()) {
        if (!listener.wait_readable(200)) {
            continue;
        }
        if (stop.load()) {
            break;
        }
        std::string peer;
        TcpSocket client = listener.accept_one(&peer, &err);
        if (!client.valid()) {
            log_warn("accept failed: %s", err.c_str());
            continue;
        }
        log_info("viewer connecting from %s", peer.c_str());
        {
            std::lock_guard<std::mutex> lock(stats_.ip_mu);
            stats_.viewer_ip = peer;
        }
        FramedConnection conn(std::move(client));
        handle_session(conn, listener, stop);
        stats_.connected.store(0);
        {
            std::lock_guard<std::mutex> lock(stats_.ip_mu);
            stats_.viewer_ip.clear();
        }
        log_info("viewer session ended");
    }

    stats_.listening.store(0);
}

bool HostServer::handshake(FramedConnection& conn, std::atomic<bool>& stop) {
    (void)stop;
    Message msg;
    if (!conn.recv(msg) || msg.type != MsgType::HelloClient) {
        log_warn("expected HELLO_CLIENT");
        return false;
    }
    HelloClient hello{};
    if (!decode_hello_client(msg.payload, hello) || hello.proto_version != kProtocolVersion) {
        send_msg(conn, MsgType::AuthResult, encode_auth_result, AuthResult{AuthStatus::BadProtocol});
        return false;
    }

    int dw = 0, dh = 0;
    source_.desktop_size(dw, dh);
    stats_.desktop_w.store(static_cast<std::uint32_t>(dw));
    stats_.desktop_h.store(static_cast<std::uint32_t>(dh));

    HelloServer hs{};
    hs.proto_version = kProtocolVersion;
    hs.desktop_width = static_cast<std::uint32_t>(dw);
    hs.desktop_height = static_cast<std::uint32_t>(dh);
    if (!send_msg(conn, MsgType::HelloServer, encode_hello_server, hs)) {
        return false;
    }

    AuthChallenge ch{};
    if (!random_bytes(ch.nonce, kNonceBytes)) {
        log_error("failed to generate auth nonce");
        return false;
    }
    if (!send_msg(conn, MsgType::AuthChallenge, encode_auth_challenge, ch)) {
        return false;
    }

    if (!conn.recv(msg) || msg.type != MsgType::AuthResponse) {
        log_warn("expected AUTH_RESPONSE");
        return false;
    }
    AuthResponse resp{};
    if (!decode_auth_response(msg.payload, resp)) {
        return false;
    }

    std::uint8_t expect[kSha256Bytes];
    auth_digest(ch.nonce, cfg_.password, expect);
    const bool ok = std::memcmp(expect, resp.digest, kSha256Bytes) == 0;
    const AuthStatus status = ok ? AuthStatus::Ok : AuthStatus::BadPassword;
    if (!send_msg(conn, MsgType::AuthResult, encode_auth_result, AuthResult{status})) {
        return false;
    }
    if (!ok) {
        log_warn("auth failed from %s", conn.peer_ip().c_str());
        return false;
    }
    log_info("auth ok from %s — desktop %dx%d", conn.peer_ip().c_str(), dw, dh);
    return true;
}

void HostServer::handle_session(FramedConnection& conn, TcpSocket& listener, std::atomic<bool>& stop) {
    if (!handshake(conn, stop)) {
        send_msg(conn, MsgType::Disconnect, encode_disconnect, DisconnectMsg{DisconnectReason::Error});
        return;
    }
    stats_.connected.store(1);

    std::uint32_t seq = 0;
    std::uint32_t last_hb = now_ms();
    const int frame_budget_ms = cfg_.fps > 0 ? (1000 / cfg_.fps) : 66;

    while (!stop.load() && conn.valid()) {
        bool extra_ready = false;
        bool client_ready = false;
        // Watch the session socket and the listener so a second viewer is rejected immediately.
        TcpSocket::select2(&conn.socket(), &listener, 5, &client_ready, &extra_ready);
        if (extra_ready) {
            std::string extra_peer;
            TcpSocket extra = listener.accept_one(&extra_peer, nullptr);
            if (extra.valid()) {
                log_info("rejecting extra viewer from %s (busy)", extra_peer.c_str());
                reject_busy(std::move(extra));
            }
        }

        if (client_ready) {
            Message msg;
            if (!conn.recv(msg)) {
                break;
            }
            if (msg.type == MsgType::Mouse) {
                MouseEvent ev{};
                if (decode_mouse(msg.payload, ev)) {
                    sink_.on_mouse(ev);
                }
            } else if (msg.type == MsgType::Key) {
                KeyEvent ev{};
                if (decode_key(msg.payload, ev)) {
                    sink_.on_key(ev);
                }
            } else if (msg.type == MsgType::Heartbeat) {
                // ignore; liveness is enough
            } else if (msg.type == MsgType::Disconnect) {
                break;
            }
        }

        std::vector<std::uint8_t> jpeg;
        int fw = 0, fh = 0;
        if (source_.next_jpeg(jpeg, fw, fh, static_cast<std::uint32_t>(frame_budget_ms))) {
            FrameMsg frame;
            frame.width = static_cast<std::uint32_t>(fw);
            frame.height = static_cast<std::uint32_t>(fh);
            frame.seq = ++seq;
            frame.codec = Codec::Jpeg;
            frame.bytes = std::move(jpeg);
            std::vector<std::uint8_t> payload;
            encode_frame(payload, frame);
            if (!conn.send(MsgType::Frame, payload)) {
                break;
            }
            stats_.frames_sent.fetch_add(1);
            stats_.bytes_sent.fetch_add(payload.size());
            stats_.desktop_w.store(frame.width);
            stats_.desktop_h.store(frame.height);
        }

        const std::uint32_t t = now_ms();
        if (t - last_hb >= 2000) {
            if (!send_msg(conn, MsgType::Heartbeat, encode_heartbeat, Heartbeat{t})) {
                break;
            }
            last_hb = t;
        }
    }
}

bool ViewerClient::connect(const std::string& host, std::uint16_t port, const std::string& password, HelloServer& info,
                           std::string* err) {
    disconnect();
    TcpSocket sock = TcpSocket::connect_to(host, port, 8000, err);
    if (!sock.valid()) {
        return false;
    }
    conn_ = std::make_unique<FramedConnection>(std::move(sock));

    HelloClient hc{};
    if (!send_msg(*conn_, MsgType::HelloClient, encode_hello_client, hc)) {
        if (err) {
            *err = "failed to send HELLO_CLIENT";
        }
        disconnect();
        return false;
    }

    Message msg;
    if (!conn_->recv(msg) || msg.type != MsgType::HelloServer || !decode_hello_server(msg.payload, info)) {
        if (err) {
            *err = "invalid HELLO_SERVER";
        }
        disconnect();
        return false;
    }
    if (info.proto_version != kProtocolVersion) {
        if (err) {
            *err = "protocol version mismatch";
        }
        disconnect();
        return false;
    }

    if (!conn_->recv(msg) || msg.type != MsgType::AuthChallenge) {
        if (err) {
            *err = "expected AUTH_CHALLENGE";
        }
        disconnect();
        return false;
    }
    AuthChallenge ch{};
    if (!decode_auth_challenge(msg.payload, ch)) {
        if (err) {
            *err = "bad AUTH_CHALLENGE";
        }
        disconnect();
        return false;
    }

    AuthResponse resp{};
    auth_digest(ch.nonce, password, resp.digest);
    if (!send_msg(*conn_, MsgType::AuthResponse, encode_auth_response, resp)) {
        if (err) {
            *err = "failed to send AUTH_RESPONSE";
        }
        disconnect();
        return false;
    }

    if (!conn_->recv(msg) || msg.type != MsgType::AuthResult) {
        if (err) {
            *err = "expected AUTH_RESULT";
        }
        disconnect();
        return false;
    }
    AuthResult ar{};
    if (!decode_auth_result(msg.payload, ar) || ar.status != AuthStatus::Ok) {
        if (err) {
            *err = ar.status == AuthStatus::Ok ? "auth failed" : auth_status_text(ar.status);
        }
        disconnect();
        return false;
    }
    return true;
}

bool ViewerClient::poll(IncomingFrame& incoming, int timeout_ms) {
    incoming = {};
    if (!conn_ || !conn_->valid()) {
        incoming.disconnected = true;
        return false;
    }
    if (!conn_->wait_readable(timeout_ms)) {
        return true;
    }
    Message msg;
    if (!conn_->recv(msg)) {
        incoming.disconnected = true;
        return false;
    }
    if (msg.type == MsgType::Frame) {
        incoming.has_frame = decode_frame(msg.payload, incoming.frame);
        return incoming.has_frame;
    }
    if (msg.type == MsgType::Disconnect) {
        DisconnectMsg d{};
        decode_disconnect(msg.payload, d);
        incoming.disconnected = true;
        incoming.reason = d.reason;
        return false;
    }
    return true;
}

bool ViewerClient::send_mouse(const MouseEvent& ev) {
    if (!conn_ || !conn_->valid()) {
        return false;
    }
    return send_msg(*conn_, MsgType::Mouse, encode_mouse, ev);
}

bool ViewerClient::send_key(const KeyEvent& ev) {
    if (!conn_ || !conn_->valid()) {
        return false;
    }
    return send_msg(*conn_, MsgType::Key, encode_key, ev);
}

bool ViewerClient::send_heartbeat(std::uint32_t tick_ms) {
    if (!conn_ || !conn_->valid()) {
        return false;
    }
    return send_msg(*conn_, MsgType::Heartbeat, encode_heartbeat, Heartbeat{tick_ms});
}

void ViewerClient::disconnect() {
    if (conn_ && conn_->valid()) {
        send_msg(*conn_, MsgType::Disconnect, encode_disconnect, DisconnectMsg{DisconnectReason::User});
        conn_->close();
    }
    conn_.reset();
}

} // namespace crd
