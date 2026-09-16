#include "clone_engine.hpp"
#include "progress_log.hpp"
#include "safety.hpp"
#include "sector_map.hpp"

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

    std::printf("engine tests passed\n");
    return 0;
}
