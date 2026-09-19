#include "agent/runtime.hpp"

#include "crd/log.hpp"
#include "crd/random.hpp"
#include "crd/sha256.hpp"
#include "crd/util.hpp"

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

} // namespace

AgentRuntime::AgentRuntime(AgentConfig cfg, IFrameSource& source, IInputSink& sink, AgentStats& stats)
    : cfg_(std::move(cfg)), source_(source), sink_(sink), stats_(stats) {}

void AgentRuntime::set_access_password(const std::string& password) {
    std::lock_guard<std::mutex> lock(pw_mu_);
    identity_.access_password = password;
    if (!identity_.id.empty() && !cfg_.identity_path.empty()) {
        save_identity(cfg_.identity_path, identity_);
    }
}

std::string AgentRuntime::access_password() const {
    std::lock_guard<std::mutex> lock(pw_mu_);
    return identity_.access_password;
}

std::string AgentRuntime::id() const {
    std::lock_guard<std::mutex> lock(pw_mu_);
    return identity_.id;
}

bool AgentRuntime::register_or_login(FramedConnection& conn, std::string* err) {
    if (!cfg_.identity_path.empty()) {
        load_identity(cfg_.identity_path, identity_);
    }
    if (identity_.access_password.empty()) {
        identity_.access_password = cfg_.access_password.empty() ? random_password(8) : cfg_.access_password;
    } else if (!cfg_.access_password.empty()) {
        identity_.access_password = cfg_.access_password;
    }

    RoleHello hello;
    hello.proto_version = kProtocolVersion;
    hello.role = PeerRole::Agent;
    hello.id = identity_.has_secret ? identity_.id : std::string();
    if (!send_msg(conn, MsgType::RoleHello, encode_role_hello, hello)) {
        if (err) {
            *err = "failed to send ROLE_HELLO";
        }
        return false;
    }

    Message msg;
    if (!conn.recv(msg)) {
        if (err) {
            *err = "hub closed during register/login";
        }
        return false;
    }

    if (hello.id.empty()) {
        if (msg.type != MsgType::AssignId) {
            if (err) {
                *err = "expected ASSIGN_ID";
            }
            return false;
        }
        AssignId assigned;
        if (!decode_assign_id(msg.payload, assigned)) {
            if (err) {
                *err = "bad ASSIGN_ID";
            }
            return false;
        }
        identity_.id = assigned.id;
        std::memcpy(identity_.device_secret, assigned.secret, kDeviceSecretBytes);
        identity_.has_secret = true;
        if (!cfg_.identity_path.empty()) {
            save_identity(cfg_.identity_path, identity_);
        }
        log_info("assigned ID %s", format_id(identity_.id).c_str());
        return true;
    }

    if (msg.type == MsgType::AuthResult) {
        AuthResult ar{};
        decode_auth_result(msg.payload, ar);
        if (err) {
            *err = auth_status_text(ar.status);
        }
        return false;
    }
    if (msg.type != MsgType::AuthChallenge) {
        if (err) {
            *err = "expected AUTH_CHALLENGE";
        }
        return false;
    }
    AuthChallenge ch{};
    if (!decode_auth_challenge(msg.payload, ch)) {
        if (err) {
            *err = "bad AUTH_CHALLENGE";
        }
        return false;
    }
    AgentLogin login{};
    device_login_digest(ch.nonce, identity_.device_secret, login.digest);
    if (!send_msg(conn, MsgType::AgentLogin, encode_agent_login, login)) {
        if (err) {
            *err = "failed AGENT_LOGIN";
        }
        return false;
    }
    if (!conn.recv(msg) || msg.type != MsgType::AuthResult) {
        if (err) {
            *err = "expected AUTH_RESULT";
        }
        return false;
    }
    AuthResult ar{};
    if (!decode_auth_result(msg.payload, ar) || ar.status != AuthStatus::Ok) {
        if (err) {
            *err = ar.status == AuthStatus::Ok ? "login failed" : auth_status_text(ar.status);
        }
        return false;
    }
    log_info("logged in as %s", format_id(identity_.id).c_str());
    return true;
}

