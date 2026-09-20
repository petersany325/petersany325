#include "clone_engine.hpp"
#include "progress_log.hpp"
#include "safety.hpp"
#include "script_engine.hpp"
#include "sector_map.hpp"
#include "usb_relay.hpp"
#include "virtual_disk.hpp"

#include <cassert>
#include <cstdio>
#include <cstring>
#include <filesystem>
#include <fstream>
#include <string>
#include <vector>

namespace fs = std::filesystem;

static void write_pattern_file(const std::string& path, uint64_t sectors, uint32_t ss) {
    std::ofstream out(path, std::ios::binary);
    std::vector<uint8_t> buf(ss);
    for (uint64_t s = 0; s < sectors; ++s) {
        for (uint32_t i = 0; i < ss; ++i) buf[i] = static_cast<uint8_t>((s + i) & 0xff);
        buf[0] = static_cast<uint8_t>(s & 0xff);
        buf[1] = static_cast<uint8_t>((s >> 8) & 0xff);
        out.write(reinterpret_cast<char*>(buf.data()), ss);
    }
}

static int fail(const char* msg) {
    std::fprintf(stderr, "FAIL: %s\n", msg);
    return 1;
}

int main() {
    fs::path tmp = fs::temp_directory_path() / "hsc-windows-test";
    fs::create_directories(tmp);
    const uint32_t ss = 512;
    const uint64_t sectors = 4096;  // 2 MiB
    auto src = (tmp / "src.img").string();
    auto dst = (tmp / "dst.img").string();
    auto logp = (tmp / "clone.log").string();
    write_pattern_file(src, sectors, ss);

    // --- sector map ---
    {
        hsc::SectorMap m;
        m.reset(1000);
        if (m.change_chunk(100, 10, hsc::kFinished, hsc::kStatusMask) < 0) return fail("map change");
        if (m.line_count() != 3) return fail("map split");
        m.change_chunk(110, 10, hsc::kFinished, hsc::kStatusMask);
        if (m.line_count() != 3) return fail("map merge");  // 0-100 nontried, 100-120 fin, 120-1000
        auto st = m.stats();
        if (st.finished_sectors != 20) return fail("map stats");
    }

    // --- safety ---
    {
        hsc::SafetyRequest r;
        auto a = hsc::check_clone_safety(r);
        if (a.ok) return fail("safety empty");
        r.source_path = "a";
        a = hsc::check_clone_safety(r);
        if (a.ok) return fail("safety no dest");
        r.dest_path = "a";
        a = hsc::check_clone_safety(r);
        if (a.ok) return fail("safety same path");
        r.dest_path = "b";
        a = hsc::check_clone_safety(r);
        if (!a.ok) return fail("safety ok");
        r.dest_is_boot_disk = true;
        a = hsc::check_clone_safety(r);
        if (a.ok || !a.needs_boot_confirm) return fail("boot confirm required");
        r.typed_confirmation = "OVERWRITE BOOT DISK";
        a = hsc::check_clone_safety(r);
        if (!a.ok) return fail("boot confirm accepted");
    }

    // --- clone with injected bad sectors ---
    {
        hsc::CloneSettings s;
        s.io_mode = hsc::IoMode::Generic;
        s.cluster_size = 16;
        s.min_skip_sectors = 32;
        s.retries = 1;
        s.log_update_seconds = 0;
        s.no_phase2 = true;  // keep test deterministic/faster
        hsc::CloneEngine eng;
        std::string err;
        std::vector<uint64_t> bad = {100, 101, 250, 1000};
        if (!eng.prepare(src, true, dst, true, logp, s, "", err, bad)) {
            std::fprintf(stderr, "%s\n", err.c_str());
            return fail("prepare");
        }
        auto rc = eng.run();
        if (rc != hsc::CloneResult::Ok) return fail("clone run");
        auto p = eng.progress();
        if (p.stats.finished_sectors + p.stats.bad_sectors != sectors)
            return fail("finished+bad != total");
        if (p.stats.bad_sectors < 1) return fail("expected some bad");
        if (p.current_status != hsc::kFinished && !p.finished) return fail("not finished");

        // resume log round-trip
        hsc::ProgressLog pl;
        std::string lerr;
        if (!pl.load(logp, lerr)) {
            std::fprintf(stderr, "%s\n", lerr.c_str());
            return fail("log load");
        }
        if (pl.map.total_sectors() != sectors) return fail("log total");
        auto bak = (tmp / "clone2.log").string();
        if (!pl.save(bak, lerr)) return fail("log save");
        hsc::ProgressLog pl2;
        if (!pl2.load(bak, lerr)) return fail("log reload");
        if (pl2.map.line_count() != pl.map.line_count()) return fail("log lines");
    }

    // --- dest not chosen ---
    {
        hsc::CloneEngine eng;
        std::string err;
        hsc::CloneSettings s;
        if (eng.prepare(src, true, "", true, logp, s, "", err)) return fail("empty dest should fail");
    }

    // --- USB relay report encoding (dcttech) ---
    {
        uint8_t r[8]{};
        hsc::encode_dcttech_set(r, 1, true);
        if (r[0] != 0xFF || r[1] != 1) return fail("relay on ch1");
        hsc::encode_dcttech_set(r, 0, false);
        if (r[0] != 0xFC) return fail("relay all off");
        hsc::encode_dcttech_set(r, 3, false);
        if (r[0] != 0xFD || r[1] != 3) return fail("relay off ch3");
    }

    // --- HDDSuperTool script subset ---
    {
        hsc::ScriptEngine se;
        auto sr = se.run_text("echo hello\nseti $n = 2\nif $n = 2\necho yes\nendif\necho done\nend\n");
        if (sr.output.find("hello") == std::string::npos) return fail("script echo");
        if (sr.output.find("yes") == std::string::npos) return fail("script if");
        if (sr.output.find("done") == std::string::npos) return fail("script done");
    }

    // --- virtual disk image (sparse file / VHDX) ---
    {
        auto vpath = (tmp / "virt.img").string();
        std::string verr;
        if (!hsc::create_virtual_disk(vpath, 1024 * 1024, verr)) return fail("vdisk create");
        if (!fs::exists(vpath) || fs::file_size(vpath) < 1024 * 1024) return fail("vdisk size");
        std::string mounted;
        if (!hsc::attach_virtual_disk(vpath, mounted, verr)) return fail("vdisk attach");
        if (mounted.empty()) return fail("vdisk mounted path");
    }

    // --- Rebuild Assist / NCQ LBA split (injected ata_lba) ---
    {
        auto src2 = (tmp / "src-ra.img").string();
        auto dst2 = (tmp / "dst-ra.img").string();
        auto log2 = (tmp / "clone-ra.log").string();
        write_pattern_file(src2, 256, ss);
        hsc::CloneSettings s;
        s.io_mode = hsc::IoMode::RebuildAssist;
        s.rebuild_assist = true;
        s.cluster_size = 16;
        s.min_skip_sectors = 16;
        s.retries = 0;
        s.no_phase2 = true;
        s.skip_enabled = false;
        s.log_update_seconds = 0;
        hsc::CloneEngine eng;
        std::string err;
        std::vector<uint64_t> bad = {40};
        if (!eng.prepare(src2, true, dst2, true, log2, s, "", err, bad)) {
            std::fprintf(stderr, "%s\n", err.c_str());
            return fail("rebuild assist prepare");
        }
        auto rc = eng.run();
        if (rc != hsc::CloneResult::Ok) return fail("rebuild assist run");
        auto p = eng.progress();
        if (p.stats.bad_sectors < 1) return fail("rebuild assist expected bad LBA");
        if (p.stats.finished_sectors + p.stats.bad_sectors != 256)
            return fail("rebuild assist finished+bad");
    }

    // --- io mode names cover every recovery path ---
    {
        if (std::strcmp(hsc::io_mode_name(hsc::IoMode::DirectAhci), "Unknown") == 0)
            return fail("ahci name");
        if (std::strcmp(hsc::io_mode_name(hsc::IoMode::UsbDirect), "Unknown") == 0)
            return fail("usb name");
        if (std::strcmp(hsc::io_mode_name(hsc::IoMode::RebuildAssist), "Unknown") == 0)
            return fail("fpdma name");
        if (hsc::kIoModeCount != 8 && hsc::kIoModeCount != 9) return fail("io mode count");
    }

    std::printf("engine tests passed\n");
    return 0;
}
