#include "ntfs_mft.hpp"

#include "file_recovery.hpp"

#include <algorithm>
#include <cstring>
#include <filesystem>
#include <fstream>
#include <functional>
#include <unordered_map>
#include <unordered_set>

namespace hsc {
namespace {

namespace fs = std::filesystem;

bool stopped(std::atomic<bool>* s) { return s && s->load(); }
void emit(const std::function<void(const std::string&)>& log, const std::string& m) {
    if (log) log(m);
}

uint16_t u16(const uint8_t* p) { return static_cast<uint16_t>(p[0] | (p[1] << 8)); }
uint32_t u32(const uint8_t* p) {
    return p[0] | (uint32_t(p[1]) << 8) | (uint32_t(p[2]) << 16) | (uint32_t(p[3]) << 24);
}
uint64_t u64(const uint8_t* p) { return uint64_t(u32(p)) | (uint64_t(u32(p + 4)) << 32); }

void p16(uint8_t* p, uint16_t v) {
    p[0] = uint8_t(v);
    p[1] = uint8_t(v >> 8);
}
void p32(uint8_t* p, uint32_t v) {
    p16(p, uint16_t(v));
    p16(p + 2, uint16_t(v >> 16));
}
void p64(uint8_t* p, uint64_t v) {
    p32(p, uint32_t(v));
    p32(p + 4, uint32_t(v >> 32));
}

bool read_secs(DiskSession& src, uint64_t lba, uint32_t count, std::vector<uint8_t>& buf) {
    uint32_t ss = src.sector_size() ? src.sector_size() : 512;
    buf.assign(static_cast<size_t>(count) * ss, 0);
    return src.read_sectors(lba, count, buf.data(), IoMode::Generic, 4000).ok;
}

std::string utf16le_ascii(const uint8_t* p, int nchars) {
    std::string s;
    s.reserve(static_cast<size_t>(nchars));
    for (int i = 0; i < nchars; ++i) {
        uint16_t c = u16(p + i * 2);
        if (c == 0) break;
        if (c < 0x80)
            s.push_back(static_cast<char>(c));
        else if (c < 0x800) {
            s.push_back(static_cast<char>(0xC0 | (c >> 6)));
            s.push_back(static_cast<char>(0x80 | (c & 0x3f)));
        } else {
            s.push_back(static_cast<char>(0xE0 | (c >> 12)));
            s.push_back(static_cast<char>(0x80 | ((c >> 6) & 0x3f)));
            s.push_back(static_cast<char>(0x80 | (c & 0x3f)));
        }
    }
    return s;
}

std::string sanitize_name(std::string name) {
    for (char& c : name) {
        if (static_cast<unsigned char>(c) < 32 || std::strchr("<>:\"/\\|?*", c)) c = '_';
    }
    while (!name.empty() && (name.back() == ' ' || name.back() == '.')) name.pop_back();
    if (name.empty()) name = "unnamed";
    return name;
}

bool apply_fixup(uint8_t* rec, size_t rec_size, uint32_t sector_size) {
    if (rec_size < 8 || sector_size < 2) return false;
    uint16_t usa_ofs = u16(rec + 4);
    uint16_t usa_count = u16(rec + 6);
    if (usa_ofs < 0x28 || usa_count < 2) return true;
    size_t usa_bytes = size_t(usa_count) * 2;
    if (size_t(usa_ofs) + usa_bytes > rec_size) return false;
    uint16_t usn = u16(rec + usa_ofs);
    uint32_t nsec = usa_count - 1;
    for (uint32_t i = 0; i < nsec; ++i) {
        size_t end = (i + 1) * sector_size;
        if (end < 2 || end > rec_size) return false;
        uint8_t* last = rec + end - 2;
        uint16_t saved = u16(rec + usa_ofs + 2 + i * 2);
        (void)usn;
        last[0] = uint8_t(saved);
        last[1] = uint8_t(saved >> 8);
    }
    return true;
}

void parse_runlist(const uint8_t* p, size_t n, std::vector<MftDataRun>& runs) {
    size_t i = 0;
    int64_t lcn = 0;
    while (i < n && p[i] != 0) {
        uint8_t h = p[i++];
        int len_len = h & 0x0f;
        int off_len = h >> 4;
        if (len_len == 0 || i + len_len + off_len > n) break;
        uint64_t run_len = 0;
        for (int b = 0; b < len_len; ++b) run_len |= uint64_t(p[i++]) << (8 * b);
        if (run_len == 0) break;
        MftDataRun r;
        r.clusters = run_len;
        if (off_len == 0) {
            r.sparse = true;
            r.lcn = 0;
        } else {
            int64_t off = 0;
            for (int b = 0; b < off_len; ++b) off |= int64_t(p[i++]) << (8 * b);
            if (p[i - 1] & 0x80) {
                for (int b = off_len; b < 8; ++b) off |= int64_t(0xff) << (8 * b);
            }
            lcn += off;
            r.lcn = lcn;
        }
        runs.push_back(r);
    }
}

struct NtfsBoot {
    uint32_t bps = 512;
    uint32_t spc = 8;
    uint32_t rec_size = 1024;
    uint64_t mft_lcn = 0;
    uint64_t mirr_lcn = 0;
    uint64_t total_sectors = 0;
    uint64_t part_lba = 0;
};

bool parse_boot(const uint8_t* b, size_t n, NtfsBoot& out) {
    if (n < 80 || std::memcmp(b + 3, "NTFS    ", 8) != 0) return false;
    out.bps = u16(b + 11);
    out.spc = b[13];
    if (!(out.bps == 512 || out.bps == 1024 || out.bps == 2048 || out.bps == 4096)) return false;
    if (out.spc == 0) return false;
    out.total_sectors = u64(b + 0x28);
    out.mft_lcn = u64(b + 0x30);
    out.mirr_lcn = u64(b + 0x38);
    int8_t rec = static_cast<int8_t>(b[0x40]);
    if (rec < 0)
        out.rec_size = 1u << (-rec);
    else
        out.rec_size = uint32_t(rec) * out.bps * out.spc;
    if (out.rec_size != 1024 && out.rec_size != 4096) {
        if (out.rec_size == 0 || out.rec_size > 4096) out.rec_size = 1024;
    }
    return true;
}

uint64_t cluster_lba(const NtfsBoot& b, int64_t lcn) {
    if (lcn < 0) return 0;
    return b.part_lba + uint64_t(lcn) * b.spc;
}

bool parse_file_record(const uint8_t* rec, size_t rec_size, uint64_t recno, MftRecordView& out) {
    out = {};
    out.recno = recno;
    if (rec_size < 0x30 || std::memcmp(rec, "FILE", 4) != 0) {
        out.skipped_bad = true;
        return false;
    }
    uint16_t flags = u16(rec + 0x16);
    out.in_use = (flags & 0x01) != 0;
    out.deleted = !out.in_use;
    out.is_dir = (flags & 0x02) != 0;
    out.sequence = u16(rec + 0x10);
    uint16_t attr_off = u16(rec + 0x14);
    if (attr_off < 0x30 || attr_off >= rec_size) {
        out.skipped_bad = true;
        return false;
    }
    std::string posix, win32, dos;
    uint64_t parent_posix = 5, parent_win32 = 5;
    size_t ao = attr_off;
    int guard = 0;
    while (ao + 16 <= rec_size && guard++ < 64) {
        uint32_t atype = u32(rec + ao);
        if (atype == 0xFFFFFFFFu) break;
        uint32_t alen = u32(rec + ao + 4);
        if (alen < 16 || ao + alen > rec_size) break;
        uint8_t nonres = rec[ao + 8];
        uint8_t nlen = rec[ao + 9];
        if (atype == 0x10 && !nonres) {  // STANDARD_INFORMATION
            uint32_t vsz = u32(rec + ao + 16);
            uint16_t voff = u16(rec + ao + 20);
            if (size_t(voff) + 36 <= alen && vsz >= 36) out.si_attrs = u32(rec + ao + voff + 32);
        }
        if (atype == 0x30 && !nonres) {  // FILE_NAME
            uint16_t voff = u16(rec + ao + 20);
            const uint8_t* fn = rec + ao + voff;
            if (size_t(voff) + 66 <= alen) {
                uint64_t pref = u64(fn) & 0x0000FFFFFFFFFFFFull;
                uint8_t namelen = fn[64];
                uint8_t nspace = fn[65];
                if (size_t(voff) + 66 + size_t(namelen) * 2 <= alen) {
                    std::string nm = utf16le_ascii(fn + 66, namelen);
                    if (nspace == 1 || nspace == 3) {
                        win32 = nm;
                        parent_win32 = pref;
                    } else if (nspace == 0) {
                        posix = nm;
                        parent_posix = pref;
                    } else {
                        dos = nm;
                    }
                }
            }
        }
        if (atype == 0x80 && nlen == 0) {  // unnamed $DATA
            out.has_unnamed_data = true;
            if (!nonres) {
                uint32_t vsz = u32(rec + ao + 16);
                uint16_t voff = u16(rec + ao + 20);
                if (size_t(voff) + vsz <= alen) {
                    out.resident = true;
                    out.resident_data.assign(rec + ao + voff, rec + ao + voff + vsz);
                    out.data_size = vsz;
                    out.allocated_size = vsz;
                }
            } else if (alen >= 0x40) {
                out.resident = false;
                out.allocated_size = u64(rec + ao + 0x28);
                out.data_size = u64(rec + ao + 0x30);
                uint16_t run_off = u16(rec + ao + 0x20);
                if (run_off < alen) parse_runlist(rec + ao + run_off, alen - run_off, out.runs);
            }
        }
        ao += alen;
    }
    if (!win32.empty()) {
        out.name = win32;
        out.parent_recno = parent_win32;
    } else if (!posix.empty()) {
        out.name = posix;
        out.parent_recno = parent_posix;
    } else {
        out.name = dos;
        out.parent_recno = parent_win32;
    }
    out.parsed_ok = true;
    return true;
}

MftRecordView parse_or_bad(const uint8_t* rec, size_t rec_size, uint64_t recno) {
    MftRecordView v;
    parse_file_record(rec, rec_size, recno, v);
    if (!v.parsed_ok) {
        v.recno = recno;
        v.skipped_bad = true;
        v.name = "(bad record)";
    }
    return v;
}

}  // namespace

void rebuild_mft_paths(NtfsVolumeInfo& vol) {
    std::unordered_map<uint64_t, size_t> idx;
    for (size_t i = 0; i < vol.records.size(); ++i) idx[vol.records[i].recno] = i;
    for (auto& r : vol.records) {
        if (r.name.empty() || r.skipped_bad) {
            r.path = r.name;
            continue;
        }
        if (r.recno == 5) {
            r.path = ".";
            continue;
        }
        std::vector<std::string> parts;
        std::unordered_set<uint64_t> seen;
        uint64_t cur = r.recno;
        int guard = 0;
        while (cur != 5 && cur != 0 && guard++ < 64) {
            if (!seen.insert(cur).second) break;
            auto it = idx.find(cur);
            if (it == idx.end()) break;
            const auto& n = vol.records[it->second];
            if (!n.name.empty() && n.name[0] != '\0') parts.push_back(sanitize_name(n.name));
            if (n.parent_recno == cur) break;
            cur = n.parent_recno;
        }
        std::string path;
        for (auto it = parts.rbegin(); it != parts.rend(); ++it) {
            if (!path.empty()) path += "/";
            path += *it;
        }
        r.path = path.empty() ? sanitize_name(r.name) : path;
    }
}

NtfsVolumeInfo scan_ntfs_mft(DiskSession& src, uint64_t part_lba, std::atomic<bool>* stop,
                             const std::function<void(const std::string&)>& log, uint32_t max_records) {
    NtfsVolumeInfo vol;
    vol.part_lba = part_lba;
    std::vector<uint8_t> boot;
    if (!read_secs(src, part_lba, 1, boot) || boot.size() < 512) {
        vol.message = "Cannot read NTFS boot sector";
        return vol;
    }
    NtfsBoot b;
    b.part_lba = part_lba;
    if (!parse_boot(boot.data(), boot.size(), b)) {
        vol.message = "Not an NTFS boot sector";
        return vol;
    }
    vol.bytes_per_sector = b.bps;
    vol.sectors_per_cluster = b.spc;
    vol.cluster_bytes = b.bps * b.spc;
    vol.record_size = b.rec_size;
    vol.mft_lcn = b.mft_lcn;
    vol.mftmirr_lcn = b.mirr_lcn;
    vol.total_clusters = b.spc ? (b.total_sectors / b.spc) : 0;

    emit(log, "NTFS MFT rec=" + std::to_string(b.rec_size) + " cluster=" + std::to_string(vol.cluster_bytes) +
                  " $MFT LCN " + std::to_string(b.mft_lcn) + " $MFTMirr LCN " + std::to_string(b.mirr_lcn));

    uint32_t ss = src.sector_size() ? src.sector_size() : b.bps;
    uint32_t rec_secs = (b.rec_size + ss - 1) / ss;
    auto rec_lba_from_lcn = [&](uint64_t lcn, uint64_t recno) -> uint64_t {
        uint64_t byte_off = recno * uint64_t(b.rec_size);
        uint64_t clus = uint64_t(b.bps) * b.spc;
        if (clus == 0) clus = b.bps;
        uint64_t vcn = byte_off / clus;
        uint64_t inner = byte_off % clus;
        return cluster_lba(b, int64_t(lcn + vcn)) + inner / ss;
    };
    auto read_rec_lcn = [&](uint64_t lcn, uint64_t recno, std::vector<uint8_t>& rec) -> bool {
        uint64_t lba = rec_lba_from_lcn(lcn, recno);
        if (!read_secs(src, lba, rec_secs, rec)) return false;
        rec.resize(b.rec_size);
        apply_fixup(rec.data(), rec.size(), b.bps);
        return rec.size() >= 4 && std::memcmp(rec.data(), "FILE", 4) == 0;
    };

    std::vector<uint8_t> rec0;
    bool rec0_ok = read_rec_lcn(b.mft_lcn, 0, rec0);
    bool from_mirr = false;
    if (!rec0_ok && b.mirr_lcn) {
        if (read_rec_lcn(b.mirr_lcn, 0, rec0)) {
            rec0_ok = true;
            from_mirr = true;
            vol.used_mirr++;
            emit(log, "$MFT record 0 unreadable; using $MFTMirr");
        }
    }
    if (!rec0_ok) {
        vol.message = "Cannot read $MFT record 0 (tried $MFTMirr)";
        return vol;
    }

    MftRecordView mft0;
    parse_file_record(rec0.data(), rec0.size(), 0, mft0);
    mft0.from_mirr = from_mirr;
    std::vector<MftDataRun> mft_runs = mft0.runs;
    uint64_t mft_bytes = mft0.data_size ? mft0.data_size : mft0.allocated_size;
    uint32_t nrec = 0;
    if (mft_bytes) nrec = uint32_t(mft_bytes / b.rec_size);
    if (nrec == 0) nrec = 4096;
    if (max_records && nrec > max_records) nrec = max_records;
    if (nrec > 262144) nrec = 262144;

    // Sequential fallback if $MFT has no runlist: treat as one run at mft_lcn.
    if (mft_runs.empty()) {
        uint64_t clus = vol.cluster_bytes ? vol.cluster_bytes : 512;
        MftDataRun r;
        r.lcn = int64_t(b.mft_lcn);
        r.clusters = (uint64_t(nrec) * b.rec_size + clus - 1) / clus;
        mft_runs.push_back(r);
        emit(log, "$MFT DATA runlist missing; sequential from LCN " + std::to_string(b.mft_lcn));
    }

    vol.records.resize(nrec);

    auto load_rec = [&](uint64_t recno, bool try_mirr) -> MftRecordView {
        MftRecordView bad;
        bad.recno = recno;
        bad.skipped_bad = true;
        bad.name = "(unreadable)";
        uint64_t byte_off = recno * uint64_t(b.rec_size);
        uint64_t clus = vol.cluster_bytes ? vol.cluster_bytes : 512;
        uint64_t vcn = byte_off / clus;
        uint64_t inner = byte_off % clus;
        uint64_t seen = 0;
        int64_t lcn = -1;
        bool sparse = false;
        for (const auto& run : mft_runs) {
            if (vcn < seen + run.clusters) {
                sparse = run.sparse;
                lcn = run.sparse ? 0 : run.lcn + int64_t(vcn - seen);
                break;
            }
            seen += run.clusters;
        }
        std::vector<uint8_t> rec;
        if (!sparse && lcn >= 0) {
            uint64_t lba = cluster_lba(b, lcn) + inner / ss;
            if (read_secs(src, lba, rec_secs, rec)) {
                rec.resize(b.rec_size);
                apply_fixup(rec.data(), rec.size(), b.bps);
                if (std::memcmp(rec.data(), "FILE", 4) == 0) return parse_or_bad(rec.data(), rec.size(), recno);
            }
        }
        if (try_mirr && recno < 4 && b.mirr_lcn) {
            if (read_rec_lcn(b.mirr_lcn, recno, rec)) {
                auto v = parse_or_bad(rec.data(), rec.size(), recno);
                v.from_mirr = true;
                vol.used_mirr++;
                return v;
            }
        }
        vol.records_bad++;
        return bad;
    };

    for (uint32_t i = 0; i < nrec; ++i) {
        if (stopped(stop)) break;
        vol.records[i] = load_rec(i, true);
        vol.records_scanned++;
        if (vol.records[i].parsed_ok) vol.records_ok++;
    }
    rebuild_mft_paths(vol);
    vol.ok = vol.records_ok > 0;
    vol.message = "MFT " + std::to_string(vol.records_ok) + " ok / " + std::to_string(vol.records_scanned) +
                  " scanned, " + std::to_string(vol.records_bad) + " bad" +
                  (vol.used_mirr ? (", " + std::to_string(vol.used_mirr) + " from $MFTMirr") : "");
    emit(log, vol.message);
    return vol;
}

NtfsVolumeInfo scan_ntfs_on_disk(DiskSession& src, std::atomic<bool>* stop,
                                 const std::function<void(const std::string&)>& log) {
    std::vector<uint8_t> mbr;
    if (!read_secs(src, 0, 1, mbr) || mbr.size() < 512) {
        NtfsVolumeInfo v;
        v.message = "Cannot read sector 0";
        return v;
    }
    if (looks_like_ntfs_boot(mbr.data())) return scan_ntfs_mft(src, 0, stop, log);
    std::vector<uint64_t> parts;
    uint64_t lba[4]{}, sz[4]{};
    int np = parse_mbr_partitions(mbr.data(), lba, sz);
    for (int i = 0; i < np; ++i) parts.push_back(lba[i]);
    std::vector<uint8_t> lba1;
    if (read_secs(src, 1, 1, lba1) && lba1.size() >= 8 && std::memcmp(lba1.data(), "EFI PART", 8) == 0) {
        std::vector<uint8_t> ents;
        if (read_secs(src, 2, 32, ents)) {
            for (size_t i = 0; i + 128 <= ents.size(); i += 128) {
                bool empty = true;
                for (int b = 0; b < 16; ++b)
                    if (ents[i + b]) empty = false;
                if (empty) continue;
                uint64_t first = 0;
                std::memcpy(&first, ents.data() + i + 32, 8);
                if (first) parts.push_back(first);
            }
        }
    }
    for (uint64_t p : parts) {
        if (stopped(stop)) break;
        std::vector<uint8_t> boot;
        if (!read_secs(src, p, 1, boot)) continue;
        if (looks_like_ntfs_boot(boot.data())) return scan_ntfs_mft(src, p, stop, log);
    }
    NtfsVolumeInfo v;
    v.message = "No NTFS volume found (MFT not loaded)";
    return v;
}

uint64_t copy_mft_record_data(DiskSession& src, const NtfsVolumeInfo& vol, const MftRecordView& rec,
                              const std::string& out_path, bool& failed, std::atomic<bool>* stop) {
    failed = false;
    std::error_code ec;
    fs::create_directories(fs::path(out_path).parent_path(), ec);
    if (rec.is_dir) {
        fs::create_directories(out_path, ec);
        return 0;
    }
    NtfsBoot b;
    b.part_lba = vol.part_lba;
    b.bps = vol.bytes_per_sector;
    b.spc = vol.sectors_per_cluster;
    b.rec_size = vol.record_size;
    std::vector<uint8_t> body;
    if (rec.resident || !rec.resident_data.empty()) {
        body = rec.resident_data;
    } else if (!rec.runs.empty()) {
        uint64_t left = rec.data_size;
        uint32_t ss = src.sector_size() ? src.sector_size() : b.bps;
        uint32_t cl_secs = (b.spc * b.bps) / ss;
        if (cl_secs == 0) cl_secs = 1;
        uint32_t cl_bytes = cl_secs * ss;
        for (const auto& run : rec.runs) {
            if (!left || stopped(stop)) break;
            for (uint64_t c = 0; c < run.clusters && left; ++c) {
                std::vector<uint8_t> chunk;
                if (run.sparse) {
                    chunk.assign(cl_bytes, 0);
                } else if (!read_secs(src, cluster_lba(b, run.lcn + int64_t(c)), cl_secs, chunk)) {
                    failed = true;
                    chunk.assign(cl_bytes, 0);
                }
                uint64_t take = std::min(left, uint64_t(chunk.size()));
                body.insert(body.end(), chunk.begin(), chunk.begin() + static_cast<ptrdiff_t>(take));
                left -= take;
            }
        }
    }
    if (rec.data_size && body.size() > rec.data_size) body.resize(static_cast<size_t>(rec.data_size));
    std::ofstream out(out_path, std::ios::binary);
    if (!out) {
        failed = true;
        return 0;
    }
    if (!body.empty()) out.write(reinterpret_cast<const char*>(body.data()), static_cast<std::streamsize>(body.size()));
    if (!out) failed = true;
    return body.size();
}

namespace {

std::vector<uint8_t> attr_resident(uint32_t type, uint16_t id, const uint8_t* val, uint32_t vsz) {
    uint16_t voff = 0x18;
    uint32_t alen = (voff + vsz + 7u) & ~7u;
    std::vector<uint8_t> a(alen, 0);
    p32(a.data(), type);
    p32(a.data() + 4, alen);
    a[8] = 0;
    p16(a.data() + 0x0E, id);
    p32(a.data() + 16, vsz);
    p16(a.data() + 20, voff);
    if (vsz) std::memcpy(a.data() + voff, val, vsz);
    return a;
}

std::vector<uint8_t> attr_nonres_data(uint16_t id, uint64_t data_size, const std::vector<MftDataRun>& runs,
                                     uint32_t cluster_bytes) {
    std::vector<uint8_t> pairs;
    int64_t prev = 0;
    uint64_t vcn = 0;
    for (const auto& r : runs) {
        uint64_t len = r.clusters;
        int64_t off = r.sparse ? 0 : (r.lcn - prev);
        uint8_t len_len = 1;
        while (len_len < 8 && (len >> (8 * len_len))) ++len_len;
        uint8_t off_len = r.sparse ? 0 : 1;
        if (!r.sparse) {
            uint64_t u = uint64_t(off < 0 ? -off : off);
            while (off_len < 8 && (u >> (8 * off_len - 1))) ++off_len;
        }
        pairs.push_back(uint8_t((off_len << 4) | len_len));
        for (int i = 0; i < len_len; ++i) pairs.push_back(uint8_t(len >> (8 * i)));
        for (int i = 0; i < off_len; ++i) pairs.push_back(uint8_t(uint64_t(off) >> (8 * i)));
        if (!r.sparse) prev = r.lcn;
        vcn += r.clusters;
    }
    pairs.push_back(0);
    uint16_t run_off = 0x40;
    uint32_t alen = (run_off + uint32_t(pairs.size()) + 7u) & ~7u;
    std::vector<uint8_t> a(alen, 0);
    p32(a.data(), 0x80);
    p32(a.data() + 4, alen);
    a[8] = 1;
    p16(a.data() + 0x0E, id);
    p64(a.data() + 0x10, 0);
    p64(a.data() + 0x18, vcn ? vcn - 1 : 0);
    p16(a.data() + 0x20, run_off);
    uint64_t alloc = vcn * cluster_bytes;
    p64(a.data() + 0x28, alloc);
    p64(a.data() + 0x30, data_size);
    p64(a.data() + 0x38, data_size);
    std::memcpy(a.data() + run_off, pairs.data(), pairs.size());
    return a;
}

std::vector<uint8_t> make_filename(uint64_t parent, const std::string& name, uint64_t size, bool dir) {
    std::vector<uint8_t> fn(66 + name.size() * 2, 0);
    p64(fn.data(), parent | (uint64_t(1) << 48));
    p64(fn.data() + 0x28, (size + 7) & ~uint64_t(7));
    p64(fn.data() + 0x30, size);
    if (dir) p32(fn.data() + 0x38, 0x10000000);
    fn[64] = uint8_t(name.size());
    fn[65] = 1;  // Win32
    for (size_t i = 0; i < name.size(); ++i) p16(fn.data() + 66 + int(i) * 2, uint8_t(name[i]));
    return fn;
}

std::vector<uint8_t> make_si() {
    std::vector<uint8_t> si(0x48, 0);
    p32(si.data() + 32, 0x20);  // archive
    return si;
}

std::vector<uint8_t> stamp_file_record(uint32_t recno, uint16_t flags, const std::vector<std::vector<uint8_t>>& attrs,
                                       uint32_t rec_size, uint32_t bps) {
    std::vector<uint8_t> r(rec_size, 0);
    std::memcpy(r.data(), "FILE", 4);
    uint16_t usa_count = uint16_t(rec_size / bps + 1);
    p16(r.data() + 4, 0x30);
    p16(r.data() + 6, usa_count);
    p16(r.data() + 0x10, 1);
    p16(r.data() + 0x12, 1);
    p16(r.data() + 0x14, 0x38);
    p16(r.data() + 0x16, flags);
    p32(r.data() + 0x1C, rec_size);
    p16(r.data() + 0x28, uint16_t(attrs.size() + 1));
    p32(r.data() + 0x2C, recno);
    size_t o = 0x38;
    for (const auto& a : attrs) {
        if (o + a.size() + 8 > rec_size) break;
        std::memcpy(r.data() + o, a.data(), a.size());
        o += a.size();
    }
    p32(r.data() + o, 0xFFFFFFFF);
    p32(r.data() + 0x18, uint32_t(o + 8));
    uint16_t usn = 1;
    p16(r.data() + 0x30, usn);
    for (uint16_t i = 1; i < usa_count; ++i) {
        size_t sec_end = size_t(i) * bps;
        uint16_t orig = u16(r.data() + sec_end - 2);
        p16(r.data() + 0x30 + i * 2, orig);
        r[sec_end - 2] = uint8_t(usn);
        r[sec_end - 1] = uint8_t(usn >> 8);
    }
    return r;
}

}  // namespace

bool write_minimal_ntfs_sample(const std::string& path, std::string& err) {
    const uint32_t bps = 512;
    const uint32_t spc = 1;
    const uint32_t rec = 1024;
    const uint32_t nsec = 512;
    const uint64_t mft_lcn = 64;
    const uint64_t mirr_lcn = 16;
    const uint32_t nrec = 16;
    std::vector<uint8_t> img(nsec * bps, 0);

    auto put_rec = [&](uint64_t lcn, uint32_t recno, const std::vector<uint8_t>& recbytes) {
        size_t off = size_t(lcn) * bps + size_t(recno) * rec;
        if (off + rec > img.size()) return;
        std::memcpy(img.data() + off, recbytes.data(), rec);
    };

    // Boot
    img[0] = 0xEB;
    img[1] = 0x52;
    img[2] = 0x90;
    std::memcpy(img.data() + 3, "NTFS    ", 8);
    p16(img.data() + 11, uint16_t(bps));
    img[13] = uint8_t(spc);
    img[0x15] = 0xF8;
    p64(img.data() + 0x28, nsec - 1);
    p64(img.data() + 0x30, mft_lcn);
    p64(img.data() + 0x38, mirr_lcn);
    img[0x40] = uint8_t(int8_t(-10));  // 1024-byte records
    img[0x44] = 1;
    img[510] = 0x55;
    img[511] = 0xAA;

    std::vector<MftDataRun> mft_run{{int64_t(mft_lcn), (nrec * rec) / (bps * spc), false}};
    auto si = make_si();

    auto rec0 = stamp_file_record(
        0, 0x01,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, make_filename(5, "$MFT", nrec * rec, false).data(),
                       uint32_t(make_filename(5, "$MFT", nrec * rec, false).size())),
         attr_nonres_data(2, nrec * rec, mft_run, bps * spc)},
        rec, bps);

    auto rec1 = stamp_file_record(
        1, 0x01,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, make_filename(5, "$MFTMirr", 4 * rec, false).data(),
                       uint32_t(make_filename(5, "$MFTMirr", 4 * rec, false).size()))},
        rec, bps);

    auto rec5fn = make_filename(5, ".", 0, true);
    auto rec5 = stamp_file_record(
        5, 0x03,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, rec5fn.data(), uint32_t(rec5fn.size()))},
        rec, bps);

    auto docs_fn = make_filename(5, "docs", 0, true);
    auto rec_docs = stamp_file_record(
        8, 0x03,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, docs_fn.data(), uint32_t(docs_fn.size()))},
        rec, bps);

    const char* readme = "hello mft";
    auto readme_fn = make_filename(8, "readme.txt", std::strlen(readme), false);
    auto rec_readme = stamp_file_record(
        9, 0x01,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, readme_fn.data(), uint32_t(readme_fn.size())),
         attr_resident(0x80, 2, reinterpret_cast<const uint8_t*>(readme), uint32_t(std::strlen(readme)))},
        rec, bps);

    // Fragmented non-resident file at LCN 200 and 210
    const char frag1[512] = "FRAG1-NTFS-RUNLIST";
    const char frag2[512] = "FRAG2-NTFS-RUNLIST";
    std::memcpy(img.data() + 200 * 512, frag1, 18);
    std::memcpy(img.data() + 210 * 512, frag2, 18);
    std::vector<MftDataRun> photo_runs{{200, 1, false}, {210, 1, false}};
    uint64_t photo_sz = 512 + 18;
    auto photo_fn = make_filename(5, "photo.dat", photo_sz, false);
    auto rec_photo = stamp_file_record(
        10, 0x01,
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, photo_fn.data(), uint32_t(photo_fn.size())),
         attr_nonres_data(2, photo_sz, photo_runs, bps * spc)},
        rec, bps);

    const char* gone = "deleted-file";
    auto gone_fn = make_filename(5, "gone.txt", std::strlen(gone), false);
    auto rec_gone = stamp_file_record(
        11, 0x00,  // not in-use / deleted
        {attr_resident(0x10, 0, si.data(), uint32_t(si.size())),
         attr_resident(0x30, 1, gone_fn.data(), uint32_t(gone_fn.size())),
         attr_resident(0x80, 2, reinterpret_cast<const uint8_t*>(gone), uint32_t(std::strlen(gone)))},
        rec, bps);

    put_rec(mft_lcn, 0, rec0);
    put_rec(mft_lcn, 1, rec1);
    put_rec(mft_lcn, 5, rec5);
    put_rec(mft_lcn, 8, rec_docs);
    put_rec(mft_lcn, 9, rec_readme);
    put_rec(mft_lcn, 10, rec_photo);
    put_rec(mft_lcn, 11, rec_gone);
    // $MFTMirr: first 4 records
    put_rec(mirr_lcn, 0, rec0);
    put_rec(mirr_lcn, 1, rec1);

    std::ofstream o(path, std::ios::binary);
    if (!o) {
        err = "cannot write " + path;
        return false;
    }
    o.write(reinterpret_cast<const char*>(img.data()), static_cast<std::streamsize>(img.size()));
    return bool(o);
}

}  // namespace hsc
