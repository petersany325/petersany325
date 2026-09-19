#pragma once

#include "crd/identity.hpp"
#include "crd/session.hpp"

#include <atomic>
#include <cstdint>
#include <mutex>
#include <string>

namespace crd {

struct AgentConfig {
    std::string hub_host = kDefaultHubHost;
    std::uint16_t hub_port = kDefaultPort;
    std::string identity_path;
    std::string access_password;
    int jpeg_quality = kDefaultJpegQuality;
    int fps = kDefaultFps;
};

struct AgentStats {
    std::atomic<int> online{0};
    std::atomic<int> in_session{0};
    std::atomic<std::uint64_t> frames_sent{0};
    std::atomic<std::uint64_t> bytes_sent{0};
    std::atomic<std::uint32_t> desktop_w{0};
    std::atomic<std::uint32_t> desktop_h{0};
    std::mutex mu;
    std::string id;
    std::string hub;
    std::string last_error;
};

class AgentRuntime {
public:
    AgentRuntime(AgentConfig cfg, IFrameSource& source, IInputSink& sink, AgentStats& stats);
    void run(std::atomic<bool>& stop);
    void set_access_password(const std::string& password);
    std::string access_password() const;
    std::string id() const;

private:
    bool register_or_login(FramedConnection& conn, std::string* err);
    void session_loop(FramedConnection& conn, std::atomic<bool>& stop);

    AgentConfig cfg_;
    IFrameSource& source_;
    IInputSink& sink_;
    AgentStats& stats_;
    AgentIdentity identity_{};
    mutable std::mutex pw_mu_;
};

} // namespace crd
