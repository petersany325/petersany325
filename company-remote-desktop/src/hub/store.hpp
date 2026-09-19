#pragma once

#include "crd/version.hpp"

#include <cstdint>
#include <mutex>
#include <string>
#include <unordered_map>
#include <vector>

namespace crd {

struct StoredAgent {
    std::string id;
    std::uint8_t secret[kDeviceSecretBytes]{};
    std::uint64_t created_unix = 0;
    std::uint64_t last_seen_unix = 0;
};

class AgentStore {
public:
    explicit AgentStore(std::string path);
    bool load();
    bool save() const;

    bool get(const std::string& id, StoredAgent& out) const;
    bool put(const StoredAgent& rec);
    bool touch(const std::string& id, std::uint64_t last_seen);
    std::string allocate_id();
    std::vector<StoredAgent> all() const;

private:
    std::string path_;
    mutable std::mutex mu_;
    std::unordered_map<std::string, StoredAgent> rows_;
};

} // namespace crd
