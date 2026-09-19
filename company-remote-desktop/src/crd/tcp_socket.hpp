#pragma once

#include "crd/platform.hpp"

#include <cstdint>
#include <string>
#include <utility>
#include <vector>

namespace crd {

#ifdef _WIN32
using SocketHandle = SOCKET;
inline constexpr SocketHandle kInvalidSocket = INVALID_SOCKET;
#else
using SocketHandle = int;
inline constexpr SocketHandle kInvalidSocket = -1;
#endif

class TcpSocket {
public:
    TcpSocket() = default;
    explicit TcpSocket(SocketHandle handle);
    TcpSocket(const TcpSocket&) = delete;
    TcpSocket& operator=(const TcpSocket&) = delete;
    TcpSocket(TcpSocket&& other) noexcept;
    TcpSocket& operator=(TcpSocket&& other) noexcept;
    ~TcpSocket();

    static bool startup(std::string* err = nullptr);
    static void cleanup();

    static TcpSocket listen_on(const std::string& bind_ip, std::uint16_t port, std::string* err = nullptr);
    static TcpSocket connect_to(const std::string& host, std::uint16_t port, int timeout_ms,
                                std::string* err = nullptr);

    TcpSocket accept_one(std::string* peer_ip = nullptr, std::string* err = nullptr);
    bool send_all(const void* data, std::size_t n);
    bool recv_all(void* data, std::size_t n);
    bool wait_readable(int timeout_ms);
    static bool select2(TcpSocket* a, TcpSocket* b, int timeout_ms, bool* a_ready, bool* b_ready);

    void set_nodelay(bool on);
    void close();
    bool valid() const { return handle_ != kInvalidSocket; }
    SocketHandle handle() const { return handle_; }
    std::string peer_ip() const { return peer_ip_; }

private:
    SocketHandle handle_ = kInvalidSocket;
    std::string peer_ip_;
};

} // namespace crd
