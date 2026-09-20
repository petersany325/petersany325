#include "file_recovery.hpp"

#include <algorithm>
#include <cctype>
#include <cstdio>
#include <cstring>
#include <filesystem>
#include <fstream>
#include <sstream>

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
uint64_t u64(const uint8_t* p) { return uint64_t(u32(p)) | (uint64_t(u32(p + 4)) << 32); }

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

// --- NTFS (basic $MFT FILE records) ---

int64_t read_runlist(const uint8_t* p, size_t n, std::vector<std::pair<int64_t, uint64_t>>& runs) {
    size_t i = 0;
    int64_t lcn = 0;
    uint64_t total = 0;
    while (i < n && p[i] != 0) {
        uint8_t h = p[i++];
        int len_len = h & 0x0f;
        int off_len = h >> 4;
        if (i + len_len + off_len > n) break;
        uint64_t run_len = 0;
        for (int b = 0; b < len_len; ++b) run_len |= uint64_t(p[i++]) << (8 * b);
        int64_t off = 0;
        for (int b = 0; b < off_len; ++b) off |= int64_t(p[i++]) << (8 * b);
        if (off_len && (p[i - 1] & 0x80)) {
            for (int b = off_len; b < 8; ++b) off |= int64_t(0xff) << (8 * b);
        }
        lcn += off;
        runs.push_back({lcn, run_len});
        total += run_len;
    }
    return static_cast<int64_t>(total);
}

void recover_ntfs(DiskSession& src, uint64_t part_lba, const std::string& dest, FileRecoveryStats& st,
                  std::atomic<bool>* stop, const RecoveryLogFn& log) {
    std::vector<uint8_t> boot;
    if (!read_lba(src, part_lba, 1, boot, IoMode::Generic) || !looks_like_ntfs_boot(boot.data())) return;
    uint16_t bps = u16(boot.data() + 11);
    uint8_t spc = boot[13];
    uint64_t mft_clus = 0;
    std::memcpy(&mft_clus, boot.data() + 48, 8);
    int rec_shift = static_cast<int8_t>(boot[64]);
    uint32_t rec_size = rec_shift < 0 ? (1u << -rec_shift) : uint32_t(rec_shift) * bps * spc;
    if (rec_size == 0 || rec_size > 4096) rec_size = 1024;
    uint32_t ss = src.sector_size() ? src.sector_size() : 512;
    uint64_t mft_lba = part_lba + (mft_clus * spc * bps) / ss;
    emit(log, "NTFS at LBA " + std::to_string(part_lba) + " MFT LBA " + std::to_string(mft_lba));
    // Read first 64 $MFT records (enough for small volumes / tests; keep scanning 4096)
    const int max_rec = 4096;
    std::vector<uint8_t> recs;
    uint32_t rec_secs = (rec_size + ss - 1) / ss;
    for (int i = 0; i < max_rec; ++i) {
        if (stopped(stop)) break;
        std::vector<uint8_t> rec;
        if (!read_lba(src, mft_lba + uint64_t(i) * rec_secs, rec_secs, rec, IoMode::Generic)) continue;
        if (rec.size() < rec_size) continue;
        if (std::memcmp(rec.data(), "FILE", 4) != 0) continue;
        uint16_t attr_off = u16(rec.data() + 20);
        uint32_t flags = u16(rec.data() + 22);
        if (!(flags & 0x01)) continue;  // not in use
        std::string fname;
        std::vector<uint8_t> resident;
        std::vector<std::pair<int64_t, uint64_t>> runs;
        uint64_t data_size = 0;
        size_t ao = attr_off;
        while (ao + 16 < rec_size) {
            uint32_t atype = u32(rec.data() + ao);
            if (atype == 0xFFFFFFFF) break;
            uint32_t alen = u32(rec.data() + ao + 4);
            if (alen < 16 || ao + alen > rec_size) break;
            uint8_t nonres = rec[ao + 8];
            if (atype == 0x30 && !nonres) {  // $FILE_NAME
                uint32_t voff = u16(rec.data() + ao + 20);
                const uint8_t* fn = rec.data() + ao + voff;
                uint8_t nlen = fn[64];
                std::string n;
                for (int c = 0; c < nlen; ++c) n.push_back(static_cast<char>(fn[66 + c * 2]));
                if (!n.empty() && n[0] != '$') fname = n;
            }
            if (atype == 0x80) {  // $DATA unnamed
                if (!nonres) {
                    uint32_t vsz = u32(rec.data() + ao + 16);
                    uint16_t voff = u16(rec.data() + ao + 20);
                    resident.assign(rec.data() + ao + voff, rec.data() + ao + voff + vsz);
                    data_size = vsz;
                } else {
                    data_size = u64(rec.data() + ao + 48);
                    uint16_t run_off = u16(rec.data() + ao + 32);
                    read_runlist(rec.data() + ao + run_off, alen > run_off ? alen - run_off : 0, runs);
                }
            }
            ao += alen;
        }
        if (fname.empty()) continue;
        std::string outp = join_path(dest, sanitize(fname));
        std::vector<uint8_t> body = std::move(resident);
        if (body.empty() && !runs.empty()) {
            uint64_t left = data_size;
            uint32_t cl_secs = (uint32_t(spc) * bps) / ss;
            for (auto [lcn, len] : runs) {
                if (!left) break;
                for (uint64_t c = 0; c < len && left; ++c) {
                    std::vector<uint8_t> chunk;
                    uint64_t lba = part_lba + uint64_t(lcn + int64_t(c)) * cl_secs;
                    uint32_t nsec = cl_secs ? cl_secs : 1;
                    if (!read_lba(src, lba, nsec, chunk, IoMode::Generic)) {
                        st.files_failed++;
                        left = 0;
                        break;
                    }
                    uint64_t take = std::min(left, uint64_t(chunk.size()));
                    body.insert(body.end(), chunk.begin(), chunk.begin() + static_cast<size_t>(take));
                    left -= take;
                }
            }
        }
        if (body.empty() && data_size == 0) continue;
        if (write_bytes(outp, body.data(), body.size())) {
            st.files_written++;
            st.bytes_written += body.size();
            emit(log, "NTFS file " + fname + " (" + std::to_string(body.size()) + " bytes)");
        } else {
            st.files_failed++;
        }
    }
}

