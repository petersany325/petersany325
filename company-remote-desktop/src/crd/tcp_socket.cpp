#include "crd/tcp_socket.hpp"

#include "crd/log.hpp"

#include <cstring>

#ifndef _WIN32
#include <chrono>
#endif

namespace crd {
namespace {

int last_socket_error() {
#ifdef _WIN32
    return WSAGetLastError();
#else
    return errno;
#endif
}

void set_cloexec_or_nonblock_defaults(SocketHandle s) {
#ifdef _WIN32
    (void)s;
#else
    const int flags = fcntl(s, F_GETFD, 0);
    if (flags >= 0) {
        fcntl(s, F_SETFD, flags | FD_CLOEXEC);
    }
#endif
}

bool set_nonblocking(SocketHandle s, bool enabled) {
#ifdef _WIN32
    u_long mode = enabled ? 1 : 0;
    return ioctlsocket(s, FIONBIO, &mode) == 0;
#else
    int flags = fcntl(s, F_GETFL, 0);
    if (flags < 0) {
        return false;
    }
    if (enabled) {
        flags |= O_NONBLOCK;
    } else {
        flags &= ~O_NONBLOCK;
    }
    return fcntl(s, F_SETFL, flags) == 0;
#endif
}

bool wait_fd(SocketHandle s, bool for_write, int timeout_ms) {
    fd_set fds;
    FD_ZERO(&fds);
    FD_SET(s, &fds);
    timeval tv{};
    timeval* ptv = nullptr;
    if (timeout_ms >= 0) {
        tv.tv_sec = timeout_ms / 1000;
        tv.tv_usec = (timeout_ms % 1000) * 1000;
        ptv = &tv;
    }
    const int rc = select(static_cast<int>(s + 1), for_write ? nullptr : &fds, for_write ? &fds : nullptr, nullptr, ptv);
    return rc > 0;
}

} // namespace

TcpSocket::TcpSocket(SocketHandle handle) : handle_(handle) { set_cloexec_or_nonblock_defaults(handle_); }

TcpSocket::TcpSocket(TcpSocket&& other) noexcept : handle_(other.handle_), peer_ip_(std::move(other.peer_ip_)) {
    other.handle_ = kInvalidSocket;
}

TcpSocket& TcpSocket::operator=(TcpSocket&& other) noexcept {
    if (this != &other) {
        close();
        handle_ = other.handle_;
        peer_ip_ = std::move(other.peer_ip_);
        other.handle_ = kInvalidSocket;
    }
    return *this;
}

TcpSocket::~TcpSocket() { close(); }

bool TcpSocket::startup(std::string* err) {
#ifdef _WIN32
    WSADATA data{};
    const int rc = WSAStartup(MAKEWORD(2, 2), &data);
    if (rc != 0) {
        if (err) {
            *err = "WSAStartup failed: " + std::to_string(rc);
        }
        return false;
    }
#else
    (void)err;
#endif
    return true;
}

void TcpSocket::cleanup() {
#ifdef _WIN32
    WSACleanup();
#endif
}

TcpSocket TcpSocket::listen_on(const std::string& bind_ip, std::uint16_t port, std::string* err) {
    TcpSocket sock(::socket(AF_INET, SOCK_STREAM, IPPROTO_TCP));
    if (!sock.valid()) {
        if (err) {
            *err = "socket() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }

#ifdef _WIN32
    BOOL yes = TRUE;
    setsockopt(sock.handle_, SOL_SOCKET, SO_REUSEADDR, reinterpret_cast<const char*>(&yes), sizeof(yes));
#else
    int yes = 1;
    setsockopt(sock.handle_, SOL_SOCKET, SO_REUSEADDR, &yes, sizeof(yes));
#endif

    sockaddr_in addr{};
    addr.sin_family = AF_INET;
    addr.sin_port = htons(port);
    if (bind_ip.empty() || bind_ip == "0.0.0.0") {
        addr.sin_addr.s_addr = htonl(INADDR_ANY);
    } else if (inet_pton(AF_INET, bind_ip.c_str(), &addr.sin_addr) != 1) {
        if (err) {
            *err = "invalid bind address: " + bind_ip;
        }
        return {};
    }

    if (bind(sock.handle_, reinterpret_cast<sockaddr*>(&addr), sizeof(addr)) != 0) {
        if (err) {
            *err = "bind() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }
    if (listen(sock.handle_, 4) != 0) {
        if (err) {
            *err = "listen() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }
    sock.set_nodelay(true);
    return sock;
}

TcpSocket TcpSocket::connect_to(const std::string& host, std::uint16_t port, int timeout_ms, std::string* err) {
    TcpSocket sock(::socket(AF_INET, SOCK_STREAM, IPPROTO_TCP));
    if (!sock.valid()) {
        if (err) {
            *err = "socket() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }

    sockaddr_in addr{};
    addr.sin_family = AF_INET;
    addr.sin_port = htons(port);
    if (inet_pton(AF_INET, host.c_str(), &addr.sin_addr) != 1) {
        addrinfo hints{};
        hints.ai_family = AF_INET;
        hints.ai_socktype = SOCK_STREAM;
        addrinfo* result = nullptr;
        if (getaddrinfo(host.c_str(), nullptr, &hints, &result) != 0 || !result) {
            if (err) {
                *err = "cannot resolve host: " + host;
            }
            return {};
        }
        addr.sin_addr = reinterpret_cast<sockaddr_in*>(result->ai_addr)->sin_addr;
        freeaddrinfo(result);
    }

    set_nonblocking(sock.handle_, true);
    const int cr = connect(sock.handle_, reinterpret_cast<sockaddr*>(&addr), sizeof(addr));
#ifdef _WIN32
    const bool in_progress = (cr != 0) && (WSAGetLastError() == WSAEWOULDBLOCK);
#else
    const bool in_progress = (cr != 0) && (errno == EINPROGRESS);
#endif
    if (cr != 0 && !in_progress) {
        if (err) {
            *err = "connect() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }
    if (cr != 0 && !wait_fd(sock.handle_, true, timeout_ms)) {
        if (err) {
            *err = "connect timed out";
        }
        return {};
    }

    int so_error = 0;
#ifdef _WIN32
    int len = sizeof(so_error);
    getsockopt(sock.handle_, SOL_SOCKET, SO_ERROR, reinterpret_cast<char*>(&so_error), &len);
#else
    socklen_t len = sizeof(so_error);
    getsockopt(sock.handle_, SOL_SOCKET, SO_ERROR, &so_error, &len);
#endif
    if (so_error != 0) {
        if (err) {
            *err = "connect failed: " + std::to_string(so_error);
        }
        return {};
    }

    set_nonblocking(sock.handle_, false);
    sock.set_nodelay(true);
    sock.peer_ip_ = host;
    return sock;
}

TcpSocket TcpSocket::accept_one(std::string* peer_ip, std::string* err) {
    sockaddr_in addr{};
#ifdef _WIN32
    int addrlen = sizeof(addr);
#else
    socklen_t addrlen = sizeof(addr);
#endif
    const SocketHandle s = ::accept(handle_, reinterpret_cast<sockaddr*>(&addr), &addrlen);
    if (s == kInvalidSocket) {
        if (err) {
            *err = "accept() failed: " + std::to_string(last_socket_error());
        }
        return {};
    }
    TcpSocket client(s);
    char ip[INET_ADDRSTRLEN] = {};
    inet_ntop(AF_INET, &addr.sin_addr, ip, sizeof(ip));
    client.peer_ip_ = ip;
    if (peer_ip) {
        *peer_ip = client.peer_ip_;
    }
    client.set_nodelay(true);
    return client;
}

bool TcpSocket::send_all(const void* data, std::size_t n) {
    const auto* p = static_cast<const char*>(data);
    std::size_t sent = 0;
    while (sent < n) {
#ifdef _WIN32
        const int rc = ::send(handle_, p + sent, static_cast<int>(n - sent), 0);
#else
        const ssize_t rc = ::send(handle_, p + sent, n - sent, MSG_NOSIGNAL);
#endif
        if (rc <= 0) {
            return false;
        }
        sent += static_cast<std::size_t>(rc);
    }
    return true;
}

bool TcpSocket::recv_all(void* data, std::size_t n) {
    auto* p = static_cast<char*>(data);
    std::size_t got = 0;
    while (got < n) {
#ifdef _WIN32
        const int rc = ::recv(handle_, p + got, static_cast<int>(n - got), 0);
#else
        const ssize_t rc = ::recv(handle_, p + got, n - got, 0);
#endif
        if (rc <= 0) {
            return false;
        }
        got += static_cast<std::size_t>(rc);
    }
    return true;
}

bool TcpSocket::wait_readable(int timeout_ms) {
    if (!valid()) {
        return false;
    }
    return wait_fd(handle_, false, timeout_ms);
}

bool TcpSocket::select2(TcpSocket* a, TcpSocket* b, int timeout_ms, bool* a_ready, bool* b_ready) {
    if (a_ready) {
        *a_ready = false;
    }
    if (b_ready) {
        *b_ready = false;
    }
    fd_set fds;
    FD_ZERO(&fds);
    SocketHandle max_fd = 0;
    if (a && a->valid()) {
        FD_SET(a->handle_, &fds);
        max_fd = a->handle_;
    }
    if (b && b->valid()) {
        FD_SET(b->handle_, &fds);
        if (b->handle_ > max_fd) {
            max_fd = b->handle_;
        }
    }
    timeval tv{};
    timeval* ptv = nullptr;
    if (timeout_ms >= 0) {
        tv.tv_sec = timeout_ms / 1000;
        tv.tv_usec = (timeout_ms % 1000) * 1000;
        ptv = &tv;
    }
    const int rc = select(static_cast<int>(max_fd + 1), &fds, nullptr, nullptr, ptv);
    if (rc <= 0) {
        return rc == 0;
    }
    if (a && a->valid() && a_ready) {
        *a_ready = FD_ISSET(a->handle_, &fds) != 0;
    }
    if (b && b->valid() && b_ready) {
        *b_ready = FD_ISSET(b->handle_, &fds) != 0;
    }
    return true;
}

void TcpSocket::set_nodelay(bool on) {
    if (!valid()) {
        return;
    }
#ifdef _WIN32
    BOOL v = on ? TRUE : FALSE;
    setsockopt(handle_, IPPROTO_TCP, TCP_NODELAY, reinterpret_cast<const char*>(&v), sizeof(v));
#else
    int v = on ? 1 : 0;
    setsockopt(handle_, IPPROTO_TCP, TCP_NODELAY, &v, sizeof(v));
#endif
}

void TcpSocket::close() {
    if (handle_ == kInvalidSocket) {
        return;
    }
#ifdef _WIN32
    closesocket(handle_);
#else
    ::close(handle_);
#endif
    handle_ = kInvalidSocket;
}

} // namespace crd
