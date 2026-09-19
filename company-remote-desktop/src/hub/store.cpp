#include "hub/store.hpp"

#include "crd/log.hpp"
#include "crd/util.hpp"

#include <fstream>
#include <sstream>

namespace crd {

AgentStore::AgentStore(std::string path) : path_(std::move(path)) {}

bool AgentStore::load() {
    std::lock_guard<std::mutex> lock(mu_);
    rows_.clear();
    std::ifstream in(path_);
    if (!in) {
        return true; // first run
    }
    std::string line;
    if (!std::getline(in, line) || line != "CRDH1") {
        log_warn("hub store missing CRDH1 header; starting empty");
        return true;
    }
    while (std::getline(in, line)) {
        if (line.empty()) {
            continue;
        }
        std::istringstream ss(line);
        StoredAgent rec;
        std::string hex;
        if (!(ss >> rec.id >> hex >> rec.created_unix >> rec.last_seen_unix)) {
            continue;
        }
        rec.id = normalize_id(rec.id);
        if (!valid_id(rec.id) || !from_hex(hex, rec.secret, kDeviceSecretBytes)) {
            continue;
        }
        rows_[rec.id] = rec;
    }
    return true;
}

bool AgentStore::save() const {
    std::lock_guard<std::mutex> lock(mu_);
    std::ofstream out(path_, std::ios::trunc);
    if (!out) {
        log_error("cannot write hub store %s", path_.c_str());
        return false;
    }
    out << "CRDH1\n";
    for (const auto& kv : rows_) {
        const auto& r = kv.second;
        out << r.id << ' ' << to_hex(r.secret, kDeviceSecretBytes) << ' ' << r.created_unix << ' ' << r.last_seen_unix
            << '\n';
    }
    return true;
}

bool AgentStore::get(const std::string& id, StoredAgent& out) const {
    std::lock_guard<std::mutex> lock(mu_);
    auto it = rows_.find(normalize_id(id));
    if (it == rows_.end()) {
        return false;
    }
    out = it->second;
    return true;
}

bool AgentStore::put(const StoredAgent& rec) {
    {
        std::lock_guard<std::mutex> lock(mu_);
        rows_[rec.id] = rec;
    }
    return save();
}

bool AgentStore::touch(const std::string& id, std::uint64_t last_seen) {
    {
        std::lock_guard<std::mutex> lock(mu_);
        auto it = rows_.find(normalize_id(id));
        if (it == rows_.end()) {
            return false;
        }
        it->second.last_seen_unix = last_seen;
    }
    return save();
}

std::string AgentStore::allocate_id() {
    std::lock_guard<std::mutex> lock(mu_);
    for (int i = 0; i < 64; ++i) {
        const std::string id = generate_id();
        if (!id.empty() && rows_.find(id) == rows_.end()) {
            return id;
        }
    }
    return {};
}

std::vector<StoredAgent> AgentStore::all() const {
    std::lock_guard<std::mutex> lock(mu_);
    std::vector<StoredAgent> out;
    out.reserve(rows_.size());
    for (const auto& kv : rows_) {
        out.push_back(kv.second);
    }
    return out;
}

} // namespace crd
