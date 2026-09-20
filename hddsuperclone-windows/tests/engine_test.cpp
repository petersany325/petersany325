#include "clone_engine.hpp"
#include "file_recovery.hpp"
#include "progress_log.hpp"
#include "safety.hpp"
#include "script_engine.hpp"
#include "sector_map.hpp"
#include "usb_relay.hpp"
#include "virtual_disk.hpp"

#include <atomic>
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
        if (std::strstr(hsc::job_mode_name(hsc::JobMode::DiskToDisk), "Disk-to-disk") == nullptr)
            return fail("job disk");
        if (std::strstr(hsc::job_mode_name(hsc::JobMode::ImageOntoDrive), "Image onto Image HDD") == nullptr)
            return fail("job image");
        if (std::strstr(hsc::job_mode_name(hsc::JobMode::FileRecovery), "File recovery") == nullptr)
            return fail("job files");
        if (std::strstr(hsc::job_mode_name(hsc::JobMode::RestoreImageToDisk), "Restore image") == nullptr)
            return fail("job restore");
    }

    // --- file recovery: FAT16 file + JPEG carving ---
    {
        auto fatp = (tmp / "fat16.img").string();
        std::vector<uint8_t> img(64 * 512, 0);
        img[0] = 0xEB;
        img[1] = 0x3C;
        img[2] = 0x90;
        std::memcpy(img.data() + 3, "MSDOS5.0", 8);
        img[11] = 0x00;
        img[12] = 0x02;  // 512
        img[13] = 1;     // spc
        img[14] = 1;
        img[15] = 0;  // reserved
        img[16] = 2;  // fats
        img[17] = 16;
        img[18] = 0;  // 16 root entries
        img[19] = 64;
        img[20] = 0;  // total 64 sectors
        img[21] = 0xF8;
        img[22] = 1;
        img[23] = 0;  // fat size 1
        img[24] = 0x20;
        img[25] = 0;
        img[26] = 2;
        img[27] = 0;
        img[510] = 0x55;
        img[511] = 0xAA;
        // FAT1 at LBA 1
        img[512] = 0xF8;
        img[513] = 0xFF;
        img[514] = 0xFF;
        img[515] = 0xFF;
        img[516] = 0xFF;
        img[517] = 0xFF;  // cluster 2 EOF
        // FAT2 at LBA 2 (copy)
        std::memcpy(img.data() + 1024, img.data() + 512, 512);
        // Root at LBA 3: HELLO.TXT cluster 2 size 5
        uint8_t* ent = img.data() + 3 * 512;
        std::memcpy(ent, "HELLO   TXT", 11);
        ent[26] = 2;
        ent[27] = 0;
        ent[28] = 5;
        // cluster 2 at data_lba = 3 + 1 = 4
        std::memcpy(img.data() + 4 * 512, "hello", 5);
        // JPEG at LBA 20
        size_t jp = 20 * 512;
        img[jp] = 0xFF;
        img[jp + 1] = 0xD8;
        img[jp + 2] = 0xFF;
        img[jp + 3] = 0xE0;
        img[jp + 4] = 0xFF;
        img[jp + 5] = 0xD9;
        {
            std::ofstream o(fatp, std::ios::binary);
            o.write(reinterpret_cast<char*>(img.data()), static_cast<std::streamsize>(img.size()));
        }
        if (!hsc::looks_like_fat_boot(img.data())) return fail("fat boot detect");
        auto sess = hsc::open_disk(fatp, false, true);
        if (!sess) return fail("open fat img");
        auto outdir = (tmp / "recovered").string();
        std::atomic<bool> stop{false};
        auto st = hsc::recover_files(*sess, outdir, &stop, nullptr);
        if (st.files_written < 1) return fail("file recovery wrote nothing");
        bool saw_hello = fs::exists(fs::path(outdir) / "HELLO.TXT") || fs::exists(fs::path(outdir) / "hello.txt");
        bool saw_jpg = false;
        for (auto& p : fs::recursive_directory_iterator(outdir)) {
            if (p.path().extension() == ".jpg") saw_jpg = true;
        }
        if (!saw_hello) return fail("fat HELLO.TXT missing");
        if (!saw_jpg) return fail("carved jpeg missing");
    }

    // --- folder dest safety ---
    {
        hsc::SafetyRequest r;
        r.source_path = "s";
        r.dest_path = "/tmp/out";
        r.dest_is_folder = true;
        auto a = hsc::check_clone_safety(r);
        if (!a.ok) return fail("folder dest safety");
    }

    std::printf("engine tests passed\n");
    return 0;
}
