#include "crd/cli.hpp"
#include "crd/log.hpp"
#include "crd/tcp_socket.hpp"
#include "hub/server.hpp"

#include <atomic>
#include <csignal>
#include <cstdio>
#include <string>

namespace {
std::atomic<bool> g_stop{false};
void on_signal(int) { g_stop.store(true); }
} // namespace

int main(int argc, char** argv) {
    crd::log_set_prefix("hub");
    crd::HubConfig cfg;
    bool help = false;
    for (int i = 1; i < argc; ++i) {
        if (crd::arg_eq(argv[i], "--help") || crd::arg_eq(argv[i], "-h")) {
            help = true;
        } else if (crd::arg_eq(argv[i], "--bind") && i + 1 < argc) {
            cfg.bind_ip = argv[++i];
        } else if (crd::arg_eq(argv[i], "--port") && i + 1 < argc) {
            crd::parse_u16(argv[++i], cfg.port);
        } else if (crd::arg_eq(argv[i], "--data") && i + 1 < argc) {
            cfg.data_path = argv[++i];
        }
    }
    if (help) {
        std::printf("Company Remote Desktop — Hub (rendezvous / relay)\n\n");
        std::printf("  hub [--bind 0.0.0.0] [--port 5938] [--data hub-state.db]\n\n");
        std::printf("Run on a company server. Agents register and receive IDs.\n");
        std::printf("Viewers connect by ID through this process (full TCP relay).\n");
        return 0;
    }

    std::string err;
    if (!crd::TcpSocket::startup(&err)) {
        crd::log_error("%s", err.c_str());
        return 1;
    }
    std::signal(SIGINT, on_signal);
    std::signal(SIGTERM, on_signal);

    crd::HubServer server(cfg);
    server.run(g_stop);
    crd::TcpSocket::cleanup();
    return 0;
}
