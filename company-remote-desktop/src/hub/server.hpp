#pragma once

#include "crd/tcp_socket.hpp"
#include "hub/store.hpp"

#include <atomic>
#include <cstdint>
#include <string>

namespace crd {

struct HubConfig {
    std::string bind_ip = "0.0.0.0";
    std::uint16_t port = kDefaultPort;
    std::string data_path = "hub-state.db";
};

class HubServer {
public:
    explicit HubServer(HubConfig cfg);
    void run(std::atomic<bool>& stop);
    int online_count() const { return online_.load(); }

private:
    void handle_client(TcpSocket sock);

    HubConfig cfg_;
    AgentStore store_;
    std::atomic<int> online_{0};
    std::atomic<int> workers_{0};
};

} // namespace crd
