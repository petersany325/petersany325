#include "file_recovery.hpp"
#include "carve_sigs.hpp"
#include "ntfs_mft.hpp"

#include <algorithm>
#include <cctype>
#include <cstdio>
#include <cstring>
#include <filesystem>
#include <fstream>
#include <sstream>
#include <unordered_set>

namespace hsc {
namespace {

namespace fs = std::filesystem;

bool stopped(std::atomic<bool>* s) { return s && s->load(); }

void emit(const RecoveryLogFn& log, const std::string& m) {
    if (log) log(m);
}

std::string join_path(const std::string& a, const std::string& b) {
    if (a.empty()) return b;
    char sep =
#ifdef _WIN32
        '\\';
#else
        '/';
#endif
    if (a.back() == '/' || a.back() == '\\') return a + b;
    return a + sep + b;
}

std::string sanitize(std::string name) {
    for (char& c : name) {
        if (c < 32 || std::strchr("<>:\"/\\|?*", c)) c = '_';
    }
    while (!name.empty() && (name.back() == ' ' || name.back() == '.')) name.pop_back();
    if (name.empty()) name = "unnamed";
    return name;
}

bool write_bytes(const std::string& path, const void* data, size_t n) {
    fs::create_directories(fs::path(path).parent_path());
    std::ofstream out(path, std::ios::binary);
    if (!out) return false;
    out.write(static_cast<const char*>(data), static_cast<std::streamsize>(n));
    return static_cast<bool>(out);
}

bool read_lba(DiskSession& src, uint64_t lba, uint32_t count, std::vector<uint8_t>& buf, IoMode mode) {
    uint32_t ss = src.sector_size() ? src.sector_size() : 512;
    buf.assign(static_cast<size_t>(count) * ss, 0);
    IoResult r = src.read_sectors(lba, count, buf.data(), mode, 4000);
    return r.ok;
}

uint16_t u16(const uint8_t* p) { return static_cast<uint16_t>(p[0] | (p[1] << 8)); }
uint32_t u32(const uint8_t* p) { return p[0] | (uint32_t(p[1]) << 8) | (uint32_t(p[2]) << 16) | (uint32_t(p[3]) << 24); }

struct FatVol {
    uint32_t bytes_per_sec = 512;
    uint32_t sec_per_clus = 1;
    uint32_t reserved = 1;
    uint32_t fats = 2;
    uint32_t root_entries = 0;
    uint32_t fat_secs = 0;
    uint32_t total_secs = 0;
    uint32_t hidden = 0;
    uint32_t root_clus = 2;
    int fat_bits = 16;
    uint64_t part_lba = 0;
    uint32_t fat_lba = 0;
    uint32_t root_lba = 0;
    uint32_t data_lba = 0;
    uint32_t root_secs = 0;
};

bool parse_fat_boot(const uint8_t* b, FatVol& v) {
    if (!looks_like_fat_boot(b)) return false;
    v.bytes_per_sec = u16(b + 11);
    v.sec_per_clus = b[13];
    v.reserved = u16(b + 14);
    v.fats = b[16];
    v.root_entries = u16(b + 17);
    uint16_t tot16 = u16(b + 19);
    v.fat_secs = u16(b + 22);
    v.hidden = u32(b + 28);
    v.total_secs = tot16 ? tot16 : u32(b + 32);
    if (v.fat_secs == 0) v.fat_secs = u32(b + 36);  // FAT32
    if (v.bytes_per_sec != 512 && v.bytes_per_sec != 4096) return false;
    if (v.sec_per_clus == 0 || v.fats == 0) return false;
    v.fat_lba = static_cast<uint32_t>(v.part_lba + v.reserved);
    v.root_secs = ((v.root_entries * 32) + (v.bytes_per_sec - 1)) / v.bytes_per_sec;
    v.root_lba = v.fat_lba + v.fat_secs * v.fats;
    v.data_lba = v.root_lba + v.root_secs;
    uint32_t data_secs = v.total_secs > (v.data_lba - static_cast<uint32_t>(v.part_lba))
                             ? v.total_secs - (v.data_lba - static_cast<uint32_t>(v.part_lba))
                             : 0;
    uint32_t clusters = v.sec_per_clus ? data_secs / v.sec_per_clus : 0;
    if (v.root_entries == 0) {
        v.fat_bits = 32;
        v.root_clus = u32(b + 44);
        v.data_lba = v.fat_lba + v.fat_secs * v.fats;
        v.root_lba = v.data_lba;
    } else if (clusters < 4085)
        v.fat_bits = 12;
    else
        v.fat_bits = 16;
    return true;
}

uint32_t fat_entry(const std::vector<uint8_t>& fat, int bits, uint32_t cl) {
    if (bits == 32) {
        size_t o = static_cast<size_t>(cl) * 4;
        if (o + 4 > fat.size()) return 0x0fffffff;
        return u32(fat.data() + o) & 0x0fffffffu;
    }
    if (bits == 16) {
        size_t o = static_cast<size_t>(cl) * 2;
        if (o + 2 > fat.size()) return 0xffff;
        return u16(fat.data() + o);
    }
    size_t o = static_cast<size_t>(cl * 3 / 2);
    if (o + 2 > fat.size()) return 0xfff;
    uint16_t w = u16(fat.data() + o);
    return (cl & 1) ? (w >> 4) : (w & 0x0fff);
}

bool fat_eof(int bits, uint32_t e) {
    if (bits == 32) return e >= 0x0ffffff8;
    if (bits == 16) return e >= 0xfff8;
    return e >= 0x0ff8;
}

uint64_t cluster_lba(const FatVol& v, uint32_t cl) {
    if (cl < 2) return v.data_lba;
    return uint64_t(v.data_lba) + uint64_t(cl - 2) * v.sec_per_clus;
}

struct DirEnt {
    std::string name;
    uint32_t cluster = 0;
    uint32_t size = 0;
    bool dir = false;
};

void parse_dir_block(const uint8_t* p, size_t n, std::vector<DirEnt>& out) {
    std::string lfn;
    for (size_t i = 0; i + 32 <= n; i += 32) {
        const uint8_t* e = p + i;
        if (e[0] == 0x00) break;
        if (e[0] == 0xE5) {
            lfn.clear();
            continue;
        }
        if (e[11] == 0x0F) {
            // LFN chunk
            char chunk[14]{};
            int k = 0;
            auto put = [&](int off) {
                char c = static_cast<char>(e[off]);
                if (c) chunk[k++] = c;
            };
            put(1);
            put(3);
            put(5);
            put(7);
            put(9);
            put(14);
            put(16);
            put(18);
            put(20);
            put(22);
            put(24);
            put(28);
            put(30);
            lfn = std::string(chunk, chunk + k) + lfn;
            continue;
        }
        if (e[11] & 0x08) {
            lfn.clear();
            continue;
        }  // volume
        char name[12]{};
        std::memcpy(name, e, 11);
        std::string shortn;
        for (int j = 0; j < 8 && name[j] != ' '; ++j) shortn.push_back(name[j]);
        if (name[8] != ' ') {
            shortn.push_back('.');
            for (int j = 8; j < 11 && name[j] != ' '; ++j) shortn.push_back(name[j]);
        }
        DirEnt d;
        d.name = lfn.empty() ? shortn : lfn;
        d.cluster = uint32_t(u16(e + 26)) | (uint32_t(u16(e + 20)) << 16);
        d.size = u32(e + 28);
        d.dir = (e[11] & 0x10) != 0;
        lfn.clear();
        if (d.name == "." || d.name == "..") continue;
        out.push_back(d);
    }
}

void recover_fat_tree(DiskSession& src, const FatVol& v, const std::vector<uint8_t>& fat, uint32_t start_cl,
                      bool is_root16, const std::string& dest, const std::string& prefix, FileRecoveryStats& st,
                      std::atomic<bool>* stop, const RecoveryLogFn& log, int depth) {
    if (depth > 24 || stopped(stop)) return;
    std::vector<uint8_t> dirbuf;
    uint32_t ss = v.bytes_per_sec;
    if (is_root16) {
        if (!read_lba(src, v.root_lba, v.root_secs ? v.root_secs : 32, dirbuf, IoMode::Generic)) return;
    } else {
        uint32_t cl = start_cl;
        int guard = 0;
        while (cl >= 2 && !fat_eof(v.fat_bits, cl) && guard++ < 4096) {
            std::vector<uint8_t> chunk;
            if (!read_lba(src, cluster_lba(v, cl), v.sec_per_clus, chunk, IoMode::Generic)) break;
            dirbuf.insert(dirbuf.end(), chunk.begin(), chunk.end());
            cl = fat_entry(fat, v.fat_bits, cl);
        }
        (void)ss;
    }
    std::vector<DirEnt> ents;
    parse_dir_block(dirbuf.data(), dirbuf.size(), ents);
    for (const auto& e : ents) {
        if (stopped(stop)) return;
        std::string rel = prefix.empty() ? sanitize(e.name) : prefix + "/" + sanitize(e.name);
        if (e.dir) {
            fs::create_directories(join_path(dest, rel));
            recover_fat_tree(src, v, fat, e.cluster, false, dest, rel, st, stop, log, depth + 1);
            continue;
        }
        std::vector<uint8_t> file;
        uint32_t cl = e.cluster;
        uint32_t left = e.size;
        int guard = 0;
        while (left && cl >= 2 && !fat_eof(v.fat_bits, cl) && guard++ < 1'000'000) {
            std::vector<uint8_t> chunk;
            if (!read_lba(src, cluster_lba(v, cl), v.sec_per_clus, chunk, IoMode::Generic)) {
                st.files_failed++;
                break;
            }
            uint32_t take = std::min(left, static_cast<uint32_t>(chunk.size()));
            file.insert(file.end(), chunk.begin(), chunk.begin() + take);
            left -= take;
            cl = fat_entry(fat, v.fat_bits, cl);
        }
        if (write_bytes(join_path(dest, rel), file.data(), file.size())) {
            st.files_written++;
            st.bytes_written += file.size();
            emit(log, "FAT file " + rel + " (" + std::to_string(file.size()) + " bytes)");
        } else {
            st.files_failed++;
        }
    }
}

void recover_fat(DiskSession& src, uint64_t part_lba, const std::string& dest, FileRecoveryStats& st,
                 std::atomic<bool>* stop, const RecoveryLogFn& log) {
    std::vector<uint8_t> boot;
    if (!read_lba(src, part_lba, 1, boot, IoMode::Generic)) return;
    FatVol v;
    v.part_lba = part_lba;
    if (!parse_fat_boot(boot.data(), v)) return;
    emit(log, std::string("FAT") + std::to_string(v.fat_bits) + " at LBA " + std::to_string(part_lba));
    std::vector<uint8_t> fat;
    uint32_t fat_secs = std::min(v.fat_secs, 2048u);
    if (!read_lba(src, v.fat_lba, fat_secs, fat, IoMode::Generic)) return;
    bool root16 = v.fat_bits != 32;
    recover_fat_tree(src, v, fat, v.root_clus, root16, dest, "", st, stop, log, 0);
}

void recover_ntfs(DiskSession& src, uint64_t part_lba, const std::string& dest, FileRecoveryStats& st,
                  std::atomic<bool>* stop, const RecoveryLogFn& log) {
    auto vol = scan_ntfs_mft(src, part_lba, stop, log);
    if (!vol.ok) {
        emit(log, vol.message.empty() ? "NTFS MFT scan failed" : vol.message);
        return;
    }
    auto rec = recover_mft_records(src, vol, {}, dest, stop, log);
    st.files_written += rec.files_written;
    st.files_failed += rec.files_failed;
    st.bytes_written += rec.bytes_written;
}

void carve(DiskSession& src, const std::string& dest, FileRecoveryStats& st, std::atomic<bool>* stop,
           const RecoveryLogFn& log) {
    int written = 0;
    uint64_t bytes = 0;
    st.carved += carve_scan(src, dest, stop, log, written, bytes);
    st.files_written += written;
    st.bytes_written += bytes;
}

}  // namespace

bool looks_like_fat_boot(const uint8_t* s512) {
    if (s512[510] != 0x55 || s512[511] != 0xAA) return false;
    if (s512[0] != 0xEB && s512[0] != 0xE9) return false;
    uint16_t bps = u16(s512 + 11);
    if (bps != 512 && bps != 1024 && bps != 2048 && bps != 4096) return false;
    return s512[13] != 0;
}

bool looks_like_ntfs_boot(const uint8_t* s512) {
    return std::memcmp(s512 + 3, "NTFS    ", 8) == 0;
}

int parse_mbr_partitions(const uint8_t* s512, uint64_t out_lba[4], uint64_t out_sectors[4]) {
    int n = 0;
    if (s512[510] != 0x55 || s512[511] != 0xAA) return 0;
    for (int i = 0; i < 4; ++i) {
        const uint8_t* e = s512 + 446 + i * 16;
        uint32_t lba = u32(e + 8);
        uint32_t secs = u32(e + 12);
        if (lba && secs) {
            out_lba[n] = lba;
            out_sectors[n] = secs;
            ++n;
        }
    }
    return n;
}

FileRecoveryStats recover_files(DiskSession& source, const std::string& dest_dir, std::atomic<bool>* stop,
                                RecoveryLogFn log) {
    FileRecoveryStats st;
    if (dest_dir.empty()) {
        st.message = "No destination folder";
        return st;
    }
    std::error_code ec;
    fs::create_directories(dest_dir, ec);
    emit(log, "File recovery into " + dest_dir);
    std::vector<uint8_t> mbr;
    if (!read_lba(source, 0, 1, mbr, IoMode::Generic)) {
        st.message = "Cannot read sector 0";
        return st;
    }

    // Superfloppy: filesystem starts at LBA 0.
    if (looks_like_fat_boot(mbr.data())) {
        recover_fat(source, 0, dest_dir, st, stop, log);
        st.partitions_found = std::max(st.partitions_found, 1);
    } else if (looks_like_ntfs_boot(mbr.data())) {
        recover_ntfs(source, 0, dest_dir, st, stop, log);
        st.partitions_found = std::max(st.partitions_found, 1);
    } else {
        uint64_t parts[4]{}, psz[4]{};
        int np = parse_mbr_partitions(mbr.data(), parts, psz);
        st.partitions_found = np;
        std::vector<uint8_t> lba1;
        if (read_lba(source, 1, 1, lba1, IoMode::Generic) && std::memcmp(lba1.data(), "EFI PART", 8) == 0) {
            emit(log, "GPT detected");
            std::vector<uint8_t> ents;
            if (read_lba(source, 2, 32, ents, IoMode::Generic)) {
                for (size_t i = 0; i + 128 <= ents.size(); i += 128) {
                    bool empty = true;
                    for (int b = 0; b < 16; ++b)
                        if (ents[i + b]) empty = false;
                    if (empty) continue;
                    uint64_t first = 0, last = 0;
                    std::memcpy(&first, ents.data() + i + 32, 8);
                    std::memcpy(&last, ents.data() + i + 40, 8);
                    if (!(first && last >= first)) continue;
                    emit(log, "GPT partition LBA " + std::to_string(first));
                    std::vector<uint8_t> boot;
                    if (!read_lba(source, first, 1, boot, IoMode::Generic)) continue;
                    if (looks_like_fat_boot(boot.data()))
                        recover_fat(source, first, dest_dir, st, stop, log);
                    else if (looks_like_ntfs_boot(boot.data()))
                        recover_ntfs(source, first, dest_dir, st, stop, log);
                    st.partitions_found++;
                }
            }
        }
        for (int i = 0; i < np; ++i) {
            std::vector<uint8_t> boot;
            if (!read_lba(source, parts[i], 1, boot, IoMode::Generic)) continue;
            if (looks_like_fat_boot(boot.data()))
                recover_fat(source, parts[i], dest_dir, st, stop, log);
            else if (looks_like_ntfs_boot(boot.data()))
                recover_ntfs(source, parts[i], dest_dir, st, stop, log);
        }
    }
    emit(log, "Signature carving pass");
    carve(source, dest_dir, st, stop, log);
    st.ok = st.files_written > 0 || st.carved > 0;
    st.message = "Wrote " + std::to_string(st.files_written) + " file(s), " + std::to_string(st.files_failed) +
                 " failed, carved " + std::to_string(st.carved);
    emit(log, st.message);
    return st;
}

FileRecoveryStats recover_mft_records(DiskSession& source, const NtfsVolumeInfo& vol,
                                      const std::vector<uint64_t>& recnos, const std::string& dest_dir,
                                      std::atomic<bool>* stop, RecoveryLogFn log) {
    FileRecoveryStats st;
    if (dest_dir.empty()) {
        st.message = "No destination folder";
        return st;
    }
    std::error_code ec;
    fs::create_directories(dest_dir, ec);
    std::unordered_set<uint64_t> want(recnos.begin(), recnos.end());
    bool filter = !recnos.empty();
    emit(log, "MFT recover into " + dest_dir + (filter ? " (selected records)" : " (all named records)"));
    for (const auto& rec : vol.records) {
        if (stop && stop->load()) break;
        if (!rec.parsed_ok || rec.skipped_bad) continue;
        if (rec.name.empty() || rec.recno < 5) continue;
        if (rec.name[0] == '$' && rec.recno < 24) continue;
        if (filter && !want.count(rec.recno)) continue;
        std::string rel = rec.path.empty() ? sanitize(rec.name) : rec.path;
        if (rec.deleted) rel = std::string("deleted/") + rel;
        std::string outp = join_path(dest_dir, rel);
        bool failed = false;
        uint64_t n = copy_mft_record_data(source, vol, rec, outp, failed, stop);
        if (rec.is_dir) {
            emit(log, "MFT dir " + rel);
            continue;
        }
        if (failed && n == 0) {
            st.files_failed++;
            emit(log, "MFT failed " + rel);
        } else {
            st.files_written++;
            st.bytes_written += n;
            emit(log, std::string("MFT ") + (rec.deleted ? "deleted " : "") +
                          (rec.resident ? "resident " : "runlist ") + rel + " (" + std::to_string(n) + " bytes)");
        }
    }
    st.ok = st.files_written > 0;
    st.message = "MFT wrote " + std::to_string(st.files_written) + " file(s), " + std::to_string(st.files_failed) +
                 " failed";
    emit(log, st.message);
    return st;
}

FileRecoveryStats grep_scan_disk(DiskSession& source, const std::string& dest_dir, const CarveFilter& filter,
                                 std::atomic<bool>* stop, RecoveryLogFn log, CarveProgress* progress) {
    FileRecoveryStats st;
    if (dest_dir.empty()) {
        st.message = "No destination folder";
        return st;
    }
    std::error_code ec;
    fs::create_directories(dest_dir, ec);
    emit(log, "Grep scan of " + source.path() + " into " + dest_dir);
    int written = 0;
    uint64_t bytes = 0;
    st.carved = carve_scan(source, dest_dir, stop, log, written, bytes, filter, progress);
    st.files_written = written;
    st.bytes_written = bytes;
    st.ok = st.carved > 0;
    st.message = "Grep scan wrote " + std::to_string(st.carved) + " carved file(s), " +
                 std::to_string(st.bytes_written) + " bytes";
    emit(log, st.message);
    return st;
}

}  // namespace hsc
