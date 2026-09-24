#include "safety.hpp"

#include "disk_io.hpp"

#include <algorithm>
#include <cctype>

#ifdef _WIN32
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#include <shellapi.h>
#else
#include <unistd.h>
#endif

namespace hsc {
namespace {

std::string normalize_path(std::string p) {
    std::transform(p.begin(), p.end(), p.begin(),
                   [](unsigned char c) { return static_cast<char>(std::tolower(c)); });
    return p;
}

}  // namespace

SafetyResult check_clone_safety(const SafetyRequest& req) {
    SafetyResult r;
    if (req.source_path.empty()) {
        r.message = "No source selected. Choose a disk or image first.";
        return r;
    }
    if (req.dest_path.empty()) {
        r.message = "No destination selected. The program will not write anything until a "
                    "destination is chosen explicitly.";
        return r;
    }
    if (req.dest_is_folder) {
        r.ok = true;
        r.message = "Safety checks passed (folder destination)";
        return r;
    }
    if (normalize_path(req.source_path) == normalize_path(req.dest_path)) {
        r.message = "Source and destination are the same device. Refusing to clone.";
        return r;
    }
    if (req.dest_is_boot_disk || path_is_boot_disk(req.dest_path)) {
        r.needs_boot_confirm = true;
        if (req.typed_confirmation != "OVERWRITE BOOT DISK") {
            r.message = "Destination is the Windows/system boot disk. Type OVERWRITE BOOT DISK "
                      "to confirm. This will destroy the OS on that drive.";
            return r;
        }
    }
    r.ok = true;
    r.message = "Safety checks passed";
    return r;
}

bool is_elevated() {
#ifdef _WIN32
    BOOL admin = FALSE;
    SID_IDENTIFIER_AUTHORITY nt = SECURITY_NT_AUTHORITY;
    PSID group = nullptr;
    if (AllocateAndInitializeSid(&nt, 2, SECURITY_BUILTIN_DOMAIN_RID, DOMAIN_ALIAS_RID_ADMINS, 0,
                                  0, 0, 0, 0, 0, &group)) {
        CheckTokenMembership(nullptr, group, &admin);
        FreeSid(group);
    }
    return admin == TRUE;
#else
    return geteuid() == 0;
#endif
}

}  // namespace hsc