struct Sig {
    const char* ext;
    const uint8_t* mag;
    size_t mag_n;
    const uint8_t* end;
    size_t end_n;
    size_t max_n;
};

const uint8_t kJpg[] = {0xFF, 0xD8, 0xFF};
const uint8_t kPng[] = {0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A};
const uint8_t kPngEnd[] = {0x49, 0x45, 0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82};
const uint8_t kPdf[] = {'%', 'P', 'D', 'F'};
const uint8_t kPdfEnd[] = {'%', '%', 'E', 'O', 'F'};
const uint8_t kZip[] = {'P', 'K', 0x03, 0x04};

void carve(DiskSession& src, const std::string& dest, FileRecoveryStats& st, std::atomic<bool>* stop,
           const RecoveryLogFn& log) {
    uint32_t ss = src.sector_size() ? src.sector_size() : 512;
    uint64_t total = src.size_bytes() / ss;
    if (total == 0) total = 1;
    const uint32_t chunk_secs = 256;
    int idx = 0;
    auto save = [&](const char* ext, const uint8_t* p, size_t n) {
        char name[64];
        std::snprintf(name, sizeof(name), "carved_%04d.%s", idx++, ext);
        if (write_bytes(join_path(join_path(dest, "carved"), name), p, n)) {
            st.carved++;
            st.files_written++;
            st.bytes_written += n;
            emit(log, std::string("Carved ") + name);
        }
    };
    for (uint64_t lba = 0; lba < total && !stopped(stop);) {
        uint32_t n = chunk_secs;
        if (lba + n > total) n = static_cast<uint32_t>(total - lba);
        std::vector<uint8_t> buf;
        if (!read_lba(src, lba, n, buf, IoMode::Generic)) {
            lba += n;
            continue;
        }
        auto find_at = [&](const uint8_t* mag, size_t mn, size_t from) -> size_t {
            if (buf.size() < mn) return size_t(-1);
            for (size_t i = from; i + mn <= buf.size(); ++i) {
                if (std::memcmp(buf.data() + i, mag, mn) == 0) return i;
            }
            return size_t(-1);
        };
        size_t pos = 0;
        while (true) {
            size_t j = find_at(kJpg, sizeof(kJpg), pos);
            if (j == size_t(-1)) break;
            size_t e = j + 3;
            bool found = false;
            for (; e + 2 <= buf.size(); ++e) {
                if (buf[e] == 0xFF && buf[e + 1] == 0xD9) {
                    save("jpg", buf.data() + j, e + 2 - j);
                    pos = e + 2;
                    found = true;
                    break;
                }
            }
            if (!found) {
                save("jpg", buf.data() + j, std::min(size_t(512 * 64), buf.size() - j));
                pos = j + 3;
            }
        }
        pos = 0;
        while (true) {
            size_t j = find_at(kPng, sizeof(kPng), pos);
            if (j == size_t(-1)) break;
            size_t e = find_at(kPngEnd, sizeof(kPngEnd), j);
            size_t nlen = (e == size_t(-1)) ? std::min(size_t(512 * 64), buf.size() - j) : (e + sizeof(kPngEnd) - j);
            save("png", buf.data() + j, nlen);
            pos = j + 4;
        }
        pos = 0;
        while (true) {
            size_t j = find_at(kPdf, sizeof(kPdf), pos);
            if (j == size_t(-1)) break;
            size_t e = find_at(kPdfEnd, sizeof(kPdfEnd), j);
            size_t nlen = (e == size_t(-1)) ? std::min(size_t(512 * 128), buf.size() - j) : (e + sizeof(kPdfEnd) - j);
            save("pdf", buf.data() + j, nlen);
            pos = j + 4;
        }
        pos = 0;
        while (true) {
            size_t j = find_at(kZip, sizeof(kZip), pos);
            if (j == size_t(-1)) break;
            save("zip", buf.data() + j, std::min(size_t(512 * 256), buf.size() - j));
            pos = j + 4;
        }
        lba += n;
    }
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

}  // namespace hsc
