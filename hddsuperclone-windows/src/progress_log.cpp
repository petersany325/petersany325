#include "progress_log.hpp"

#include <cctype>
#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <fstream>
#include <sstream>

namespace hsc {
namespace {

std::string now_string() {
    std::time_t t = std::time(nullptr);
    char buf[64];
    std::strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", std::localtime(&t));
    return buf;
}

char ddrescue_status(uint64_t st) {
    st &= kStatusMask;
    if (st == kFinished) return '+';
    if (st == kBad || st == kBadHead) return '-';
    if (st == kNonTried) return '?';
    return '*';  // non-trimmed / dividing / scraping
}

}  // namespace

bool ProgressLog::load(const std::string& file, std::string& error) {
    std::ifstream in(file);
    if (!in) {
        error = "Cannot open log file for reading: " + file;
        return false;
    }
    path = file;
    std::vector<MapRegion> regions;
    bool found_current = false;
    std::string line;
    while (std::getline(in, line)) {
        if (line.empty()) continue;
        if (line[0] == '#') {
            std::istringstream ls(line.substr(1));
            std::string key;
            ls >> key;
            auto rest = [&]() {
                std::string v;
                std::getline(ls, v);
                if (!v.empty() && v[0] == ' ') v.erase(0, 1);
                return v;
            };
            if (key == "source") source = rest();
            else if (key == "destination") destination = rest();
            else if (key == "logfile") { /* keep */ }
            else if (key == "model") model = rest();
            else if (key == "serial") serial = rest();
            else if (key == "clustersize") ls >> settings.cluster_size;
            else if (key == "blocksize") ls >> settings.block_size;
            else if (key == "sectorsize") ls >> settings.sector_size;
            else if (key == "retries") ls >> settings.retries;
            else if (key == "nophase1") ls >> settings.no_phase1;
            else if (key == "nophase2") ls >> settings.no_phase2;
            else if (key == "nophase3") ls >> settings.no_phase3;
            else if (key == "nophase4") ls >> settings.no_phase4;
            else if (key == "notrim") ls >> settings.no_trim;
            else if (key == "noscrape") ls >> settings.no_scrape;
            else if (key == "skipfast") ls >> settings.skip_fast;
            else if (key == "atapass") {
                int v = 0;
                ls >> v;
                if (v) settings.io_mode = IoMode::AtaPassthrough;
            } else if (key == "scsipass") {
                int v = 0;
                ls >> v;
                if (v) settings.io_mode = IoMode::ScsiPassthrough;
            } else if (key == "genericsource") {
                int v = 0;
                ls >> v;
                if (v) settings.io_mode = IoMode::Generic;
            }
            continue;
        }
        std::istringstream ls(line);
        std::string a, b, c, d, e;
        ls >> a >> b >> c >> d >> e;
        if (a.empty()) continue;
        if (!found_current && c.empty()) {
            current_lba = std::strtoull(a.c_str(), nullptr, 0);
            current_status = std::strtoull(b.c_str(), nullptr, 0);
            found_current = true;
            continue;
        }
        MapRegion r;
        r.position = std::strtoull(a.c_str(), nullptr, 0);
        r.size = std::strtoull(b.c_str(), nullptr, 0);
        r.status = std::strtoull(c.c_str(), nullptr, 0);
        uint64_t info = d.empty() ? 0 : std::strtoull(d.c_str(), nullptr, 0);
        uint64_t err = e.empty() ? 0 : std::strtoull(e.c_str(), nullptr, 0);
        r.status = (r.status & kStatusMask) | ((info << 8) & kInfoMask) | (err << 32);
        regions.push_back(r);
    }
    if (regions.empty()) {
        error = "Log file contained no map entries";
        return false;
    }
    uint64_t total = 0;
    for (const auto& r : regions) total += r.size;
    total_sectors = total;
    map.set_regions(std::move(regions), total);
    retries_remaining = settings.retries;
    return true;
}

bool ProgressLog::save(const std::string& file, std::string& error) const {
    std::string tmp = file + ".tmp";
    std::ofstream out(tmp);
    if (!out) {
        error = "Cannot open temporary log file: " + tmp;
        return false;
    }
    out << "# Disk progress log file created by HDDSuperClone Windows port 2.4.0-windows\n";
    out << "# " << now_string() << "\n";
    out << "# Compatible with HDDSuperClone / HDDSCViewer progress logs.\n";
    out << "#\n";
    out << "################ START CONFIGURATION DATA ################\n";
    out << "# startconfig\n";
    out << "# logfile  \t" << file << "\n";
    out << "# source  \t" << source << "\n";
    out << "# destination  \t" << destination << "\n";
    out << "# model  \t" << model << "\n";
    out << "# serial  \t" << serial << "\n";
    out << "# clustersize  \t" << settings.cluster_size << "\n";
    out << "# blocksize  \t" << settings.block_size << "\n";
    out << "# sectorsize  \t" << settings.sector_size << "\n";
    out << "# retries  \t" << settings.retries << "\n";
    out << "# nophase1  \t" << (settings.no_phase1 ? 1 : 0) << "\n";
    out << "# nophase2  \t" << settings.no_phase2 << "\n";
    out << "# nophase3  \t" << settings.no_phase3 << "\n";
    out << "# nophase4  \t" << settings.no_phase4 << "\n";
    out << "# notrim  \t" << settings.no_trim << "\n";
    out << "# noscrape  \t" << settings.no_scrape << "\n";
    out << "# skipfast  \t" << settings.skip_fast << "\n";
    out << "# atapass  \t" << (settings.io_mode == IoMode::AtaPassthrough ? 1 : 0) << "\n";
    out << "# scsipass  \t" << (settings.io_mode == IoMode::ScsiPassthrough ? 1 : 0) << "\n";
    out << "# genericsource  \t" << (settings.io_mode == IoMode::Generic ? 1 : 0) << "\n";
    out << "# minskip \t " << settings.min_skip_sectors << "\n";
    out << "# maxskip  \t" << settings.max_skip_sectors << "\n";
    out << "# endconfig\n";
    out << "################ END CONFIGURATION DATA ################\n";
    out << "#\n";
    out << "# current position  \t \tstatus\n";
    char buf[256];
    std::snprintf(buf, sizeof(buf), "0x%06llx        \t \t0x%llx\n",
                  static_cast<unsigned long long>(current_lba),
                  static_cast<unsigned long long>(current_status));
    out << buf;
    out << "#\n";
    out << "# position \tsize     \tstatus \tinfo \terr/status/time\n";
    for (const auto& r : map.regions()) {
        std::snprintf(buf, sizeof(buf), "0x%06llx \t0x%06llx \t0x%llx \t0x%llx \t0x%llx\n",
                      static_cast<unsigned long long>(r.position),
                      static_cast<unsigned long long>(r.size),
                      static_cast<unsigned long long>(r.status & kStatusMask),
                      static_cast<unsigned long long>((r.status & kInfoMask) >> 8),
                      static_cast<unsigned long long>(r.status >> 32));
        out << buf;
    }
    out.flush();
    if (!out) {
        error = "Failed writing log file";
        return false;
    }
    out.close();
    std::string bak = file + ".bak";
    std::rename(file.c_str(), bak.c_str());
    if (std::rename(tmp.c_str(), file.c_str()) != 0) {
        error = "Failed to replace log file";
        return false;
    }
    return true;
}

bool ProgressLog::save_ddrescue(const std::string& file, std::string& error) const {
    std::ofstream out(file);
    if (!out) {
        error = "Cannot write ddrescue map: " + file;
        return false;
    }
    out << "# Mapfile. Generated by HDDSuperClone Windows port\n";
    char buf[128];
    std::snprintf(buf, sizeof(buf), "0x%08llX  %c\n",
                  static_cast<unsigned long long>(current_lba * settings.sector_size),
                  current_status == kFinished ? '+' : '?');
    out << buf;
    for (const auto& r : map.regions()) {
        std::snprintf(buf, sizeof(buf), "0x%08llX  0x%08llX  %c\n",
                      static_cast<unsigned long long>(r.position * settings.sector_size),
                      static_cast<unsigned long long>(r.size * settings.sector_size),
                      ddrescue_status(r.status));
        out << buf;
    }
    return true;
}

}  // namespace hsc
