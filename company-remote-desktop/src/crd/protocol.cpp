#include "crd/protocol.hpp"

#include "crd/byte_io.hpp"

#include <cstring>

namespace crd {
namespace {

bool expect_empty(ByteReader& r) { return r.empty(); }

} // namespace

bool encode_hello_client(std::vector<std::uint8_t>& payload, const HelloClient& m) {
    payload.clear();
    write_u16le(payload, m.proto_version);
    write_u16le(payload, m.flags);
    return true;
}

bool encode_hello_server(std::vector<std::uint8_t>& payload, const HelloServer& m) {
    payload.clear();
    write_u16le(payload, m.proto_version);
    write_u16le(payload, m.flags);
    write_u32le(payload, m.desktop_width);
    write_u32le(payload, m.desktop_height);
    return true;
}

bool encode_auth_challenge(std::vector<std::uint8_t>& payload, const AuthChallenge& m) {
    payload.assign(m.nonce, m.nonce + kNonceBytes);
    return true;
}

bool encode_auth_response(std::vector<std::uint8_t>& payload, const AuthResponse& m) {
    payload.assign(m.digest, m.digest + kSha256Bytes);
    return true;
}

bool encode_auth_result(std::vector<std::uint8_t>& payload, const AuthResult& m) {
    payload.clear();
    write_u8(payload, static_cast<std::uint8_t>(m.status));
    return true;
}

bool encode_frame(std::vector<std::uint8_t>& payload, const FrameMsg& m) {
    payload.clear();
    write_u32le(payload, m.width);
    write_u32le(payload, m.height);
    write_u32le(payload, m.seq);
    write_u8(payload, static_cast<std::uint8_t>(m.codec));
    write_u32le(payload, static_cast<std::uint32_t>(m.bytes.size()));
    write_bytes(payload, m.bytes.data(), m.bytes.size());
    return true;
}

bool encode_mouse(std::vector<std::uint8_t>& payload, const MouseEvent& m) {
    payload.clear();
    write_u8(payload, m.flags);
    write_i16le(payload, m.x);
    write_i16le(payload, m.y);
    write_i16le(payload, m.wheel);
    return true;
}

bool encode_key(std::vector<std::uint8_t>& payload, const KeyEvent& m) {
    payload.clear();
    write_u16le(payload, m.vk);
    write_u8(payload, m.down);
    write_u8(payload, m.extended);
    return true;
}

bool encode_heartbeat(std::vector<std::uint8_t>& payload, const Heartbeat& m) {
    payload.clear();
    write_u32le(payload, m.tick_ms);
    return true;
}

bool encode_disconnect(std::vector<std::uint8_t>& payload, const DisconnectMsg& m) {
    payload.clear();
    write_u8(payload, static_cast<std::uint8_t>(m.reason));
    return true;
}

bool encode_role_hello(std::vector<std::uint8_t>& payload, const RoleHello& m) {
    if (m.id.size() > static_cast<std::size_t>(kMaxIdBytes)) {
        return false;
    }
    payload.clear();
    write_u16le(payload, m.proto_version);
    write_u8(payload, static_cast<std::uint8_t>(m.role));
    write_u8(payload, static_cast<std::uint8_t>(m.id.size()));
    write_bytes(payload, m.id.data(), m.id.size());
    return true;
}

bool encode_assign_id(std::vector<std::uint8_t>& payload, const AssignId& m) {
    if (m.id.size() > static_cast<std::size_t>(kMaxIdBytes)) {
        return false;
    }
    payload.clear();
    write_u8(payload, static_cast<std::uint8_t>(m.id.size()));
    write_bytes(payload, m.id.data(), m.id.size());
    write_bytes(payload, m.secret, kDeviceSecretBytes);
    return true;
}

bool encode_agent_login(std::vector<std::uint8_t>& payload, const AgentLogin& m) {
    payload.assign(m.digest, m.digest + kSha256Bytes);
    return true;
}

bool encode_session_incoming(std::vector<std::uint8_t>& payload) {
    payload.clear();
    write_u8(payload, 0);
    return true;
}

bool encode_agent_ready(std::vector<std::uint8_t>& payload, const AgentReady& m) {
    payload.clear();
    write_u32le(payload, m.desktop_width);
    write_u32le(payload, m.desktop_height);
    return true;
}

bool decode_hello_client(const std::vector<std::uint8_t>& payload, HelloClient& m) {
    ByteReader r(payload);
    return r.u16le(m.proto_version) && r.u16le(m.flags) && expect_empty(r);
}

bool decode_hello_server(const std::vector<std::uint8_t>& payload, HelloServer& m) {
    ByteReader r(payload);
    return r.u16le(m.proto_version) && r.u16le(m.flags) && r.u32le(m.desktop_width) && r.u32le(m.desktop_height) &&
           expect_empty(r);
}

bool decode_auth_challenge(const std::vector<std::uint8_t>& payload, AuthChallenge& m) {
    if (payload.size() != kNonceBytes) {
        return false;
    }
    std::memcpy(m.nonce, payload.data(), kNonceBytes);
    return true;
}

bool decode_auth_response(const std::vector<std::uint8_t>& payload, AuthResponse& m) {
    if (payload.size() != kSha256Bytes) {
        return false;
    }
    std::memcpy(m.digest, payload.data(), kSha256Bytes);
    return true;
}

bool decode_auth_result(const std::vector<std::uint8_t>& payload, AuthResult& m) {
    ByteReader r(payload);
    std::uint8_t status = 0;
    if (!r.u8(status) || !expect_empty(r)) {
        return false;
    }
    m.status = static_cast<AuthStatus>(status);
    return true;
}

bool decode_frame(const std::vector<std::uint8_t>& payload, FrameMsg& m) {
    ByteReader r(payload);
    std::uint8_t codec = 0;
    std::uint32_t nbytes = 0;
    if (!r.u32le(m.width) || !r.u32le(m.height) || !r.u32le(m.seq) || !r.u8(codec) || !r.u32le(nbytes)) {
        return false;
    }
    if (nbytes > static_cast<std::uint32_t>(kMaxMessageBytes) || r.remaining() != nbytes) {
        return false;
    }
    m.codec = static_cast<Codec>(codec);
    m.bytes.assign(r.remaining_data(), r.remaining_data() + nbytes);
    return true;
}

bool decode_mouse(const std::vector<std::uint8_t>& payload, MouseEvent& m) {
    ByteReader r(payload);
    return r.u8(m.flags) && r.i16le(m.x) && r.i16le(m.y) && r.i16le(m.wheel) && expect_empty(r);
}

bool decode_key(const std::vector<std::uint8_t>& payload, KeyEvent& m) {
    ByteReader r(payload);
    return r.u16le(m.vk) && r.u8(m.down) && r.u8(m.extended) && expect_empty(r);
}

bool decode_heartbeat(const std::vector<std::uint8_t>& payload, Heartbeat& m) {
    ByteReader r(payload);
    return r.u32le(m.tick_ms) && expect_empty(r);
}

bool decode_disconnect(const std::vector<std::uint8_t>& payload, DisconnectMsg& m) {
    ByteReader r(payload);
    std::uint8_t reason = 0;
    if (!r.u8(reason) || !expect_empty(r)) {
        return false;
    }
    m.reason = static_cast<DisconnectReason>(reason);
    return true;
}

bool decode_role_hello(const std::vector<std::uint8_t>& payload, RoleHello& m) {
    ByteReader r(payload);
    std::uint8_t role = 0, id_len = 0;
    if (!r.u16le(m.proto_version) || !r.u8(role) || !r.u8(id_len)) {
        return false;
    }
    if (id_len > kMaxIdBytes || r.remaining() != id_len) {
        return false;
    }
    m.role = static_cast<PeerRole>(role);
    m.id.assign(reinterpret_cast<const char*>(r.remaining_data()), id_len);
    return true;
}

bool decode_assign_id(const std::vector<std::uint8_t>& payload, AssignId& m) {
    ByteReader r(payload);
    std::uint8_t id_len = 0;
    if (!r.u8(id_len) || id_len == 0 || id_len > kMaxIdBytes || r.remaining() != static_cast<std::size_t>(id_len) + kDeviceSecretBytes) {
        return false;
    }
    m.id.assign(reinterpret_cast<const char*>(r.remaining_data()), id_len);
    r.skip(id_len);
    return r.bytes(m.secret, kDeviceSecretBytes);
}

bool decode_agent_login(const std::vector<std::uint8_t>& payload, AgentLogin& m) {
    if (payload.size() != kSha256Bytes) {
        return false;
    }
    std::memcpy(m.digest, payload.data(), kSha256Bytes);
    return true;
}

bool decode_agent_ready(const std::vector<std::uint8_t>& payload, AgentReady& m) {
    ByteReader r(payload);
    return r.u32le(m.desktop_width) && r.u32le(m.desktop_height) && expect_empty(r);
}

FramedConnection::FramedConnection(TcpSocket sock) : sock_(std::move(sock)) {}

bool FramedConnection::send(MsgType type, const std::vector<std::uint8_t>& payload) {
    if (payload.size() + 1 > static_cast<std::size_t>(kMaxMessageBytes)) {
        return false;
    }
    std::vector<std::uint8_t> header;
    write_u32le(header, static_cast<std::uint32_t>(payload.size() + 1));
    write_u8(header, static_cast<std::uint8_t>(type));
    return sock_.send_all(header.data(), header.size()) &&
           (payload.empty() || sock_.send_all(payload.data(), payload.size()));
}

bool FramedConnection::recv(Message& out) {
    std::uint8_t len_buf[4];
    if (!sock_.recv_all(len_buf, 4)) {
        return false;
    }
    ByteReader lr(len_buf, 4);
    std::uint32_t body_len = 0;
    if (!lr.u32le(body_len) || body_len == 0 || body_len > static_cast<std::uint32_t>(kMaxMessageBytes)) {
        return false;
    }
    std::vector<std::uint8_t> body(body_len);
    if (!sock_.recv_all(body.data(), body.size())) {
        return false;
    }
    out.type = static_cast<MsgType>(body[0]);
    out.payload.assign(body.begin() + 1, body.end());
    return true;
}

const char* auth_status_text(AuthStatus s) {
    switch (s) {
    case AuthStatus::Ok:
        return "ok";
    case AuthStatus::BadPassword:
        return "bad password";
    case AuthStatus::Busy:
        return "host busy (one viewer only)";
    case AuthStatus::BadProtocol:
        return "protocol mismatch";
    case AuthStatus::Offline:
        return "agent offline";
    case AuthStatus::UnknownId:
        return "unknown ID";
    }
    return "unknown";
}

const char* disconnect_reason_text(DisconnectReason r) {
    switch (r) {
    case DisconnectReason::User:
        return "user";
    case DisconnectReason::Error:
        return "error";
    case DisconnectReason::Busy:
        return "busy";
    case DisconnectReason::Shutdown:
        return "shutdown";
    }
    return "unknown";
}

} // namespace crd
