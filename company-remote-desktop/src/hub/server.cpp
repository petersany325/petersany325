#include "hub/server.hpp"

#include "crd/log.hpp"
#include "crd/protocol.hpp"
#include "crd/random.hpp"
#include "crd/sha256.hpp"
#include "crd/util.hpp"

#include <chrono>
#include <cstring>
#include <memory>
#include <mutex>
#include <thread>
#include <unordered_map>
#include <vector>

namespace crd {
namespace {

template <typename T, typename EncodeFn>
bool send_msg(FramedConnection& conn, MsgType type, EncodeFn encode, const T& msg) {
    std::vector<std::uint8_t> payload;
    encode(payload, msg);
    return conn.send(type, payload);
}

struct SafePeer {
    std::mutex send_mu;
    std::unique_ptr<FramedConnection> conn;

    bool send(MsgType type, const std::vector<std::uint8_t>& payload) {
        std::lock_guard<std::mutex> lock(send_mu);
        return conn && conn->valid() && conn->send(type, payload);
    }
    bool wait(int timeout_ms) { return conn && conn->valid() && conn->wait_readable(timeout_ms); }
    bool recv(Message& msg) { return conn && conn->recv(msg); }
    bool valid() { return conn && conn->valid(); }
    void close() {
        std::lock_guard<std::mutex> lock(send_mu);
        if (conn) {
            conn->close();
        }
    }
};

bool should_relay_to_viewer(MsgType t) {
    return t == MsgType::AuthChallenge || t == MsgType::AuthResult || t == MsgType::HelloServer ||
           t == MsgType::Frame || t == MsgType::Heartbeat || t == MsgType::Disconnect;
}

struct AgentSlot {
    std::string id;
    SafePeer agent;
    SafePeer viewer;
    std::atomic<int> busy{0};
};

std::mutex g_online_mu;
std::unordered_map<std::string, std::shared_ptr<AgentSlot>> g_online;

std::shared_ptr<AgentSlot> find_online(const std::string& id) {
    std::lock_guard<std::mutex> lock(g_online_mu);
    auto it = g_online.find(normalize_id(id));
    return it == g_online.end() ? nullptr : it->second;
}

void publish_online(const std::shared_ptr<AgentSlot>& slot) {
    std::lock_guard<std::mutex> lock(g_online_mu);
    g_online[slot->id] = slot;
}

void unpublish(const std::string& id) {
    std::lock_guard<std::mutex> lock(g_online_mu);
    g_online.erase(normalize_id(id));
}

bool send_auth_status(FramedConnection& conn, AuthStatus st) {
    return send_msg(conn, MsgType::AuthResult, encode_auth_result, AuthResult{st});
}

void handle_agent(FramedConnection conn, RoleHello hello, AgentStore& store, std::atomic<int>& online,
                  std::atomic<bool>* stop) {
    (void)stop;
    std::shared_ptr<AgentSlot> slot = std::make_shared<AgentSlot>();

    if (hello.id.empty()) {
        AssignId assigned;
        assigned.id = store.allocate_id();
        if (assigned.id.empty() || !random_bytes(assigned.secret, kDeviceSecretBytes)) {
            send_auth_status(conn, AuthStatus::BadProtocol);
            return;
        }
        StoredAgent rec;
        rec.id = assigned.id;
        std::memcpy(rec.secret, assigned.secret, kDeviceSecretBytes);
        rec.created_unix = unix_now();
        rec.last_seen_unix = rec.created_unix;
        store.put(rec);
        if (!send_msg(conn, MsgType::AssignId, encode_assign_id, assigned)) {
            return;
        }
        slot->id = assigned.id;
        log_info("registered new agent %s", format_id(slot->id).c_str());
    } else {
        const std::string id = normalize_id(hello.id);
        StoredAgent rec;
        if (!store.get(id, rec)) {
            send_auth_status(conn, AuthStatus::UnknownId);
            return;
        }
        AuthChallenge ch{};
        if (!random_bytes(ch.nonce, kNonceBytes) || !send_msg(conn, MsgType::AuthChallenge, encode_auth_challenge, ch)) {
            return;
        }
        Message msg;
        if (!conn.recv(msg) || msg.type != MsgType::AgentLogin) {
            send_auth_status(conn, AuthStatus::BadPassword);
            return;
        }
        AgentLogin login{};
        if (!decode_agent_login(msg.payload, login)) {
            send_auth_status(conn, AuthStatus::BadPassword);
            return;
        }
        std::uint8_t expect[kSha256Bytes];
        device_login_digest(ch.nonce, rec.secret, expect);
        if (std::memcmp(expect, login.digest, kSha256Bytes) != 0) {
            send_auth_status(conn, AuthStatus::BadPassword);
            log_warn("agent login failed for %s", format_id(id).c_str());
            return;
        }
        if (!send_auth_status(conn, AuthStatus::Ok)) {
            return;
        }
        slot->id = id;
        store.touch(id, unix_now());
        log_info("agent login %s", format_id(id).c_str());
    }

    slot->agent.conn = std::make_unique<FramedConnection>(std::move(conn));
    publish_online(slot);
    online.fetch_add(1);

    while (slot->agent.valid()) {
        if (!slot->agent.wait(250)) {
            continue;
        }
        Message msg;
        if (!slot->agent.recv(msg)) {
            break;
        }
        if (msg.type == MsgType::Heartbeat) {
            store.touch(slot->id, unix_now());
            if (slot->busy.load()) {
                slot->viewer.send(msg.type, msg.payload);
            }
            continue;
        }
        if (msg.type == MsgType::Disconnect) {
            slot->viewer.send(MsgType::Disconnect, msg.payload);
            slot->viewer.close();
            slot->busy.store(0);
            continue;
        }
        if (slot->busy.load() && should_relay_to_viewer(msg.type)) {
            slot->viewer.send(msg.type, msg.payload);
        }
    }

    slot->viewer.close();
    unpublish(slot->id);
    online.fetch_sub(1);
    log_info("agent %s offline", format_id(slot->id).c_str());
}

void handle_viewer(FramedConnection conn, RoleHello hello) {
    const std::string id = normalize_id(hello.id);
    if (!valid_id(id)) {
        send_auth_status(conn, AuthStatus::UnknownId);
        return;
    }
    auto slot = find_online(id);
    if (!slot) {
        send_auth_status(conn, AuthStatus::Offline);
        log_info("viewer asked for %s — offline", format_id(id).c_str());
        return;
    }
    int expected = 0;
    if (!slot->busy.compare_exchange_strong(expected, 1)) {
        send_auth_status(conn, AuthStatus::Busy);
        return;
    }
    slot->viewer.conn = std::make_unique<FramedConnection>(std::move(conn));
    std::vector<std::uint8_t> incoming;
    encode_session_incoming(incoming);
    if (!slot->agent.send(MsgType::SessionIncoming, incoming)) {
        send_auth_status(*slot->viewer.conn, AuthStatus::Offline);
        slot->viewer.close();
        slot->busy.store(0);
        return;
    }
    log_info("relaying viewer to agent %s", format_id(id).c_str());

    while (slot->viewer.valid() && slot->agent.valid()) {
        if (!slot->viewer.wait(250)) {
            continue;
        }
        Message msg;
        if (!slot->viewer.recv(msg)) {
            break;
        }
        if (msg.type == MsgType::Disconnect) {
            slot->agent.send(MsgType::Disconnect, msg.payload);
            break;
        }
        slot->agent.send(msg.type, msg.payload);
    }

    slot->viewer.close();
    slot->busy.store(0);
    log_info("viewer session ended for %s", format_id(id).c_str());
}

} // namespace

HubServer::HubServer(HubConfig cfg) : cfg_(std::move(cfg)), store_(cfg_.data_path) {}

void HubServer::run(std::atomic<bool>& stop) {
    store_.load();
    std::string err;
    TcpSocket listener = TcpSocket::listen_on(cfg_.bind_ip, cfg_.port, &err);
    if (!listener.valid()) {
        log_error("hub listen failed on %s:%u: %s", cfg_.bind_ip.c_str(), cfg_.port, err.c_str());
        return;
    }
    log_info("hub listening on %s:%u (state %s)", cfg_.bind_ip.c_str(), static_cast<unsigned>(cfg_.port),
             cfg_.data_path.c_str());

    while (!stop.load()) {
        if (!listener.wait_readable(200)) {
            continue;
        }
        if (stop.load()) {
            break;
        }
        TcpSocket sock = listener.accept_one(nullptr, &err);
        if (!sock.valid()) {
            continue;
        }
        workers_.fetch_add(1);
        std::thread([this, s = std::move(sock)]() mutable {
            handle_client(std::move(s));
            workers_.fetch_sub(1);
        }).detach();
    }
    {
        std::lock_guard<std::mutex> lock(g_online_mu);
        for (auto& kv : g_online) {
            kv.second->agent.close();
            kv.second->viewer.close();
        }
    }
    for (int i = 0; i < 40 && workers_.load() > 0; ++i) {
        std::this_thread::sleep_for(std::chrono::milliseconds(50));
    }
}

void HubServer::handle_client(TcpSocket sock) {
    FramedConnection conn(std::move(sock));
    Message msg;
    if (!conn.recv(msg) || msg.type != MsgType::RoleHello) {
        send_auth_status(conn, AuthStatus::BadProtocol);
        return;
    }
    RoleHello hello{};
    if (!decode_role_hello(msg.payload, hello) || hello.proto_version != kProtocolVersion) {
        send_auth_status(conn, AuthStatus::BadProtocol);
        return;
    }
    if (hello.role == PeerRole::Agent) {
        handle_agent(std::move(conn), hello, store_, online_, nullptr);
    } else if (hello.role == PeerRole::Viewer) {
        handle_viewer(std::move(conn), hello);
    } else {
        send_auth_status(conn, AuthStatus::BadProtocol);
    }
}

} // namespace crd
