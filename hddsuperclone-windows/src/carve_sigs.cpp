#include "carve_sigs.hpp"

#include <algorithm>
#include <cctype>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <filesystem>
#include <fstream>
#include <mutex>
#include <sstream>

namespace hsc {
namespace {

std::mutex g_mu;
std::vector<CarveSig> g_sigs;
std::vector<CarveCatalogEntry> g_cat;
bool g_loaded = false;

std::string trim(std::string s) {
    while (!s.empty() && (s.back() == '\r' || s.back() == ' ' || s.back() == '\t')) s.pop_back();
    size_t i = 0;
    while (i < s.size() && (s[i] == ' ' || s[i] == '\t')) ++i;
    return s.substr(i);
}

std::vector<uint8_t> parse_hex(const std::string& h) {
    std::vector<uint8_t> o;
    if (h.empty() || h == "-") return o;
    int acc = -1;
    for (char c : h) {
        int v = -1;
        if (c >= '0' && c <= '9') v = c - '0';
        else if (c >= 'a' && c <= 'f') v = c - 'a' + 10;
        else if (c >= 'A' && c <= 'F') v = c - 'A' + 10;
        else
            continue;
        if (acc < 0)
            acc = v;
        else {
            o.push_back(uint8_t((acc << 4) | v));
            acc = -1;
        }
    }
    return o;
}

std::string hex_desc(const std::vector<uint8_t>& b) {
    std::string s;
    char tmp[4];
    for (uint8_t x : b) {
        std::snprintf(tmp, sizeof(tmp), "%02X", x);
        s += tmp;
    }
    return s;
}

void apply_flags(CarveSig& sig, const std::string& flags) {
    if (flags.empty() || flags == "-") return;
    std::stringstream ss(flags);
    std::string tok;
    while (std::getline(ss, tok, '|')) {
        tok = trim(tok);
        if (tok.rfind("skip=", 0) == 0) {
            sig.skip = true;
            sig.skip_reason = tok.substr(5);
        } else if (tok.rfind("ftyp:", 0) == 0) {
            std::stringstream bs(tok.substr(5));
            std::string b;
            while (std::getline(bs, b, ',')) {
                b = trim(b);
                if (!b.empty()) sig.ftyp_brands.push_back(b);
            }
            if (sig.mag.empty()) {
                sig.mag = {'f', 't', 'y', 'p'};
                sig.mag_off = 4;
            }
        } else if (tok.rfind("mag2=", 0) == 0) {
            auto rest = tok.substr(5);
            auto at = rest.find('@');
            if (at != std::string::npos) {
                sig.mag2 = parse_hex(rest.substr(0, at));
                sig.mag2_off = uint16_t(std::atoi(rest.c_str() + at + 1));
            } else {
                sig.mag2 = parse_hex(rest);
            }
        }
    }
}

CarveCatalogEntry to_cat(const CarveSig& s) {
    CarveCatalogEntry e;
    e.ext = s.ext;
    e.category = s.category;
    e.max_bytes = s.max_bytes;
    e.greppable = !s.skip && !s.mag.empty();
    e.skip_reason = s.skip_reason;
    if (!e.greppable) {
        e.magic_desc = s.skip_reason.empty() ? "no reliable magic" : s.skip_reason;
        return e;
    }
    e.magic_desc = "off " + std::to_string(s.mag_off) + " " + hex_desc(s.mag);
    if (!s.mag2.empty()) e.magic_desc += " +" + hex_desc(s.mag2) + "@" + std::to_string(s.mag2_off);
    if (!s.ftyp_brands.empty()) {
        e.magic_desc += " ftyp[";
        for (size_t i = 0; i < s.ftyp_brands.size(); ++i) {
            if (i) e.magic_desc += ",";
            e.magic_desc += s.ftyp_brands[i];
        }
        e.magic_desc += "]";
    }
    if (!s.footer.empty()) e.magic_desc += " footer " + hex_desc(s.footer);
    return e;
}

void parse_table(const std::string& text) {
    g_sigs.clear();
    g_cat.clear();
    std::istringstream in(text);
    std::string line;
    while (std::getline(in, line)) {
        if (!line.empty() && line.back() == '\r') line.pop_back();
        line = trim(line);
        if (line.empty() || line[0] == '#') continue;
        std::vector<std::string> col;
        std::string cur;
        std::istringstream ls(line);
        while (std::getline(ls, cur, '\t')) col.push_back(trim(cur));
        if (col.size() < 3) continue;
        CarveSig s;
        s.ext = col[0];
        s.category = col.size() > 1 ? col[1] : "";
        s.mag = parse_hex(col.size() > 2 ? col[2] : "");
        s.mag_off = col.size() > 3 ? uint16_t(std::atoi(col[3].c_str())) : 0;
        s.footer = parse_hex(col.size() > 4 ? col[4] : "");
        s.max_bytes = col.size() > 5 ? uint32_t(std::strtoul(col[5].c_str(), nullptr, 10)) : 8u * 1024 * 1024;
        if (col.size() > 6) apply_flags(s, col[6]);
        if (s.skip) s.mag.clear();
        g_sigs.push_back(s);
        g_cat.push_back(to_cat(s));
    }
}

const char kBuiltinTable[] =
#include "carve_sigs_table.inc"
    ;

}  // namespace

void load_carve_signatures(const std::string& optional_file) {
    std::lock_guard<std::mutex> g(g_mu);
    std::string text;
    if (!optional_file.empty()) {
        std::ifstream f(optional_file);
        if (f) {
            std::ostringstream ss;
            ss << f.rdbuf();
            text = ss.str();
        }
    }
    if (text.empty()) text = kBuiltinTable;
    parse_table(text);
    g_loaded = true;
}

const std::vector<CarveSig>& carve_signatures() {
    std::lock_guard<std::mutex> g(g_mu);
    if (!g_loaded) {
        parse_table(kBuiltinTable);
        g_loaded = true;
    }
    return g_sigs;
}

const std::vector<CarveCatalogEntry>& carve_catalog() {
    carve_signatures();
    return g_cat;
}

int carve_greppable_count() {
    int n = 0;
    for (auto& e : carve_catalog())
        if (e.greppable) ++n;
    return n;
}
int carve_skipped_count() {
    int n = 0;
    for (auto& e : carve_catalog())
        if (!e.greppable) ++n;
    return n;
}

bool carve_filter_allows(const CarveFilter& filter, const std::string& category) {
    if (filter.all) return true;
    if (category == "photo") return filter.photo;
    if (category == "video") return filter.video;
    if (category == "audio") return filter.audio;
    if (category == "document") return filter.document;
    if (category == "archive") return filter.archive;
    if (category == "database") return filter.database;
    if (category == "mail") return filter.mail;
    if (category == "executable") return filter.executable;
    if (category == "media") return filter.media;
    return false;
}

std::string carve_filter_summary(const CarveFilter& filter) {
    if (filter.all) return "all greppable types";
    std::string s;
    auto add = [&](bool on, const char* n) {
        if (!on) return;
        if (!s.empty()) s += ", ";
        s += n;
    };
    add(filter.photo, "photos");
    add(filter.video, "video");
    add(filter.audio, "audio");
    add(filter.document, "documents");
    add(filter.archive, "archives");
    add(filter.database, "databases");
    add(filter.mail, "mail");
    add(filter.executable, "executables");
    add(filter.media, "media");
    return s.empty() ? "no categories selected" : s;
}

namespace {

bool match_at(const uint8_t* p, size_t n, size_t pos, const std::vector<uint8_t>& mag, uint16_t off) {
    if (mag.empty()) return false;
    size_t at = pos + off;
    if (at + mag.size() > n) return false;
    return std::memcmp(p + at, mag.data(), mag.size()) == 0;
}

bool ftyp_ok(const uint8_t* p, size_t n, size_t start, const std::vector<std::string>& brands) {
    if (start + 12 > n) return false;
    if (std::memcmp(p + start + 4, "ftyp", 4) != 0) return false;
    if (brands.empty()) return true;
    char brand[5]{};
    std::memcpy(brand, p + start + 8, 4);
    for (auto& b : brands)
        if (b == brand) return true;
    return false;
}

size_t find_bytes(const uint8_t* p, size_t n, size_t from, const std::vector<uint8_t>& needle) {
    if (needle.empty() || from + needle.size() > n) return size_t(-1);
    for (size_t i = from; i + needle.size() <= n; ++i)
        if (std::memcmp(p + i, needle.data(), needle.size()) == 0) return i;
    return size_t(-1);
}

bool write_file(const std::string& path, const uint8_t* p, size_t n) {
    std::error_code ec;
    std::filesystem::create_directories(std::filesystem::path(path).parent_path(), ec);
    std::ofstream o(path, std::ios::binary);
    if (!o) return false;
    o.write(reinterpret_cast<const char*>(p), static_cast<std::streamsize>(n));
    return bool(o);
}

}  // namespace

int carve_scan(DiskSession& src, const std::string& dest_dir, std::atomic<bool>* stop,
               const std::function<void(const std::string&)>& log, int& files_written, uint64_t& bytes_written,
               const CarveFilter& filter, CarveProgress* progress) {
    const auto& sigs = carve_signatures();
    uint32_t ss = src.sector_size() ? src.sector_size() : 512;
    uint64_t total = src.size_bytes() / ss;
    if (total == 0) total = 1;
    if (progress) {
        progress->bytes_total.store(total * ss);
        progress->bytes_done.store(0);
        progress->hits.store(0);
    }
    const uint32_t chunk_secs = 256;
    int carved = 0;
    int idx = 0;
    auto emit = [&](const std::string& m) {
        if (log) log(m);
    };
    emit("Grep scan: " + carve_filter_summary(filter) + " — " + std::to_string(carve_greppable_count()) +
         " greppable types in table, " + std::to_string(carve_skipped_count()) +
         " skipped (no reliable magic)");

    for (uint64_t lba = 0; lba < total && !(stop && stop->load());) {
        uint32_t n = chunk_secs;
        if (lba + n > total) n = uint32_t(total - lba);
        std::vector<uint8_t> buf(size_t(n) * ss);
        auto rd = src.read_sectors(lba, n, buf.data(), IoMode::Generic, 4000);
        if (!rd.ok) {
            lba += n;
            if (progress) progress->bytes_done.store(std::min(total, lba) * ss);
            continue;
        }
        for (const auto& sig : sigs) {
            if (sig.skip || sig.mag.empty()) continue;
            if (!carve_filter_allows(filter, sig.category)) continue;
            size_t pos = 0;
            while (pos < buf.size()) {
                size_t j = find_bytes(buf.data(), buf.size(), pos, sig.mag);
                if (j == size_t(-1)) break;
                if (j < sig.mag_off) {
                    pos = j + 1;
                    continue;
                }
                size_t start = j - sig.mag_off;
                if (!sig.mag2.empty() && !match_at(buf.data(), buf.size(), start, sig.mag2, sig.mag2_off)) {
                    pos = j + 1;
                    continue;
                }
                if (!sig.ftyp_brands.empty() && !ftyp_ok(buf.data(), buf.size(), start, sig.ftyp_brands)) {
                    pos = j + 1;
                    continue;
                }
                size_t maxn = sig.max_bytes ? sig.max_bytes : 8 * 1024 * 1024;
                size_t avail = buf.size() - start;
                size_t take = std::min(maxn, avail);
                if (!sig.footer.empty()) {
                    size_t e = find_bytes(buf.data(), buf.size(), j + sig.mag.size(), sig.footer);
                    if (e != size_t(-1)) take = std::min(maxn, e + sig.footer.size() - start);
                }
                if (take < sig.mag.size()) {
                    pos = j + 1;
                    continue;
                }
                char name[80];
                std::snprintf(name, sizeof(name), "carved_%04d.%s", idx++, sig.ext.c_str());
                std::string path = dest_dir + "/carved/" + name;
#ifdef _WIN32
                path = dest_dir + "\\carved\\" + name;
#endif
                if (write_file(path, buf.data() + start, take)) {
                    carved++;
                    files_written++;
                    bytes_written += take;
                    if (progress) progress->hits.store(carved);
                    emit(std::string("Grep hit ") + name + " (" + sig.category + ", LBA " +
                         std::to_string(lba + start / ss) + ")");
                }
                pos = start + std::max(sig.mag.size(), size_t(8));
            }
        }
        lba += n;
        if (progress) progress->bytes_done.store(std::min(total, lba) * ss);
    }
    emit("Grep scan finished: " + std::to_string(carved) + " hit(s) written to carved/");
    return carved;
}

}  // namespace hsc
