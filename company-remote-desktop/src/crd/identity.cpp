#include "crd/identity.hpp"

#include "crd/util.hpp"

#include <fstream>
#include <sstream>

namespace crd {
namespace {

bool extract_quoted(const std::string& json, const char* key, std::string& out) {
    const std::string needle = std::string("\"") + key + "\"";
    auto pos = json.find(needle);
    if (pos == std::string::npos) {
        return false;
    }
    pos = json.find(':', pos + needle.size());
    if (pos == std::string::npos) {
        return false;
    }
    pos = json.find('"', pos + 1);
    if (pos == std::string::npos) {
        return false;
    }
    const auto end = json.find('"', pos + 1);
    if (end == std::string::npos) {
        return false;
    }
    out = json.substr(pos + 1, end - pos - 1);
    return true;
}

} // namespace

bool load_identity(const std::string& path, AgentIdentity& id) {
    std::ifstream in(path);
    if (!in) {
        return false;
    }
    std::ostringstream ss;
    ss << in.rdbuf();
    const std::string json = ss.str();
    std::string hex;
    if (!extract_quoted(json, "id", id.id) || !extract_quoted(json, "device_secret_hex", hex)) {
        return false;
    }
    id.id = normalize_id(id.id);
    extract_quoted(json, "access_password", id.access_password);
    if (!from_hex(hex, id.device_secret, kDeviceSecretBytes)) {
        return false;
    }
    id.has_secret = true;
    return valid_id(id.id);
}

bool save_identity(const std::string& path, const AgentIdentity& id) {
    std::ofstream out(path, std::ios::trunc);
    if (!out) {
        return false;
    }
    out << "{\n  \"id\": \"" << id.id << "\",\n  \"device_secret_hex\": \""
        << to_hex(id.device_secret, kDeviceSecretBytes) << "\",\n  \"access_password\": \"" << id.access_password
        << "\"\n}\n";
    return true;
}

} // namespace crd