void AgentRuntime::session_loop(FramedConnection& conn, std::atomic<bool>& stop) {
    bool streaming = false;
    std::uint8_t pending_nonce[kNonceBytes]{};
    bool pending_auth = false;
    std::uint32_t seq = 0;
    std::uint32_t last_hb = now_ms();
    const int frame_budget_ms = cfg_.fps > 0 ? (1000 / cfg_.fps) : 66;

    int dw = 0, dh = 0;
    source_.desktop_size(dw, dh);
    stats_.desktop_w.store(static_cast<std::uint32_t>(dw));
    stats_.desktop_h.store(static_cast<std::uint32_t>(dh));
    send_msg(conn, MsgType::AgentReady, encode_agent_ready, AgentReady{static_cast<std::uint32_t>(dw), static_cast<std::uint32_t>(dh)});

    while (!stop.load() && conn.valid()) {
        if (conn.wait_readable(streaming ? 5 : 200)) {
            Message msg;
            if (!conn.recv(msg)) {
                break;
            }
            if (msg.type == MsgType::SessionIncoming) {
                AuthChallenge ch{};
                if (!random_bytes(ch.nonce, kNonceBytes)) {
                    break;
                }
                std::memcpy(pending_nonce, ch.nonce, kNonceBytes);
                pending_auth = true;
                streaming = false;
                stats_.in_session.store(0);
                send_msg(conn, MsgType::AuthChallenge, encode_auth_challenge, ch);
                log_info("incoming session — sending access challenge");
            } else if (msg.type == MsgType::AuthResponse && pending_auth) {
                AuthResponse resp{};
                const bool parsed = decode_auth_response(msg.payload, resp);
                std::uint8_t expect[kSha256Bytes];
                auth_digest(pending_nonce, access_password(), expect);
                const bool ok = parsed && std::memcmp(expect, resp.digest, kSha256Bytes) == 0;
                send_msg(conn, MsgType::AuthResult, encode_auth_result, AuthResult{ok ? AuthStatus::Ok : AuthStatus::BadPassword});
                pending_auth = false;
                if (ok) {
                    HelloServer hs{};
                    hs.proto_version = kProtocolVersion;
                    hs.desktop_width = static_cast<std::uint32_t>(dw);
                    hs.desktop_height = static_cast<std::uint32_t>(dh);
                    send_msg(conn, MsgType::HelloServer, encode_hello_server, hs);
                    streaming = true;
                    stats_.in_session.store(1);
                    log_info("viewer authenticated");
                } else {
                    log_warn("viewer password rejected");
                }
            } else if (msg.type == MsgType::Mouse && streaming) {
                MouseEvent ev{};
                if (decode_mouse(msg.payload, ev)) {
                    sink_.on_mouse(ev);
                }
            } else if (msg.type == MsgType::Key && streaming) {
                KeyEvent ev{};
                if (decode_key(msg.payload, ev)) {
                    sink_.on_key(ev);
                }
            } else if (msg.type == MsgType::Disconnect) {
                streaming = false;
                pending_auth = false;
                stats_.in_session.store(0);
                log_info("viewer disconnected");
            }
        }

        if (streaming) {
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
                dw = fw;
                dh = fh;
            }
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

void AgentRuntime::run(std::atomic<bool>& stop) {
    while (!stop.load()) {
        std::string err;
        TcpSocket sock = TcpSocket::connect_to(cfg_.hub_host, cfg_.hub_port, 5000, &err);
        if (!sock.valid()) {
            {
                std::lock_guard<std::mutex> lock(stats_.mu);
                stats_.last_error = "hub unreachable: " + err;
            }
            stats_.online.store(0);
            log_warn("hub %s:%u unreachable (%s)", cfg_.hub_host.c_str(), cfg_.hub_port, err.c_str());
            for (int i = 0; i < 20 && !stop.load(); ++i) {
                std::this_thread::sleep_for(std::chrono::milliseconds(100));
            }
            continue;
        }
        FramedConnection conn(std::move(sock));
        if (!register_or_login(conn, &err)) {
            {
                std::lock_guard<std::mutex> lock(stats_.mu);
                stats_.last_error = err;
            }
            stats_.online.store(0);
            log_error("register/login failed: %s", err.c_str());
            std::this_thread::sleep_for(std::chrono::seconds(2));
            continue;
        }
        {
            std::lock_guard<std::mutex> lock(stats_.mu);
            stats_.id = identity_.id;
            stats_.hub = cfg_.hub_host + ":" + std::to_string(cfg_.hub_port);
            stats_.last_error.clear();
        }
        stats_.online.store(1);
        session_loop(conn, stop);
        stats_.online.store(0);
        stats_.in_session.store(0);
        log_info("disconnected from hub; retrying");
    }
}

} // namespace crd
