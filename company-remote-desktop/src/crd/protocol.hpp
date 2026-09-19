#pragma once

#include "crd/tcp_socket.hpp"
#include "crd/version.hpp"

#include <cstdint>
#include <string>
#include <vector>

namespace crd {

enum class MsgType : std::uint8_t {
    HelloClient = 0x01,
    HelloServer = 0x02,
    AuthChallenge = 0x03,
    AuthResponse = 0x04,
    AuthResult = 0x05,
    Frame = 0x10,
    Mouse = 0x20,
    Key = 0x21,
    Heartbeat = 0x30,
    Disconnect = 0x31,
    RoleHello = 0x40,
    AssignId = 0x41,
    AgentLogin = 0x42,
    SessionIncoming = 0x43,
    AgentReady = 0x44,
};

enum class PeerRole : std::uint8_t {
    Agent = 1,
    Viewer = 2,
};

enum class AuthStatus : std::uint8_t {
    Ok = 0,
    BadPassword = 1,
    Busy = 2,
    BadProtocol = 3,
    Offline = 4,
    UnknownId = 5,
};

enum class DisconnectReason : std::uint8_t {
    User = 0,
    Error = 1,
    Busy = 2,
    Shutdown = 3,
};

enum class Codec : std::uint8_t {
    Jpeg = 1,
};

enum class MouseFlags : std::uint8_t {
    None = 0,
    Move = 1 << 0,
    LeftDown = 1 << 1,
    LeftUp = 1 << 2,
    RightDown = 1 << 3,
    RightUp = 1 << 4,
    MiddleDown = 1 << 5,
    MiddleUp = 1 << 6,
    Wheel = 1 << 7,
};

struct HelloClient {
    std::uint16_t proto_version = kProtocolVersion;
    std::uint16_t flags = 0;
};

struct HelloServer {
    std::uint16_t proto_version = kProtocolVersion;
    std::uint16_t flags = 0;
    std::uint32_t desktop_width = 0;
    std::uint32_t desktop_height = 0;
};

struct AuthChallenge {
    std::uint8_t nonce[kNonceBytes]{};
};

struct AuthResponse {
    std::uint8_t digest[kSha256Bytes]{};
};

struct AuthResult {
    AuthStatus status = AuthStatus::BadPassword;
};

struct FrameMsg {
    std::uint32_t width = 0;
    std::uint32_t height = 0;
    std::uint32_t seq = 0;
    Codec codec = Codec::Jpeg;
    std::vector<std::uint8_t> bytes;
};

struct MouseEvent {
    std::uint8_t flags = 0;
    std::int16_t x = 0;
    std::int16_t y = 0;
    std::int16_t wheel = 0;
};

struct KeyEvent {
    std::uint16_t vk = 0;
    std::uint8_t down = 0;
    std::uint8_t extended = 0;
};

struct Heartbeat {
    std::uint32_t tick_ms = 0;
};

struct DisconnectMsg {
    DisconnectReason reason = DisconnectReason::User;
};

struct RoleHello {
    std::uint16_t proto_version = kProtocolVersion;
    PeerRole role = PeerRole::Viewer;
    std::string id; // agent: saved ID (empty = first run); viewer: target ID
};

struct AssignId {
    std::string id;
    std::uint8_t secret[kDeviceSecretBytes]{};
};

struct AgentLogin {
    std::uint8_t digest[kSha256Bytes]{};
};

struct AgentReady {
    std::uint32_t desktop_width = 0;
    std::uint32_t desktop_height = 0;
};

struct Message {
    MsgType type = MsgType::Heartbeat;
    std::vector<std::uint8_t> payload;
};

bool encode_hello_client(std::vector<std::uint8_t>& payload, const HelloClient& m);
bool encode_hello_server(std::vector<std::uint8_t>& payload, const HelloServer& m);
bool encode_auth_challenge(std::vector<std::uint8_t>& payload, const AuthChallenge& m);
bool encode_auth_response(std::vector<std::uint8_t>& payload, const AuthResponse& m);
bool encode_auth_result(std::vector<std::uint8_t>& payload, const AuthResult& m);
bool encode_frame(std::vector<std::uint8_t>& payload, const FrameMsg& m);
bool encode_mouse(std::vector<std::uint8_t>& payload, const MouseEvent& m);
bool encode_key(std::vector<std::uint8_t>& payload, const KeyEvent& m);
bool encode_heartbeat(std::vector<std::uint8_t>& payload, const Heartbeat& m);
bool encode_disconnect(std::vector<std::uint8_t>& payload, const DisconnectMsg& m);
bool encode_role_hello(std::vector<std::uint8_t>& payload, const RoleHello& m);
bool encode_assign_id(std::vector<std::uint8_t>& payload, const AssignId& m);
bool encode_agent_login(std::vector<std::uint8_t>& payload, const AgentLogin& m);
bool encode_session_incoming(std::vector<std::uint8_t>& payload);
bool encode_agent_ready(std::vector<std::uint8_t>& payload, const AgentReady& m);

bool decode_hello_client(const std::vector<std::uint8_t>& payload, HelloClient& m);
bool decode_hello_server(const std::vector<std::uint8_t>& payload, HelloServer& m);
bool decode_auth_challenge(const std::vector<std::uint8_t>& payload, AuthChallenge& m);
bool decode_auth_response(const std::vector<std::uint8_t>& payload, AuthResponse& m);
bool decode_auth_result(const std::vector<std::uint8_t>& payload, AuthResult& m);
bool decode_frame(const std::vector<std::uint8_t>& payload, FrameMsg& m);
bool decode_mouse(const std::vector<std::uint8_t>& payload, MouseEvent& m);
bool decode_key(const std::vector<std::uint8_t>& payload, KeyEvent& m);
bool decode_heartbeat(const std::vector<std::uint8_t>& payload, Heartbeat& m);
bool decode_disconnect(const std::vector<std::uint8_t>& payload, DisconnectMsg& m);
bool decode_role_hello(const std::vector<std::uint8_t>& payload, RoleHello& m);
bool decode_assign_id(const std::vector<std::uint8_t>& payload, AssignId& m);
bool decode_agent_login(const std::vector<std::uint8_t>& payload, AgentLogin& m);
bool decode_agent_ready(const std::vector<std::uint8_t>& payload, AgentReady& m);

class FramedConnection {
public:
    explicit FramedConnection(TcpSocket sock);
    FramedConnection(FramedConnection&&) noexcept = default;
    FramedConnection& operator=(FramedConnection&&) noexcept = default;

    bool send(MsgType type, const std::vector<std::uint8_t>& payload);
    bool recv(Message& out);
    bool wait_readable(int timeout_ms) { return sock_.wait_readable(timeout_ms); }
    TcpSocket& socket() { return sock_; }
    const TcpSocket& socket() const { return sock_; }
    void close() { sock_.close(); }
    bool valid() const { return sock_.valid(); }
    std::string peer_ip() const { return sock_.peer_ip(); }

private:
    TcpSocket sock_;
};

const char* auth_status_text(AuthStatus s);
const char* disconnect_reason_text(DisconnectReason r);

} // namespace crd
