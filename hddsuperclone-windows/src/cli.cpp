#include "app.hpp"

#include "clone_engine.hpp"
#include "disk_io.hpp"
#include "safety.hpp"
#include "script_engine.hpp"
#include "usb_relay.hpp"
#include "virtual_disk.hpp"

#include <cstdlib>
#include <cstdio>
#include <cstring>
#include <fstream>
#include <string>
#include <vector>

namespace hsc {
namespace {

void usage() {
    std::fprintf(stderr,
                 "HDDSuperClone Windows port\n"
                 "Usage:\n"
                 "  hddsuperclone-windows                 # GUI\n"
                 "  hddsuperclone-windows --cli --source SRC --dest DST --log FILE [options]\n"
                 "  hddsuperclone-windows --script FILE [--source SRC]\n"
                 "Options:\n"
                 "  --mode auto|generic|ata|scsi|ahci|ide|usb|fpdma\n"
                 "  --cluster N --retries N --skip-kib N\n"
                 "  --rebuild-assist --virtual-disk\n"
                 "  --relay PATH --relay-channel N --relay-on-error\n"
                 "  --inject-bad LBA[,LBA...]   (image files only, testing)\n"
                 "  --confirm-boot-overwrite     (required if dest is the boot disk)\n"
                 "  --source-file --dest-file\n");
}

IoMode parse_mode(const char* s) {
    if (!s) return IoMode::Auto;
    if (std::strcmp(s, "generic") == 0) return IoMode::Generic;
    if (std::strcmp(s, "ata") == 0) return IoMode::AtaPassthrough;
    if (std::strcmp(s, "scsi") == 0) return IoMode::ScsiPassthrough;
    if (std::strcmp(s, "ahci") == 0) return IoMode::DirectAhci;
    if (std::strcmp(s, "ide") == 0) return IoMode::DirectIde;
    if (std::strcmp(s, "usb") == 0) return IoMode::UsbDirect;
    if (std::strcmp(s, "fpdma") == 0 || std::strcmp(s, "rebuild") == 0) return IoMode::RebuildAssist;
    return IoMode::Auto;
}

}  // namespace

int run_cli(int argc, char** argv) {
    std::string source, dest, log, script;
    bool source_file = false, dest_file = false;
    CloneSettings s;
    std::vector<uint64_t> bad;
    std::string boot;
    for (int i = 1; i < argc; ++i) {
        std::string a = argv[i];
        auto next = [&]() -> const char* {
            return (i + 1 < argc) ? argv[++i] : "";
        };
        if (a == "--cli") continue;
        if (a == "--help" || a == "-h") {
            usage();
            return 0;
        }
        if (a == "--source") source = next();
        else if (a == "--dest") dest = next();
        else if (a == "--log") log = next();
        else if (a == "--mode") s.io_mode = parse_mode(next());
        else if (a == "--cluster") s.cluster_size = std::atoi(next());
        else if (a == "--retries") s.retries = std::atoi(next());
        else if (a == "--skip-kib") {
            int kib = std::atoi(next());
            s.min_skip_sectors = (static_cast<int64_t>(kib) * 1024) / s.sector_size;
        } else if (a == "--source-file") source_file = true;
        else if (a == "--dest-file") dest_file = true;
        else if (a == "--confirm-boot-overwrite") boot = "OVERWRITE BOOT DISK";
        else if (a == "--rebuild-assist") s.rebuild_assist = true;
        else if (a == "--virtual-disk") s.virtual_disk_dest = true;
        else if (a == "--relay") s.relay_path = next();
        else if (a == "--relay-channel") s.relay_channel = std::atoi(next());
        else if (a == "--relay-on-error") s.relay_on_error = true;
        else if (a == "--script") script = next();
        else if (a == "--inject-bad") {
            std::string list = next();
            size_t p = 0;
            while (p < list.size()) {
                size_t c = list.find(',', p);
                bad.push_back(std::strtoull(list.c_str() + p, nullptr, 0));
                p = (c == std::string::npos) ? list.size() : c + 1;
            }
            source_file = true;
        } else if (a == "--no-phase1") s.no_phase1 = true;
        else if (a == "--no-phase2") s.no_phase2 = true;
        else if (a == "--no-skip") s.skip_enabled = false;
        else {
            std::fprintf(stderr, "Unknown argument: %s\n", a.c_str());
            usage();
            return 2;
        }
    }

    if (!script.empty()) {
        ScriptEngine se;
        auto slash = script.find_last_of("/\\");
        if (slash != std::string::npos) se.set_script_dir(script.substr(0, slash));
        std::unique_ptr<DiskSession> disk;
        if (!source.empty()) {
            disk = open_disk(source, false, source_file);
            if (disk) se.set_disk(disk.get());
        }
        auto r = se.run_file(script);
        std::fputs(r.output.c_str(), stdout);
        return r.ok ? 0 : 1;
    }

    if (source.empty() || dest.empty()) {
        usage();
        return 2;
    }
    if (log.empty()) log = "clone.progress.log";
    CloneEngine eng;
    std::string err;
    if (!eng.prepare(source, source_file, dest, dest_file, log, s, boot, err, bad)) {
        std::fprintf(stderr, "%s\n", err.c_str());
        return 1;
    }
    CloneResult r = eng.run();
    auto p = eng.progress();
    std::printf("result=%d finished=%llu bad=%llu nontried=%llu phase=%s mode=%s\n",
                static_cast<int>(r),
                static_cast<unsigned long long>(p.stats.finished_sectors),
                static_cast<unsigned long long>(p.stats.bad_sectors),
                static_cast<unsigned long long>(p.stats.nontried_sectors),
                p.phase_name.c_str(),
                io_mode_name(s.io_mode));
    return r == CloneResult::Ok ? 0 : 1;
}

}  // namespace hsc
