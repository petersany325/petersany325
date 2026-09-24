#pragma once

#include "types.hpp"

#include <string>

namespace hsc {

struct SafetyRequest {
    std::string source_path;
    std::string dest_path;
    bool source_is_file = false;
    bool dest_is_file = false;
    bool dest_is_boot_disk = false;
    std::string typed_confirmation;  // must be OVERWRITE BOOT DISK for boot dest
    bool dest_is_folder = false;     // file-recovery destination
};

struct SafetyResult {
    bool ok = false;
    bool needs_boot_confirm = false;
    std::string message;
};

SafetyResult check_clone_safety(const SafetyRequest& req);
bool is_elevated();

}  // namespace hsc
