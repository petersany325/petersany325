#include "app.hpp"

#include "carve_sigs.hpp"
#include "clone_engine.hpp"
#include "disk_io.hpp"
#include "file_recovery.hpp"
#include "ntfs_mft.hpp"
#include "platform.hpp"
#include "safety.hpp"
#include "script_engine.hpp"
#include "usb_relay.hpp"

#include "imgui.h"

#include <algorithm>
#include <atomic>
#include <cmath>
#include <cstdio>
#include <cstring>
#include <filesystem>
#include <functional>
#include <memory>
#include <mutex>
#include <string>
#include <thread>
#include <unordered_map>
#include <vector>

namespace hsc {
namespace {

struct AppState {
    std::vector<DiskInfo> disks;
    std::vector<char> recover_checked;  // one per scanned device (not vector<bool>)
    int dest_index = -1;
    bool dest_is_file = false;
    char dest_path[1024] = {};
    char image_file[1024] = {};
    char dest_folder[1024] = {};
    char image_save_as[1024] = {};
    char log_path[1024] = "clone.progress.log";
    char boot_confirm[64] = {};
    CloneSettings settings;
    int io_mode = 0;
    int job_mode = 0;  // JobMode
    CloneEngine engine;
    std::unique_ptr<std::thread> worker;
    std::unique_ptr<std::thread> scan_worker;
    CloneResult last_result = CloneResult::Ok;
    std::string status_message =
        "Start scan, then TICK the damaged disk (source). Pick dest / Image HDD separately. Ticks are never the dest.";
    bool show_confirm = false;
    bool show_boot_confirm = false;
    bool running = false;
    bool scanned = false;
    bool scanning = false;
    std::atomic<bool> stop_job{false};
    int queue_index = 0;
    int queue_total = 0;
    std::vector<std::string> job_log;
    std::mutex log_mu;
    std::vector<RelayInfo> relays;
    int relay_index = -1;
    char script_dir[1024] = {};
    std::vector<std::string> scripts;
    int script_index = 0;
    std::string script_output;

    NtfsVolumeInfo mft;
    std::vector<char> mft_checked;
    std::mutex mft_mu;
    bool mft_loading = false;
    CarveFilter grep;
    CarveProgress grep_progress;
    bool show_grep_catalog = true;
};

void append_job_log(AppState& a, const std::string& s) {
    std::lock_guard<std::mutex> g(a.log_mu);
    a.job_log.push_back(s);
    if (a.job_log.size() > 400) a.job_log.erase(a.job_log.begin(), a.job_log.begin() + 80);
}

std::string sanitize_stem(std::string s) {
    for (char& c : s) {
        if (c < 32 || std::strchr("<>:\"/\\|?*", c)) c = '_';
    }
    if (s.empty()) s = "disk";
    return s;
}

std::vector<int> checked_indices(const AppState& a) {
    std::vector<int> out;
    for (int i = 0; i < static_cast<int>(a.disks.size()); ++i) {
        if (i < static_cast<int>(a.recover_checked.size()) && a.recover_checked[static_cast<size_t>(i)])
            out.push_back(i);
    }
    return out;
}

void join_worker(AppState& a) {
    if (a.worker && a.worker->joinable()) a.worker->join();
    a.worker.reset();
    a.running = false;
}

void join_scan(AppState& a) {
    if (a.scan_worker && a.scan_worker->joinable()) a.scan_worker->join();
    a.scan_worker.reset();
}

ImU32 status_color(uint64_t st) {
    st &= kStatusMask;
    if (st == kFinished) return IM_COL32(46, 160, 70, 255);
    if (st == kNonTried) return IM_COL32(90, 96, 110, 255);
    if (st == kNonTrimmed) return IM_COL32(220, 170, 40, 255);
    if (st == kNonDivided) return IM_COL32(220, 120, 30, 255);
    if (st == kNonScraped) return IM_COL32(200, 80, 40, 255);
    if (st == kBad || st == kBadHead) return IM_COL32(200, 40, 40, 255);
    return IM_COL32(70, 70, 90, 255);
}

void draw_map(const SectorMap& map, float height) {
    ImVec2 p = ImGui::GetCursorScreenPos();
    float w = ImGui::GetContentRegionAvail().x;
    if (w < 50) w = 50;
    ImDrawList* dl = ImGui::GetWindowDrawList();
    dl->AddRectFilled(p, ImVec2(p.x + w, p.y + height), IM_COL32(20, 20, 24, 255));
    uint64_t total = map.total_sectors();
    if (total == 0 || map.regions().empty()) {
        ImGui::Dummy(ImVec2(w, height));
        return;
    }
    for (const auto& r : map.regions()) {
        float x0 = p.x + (static_cast<float>(r.position) / static_cast<float>(total)) * w;
        float x1 = p.x + (static_cast<float>(r.position + r.size) / static_cast<float>(total)) * w;
        if (x1 <= x0) x1 = x0 + 1.0f;
        dl->AddRectFilled(ImVec2(x0, p.y), ImVec2(x1, p.y + height), status_color(r.status));
    }
    dl->AddRect(p, ImVec2(p.x + w, p.y + height), IM_COL32(255, 255, 255, 40));
    ImGui::Dummy(ImVec2(w, height));
}

const DiskInfo* selected(const AppState& a, int idx) {
    if (idx < 0 || idx >= static_cast<int>(a.disks.size())) return nullptr;
    return &a.disks[static_cast<size_t>(idx)];
}

struct PendingJob {
    JobMode mode = JobMode::DiskToDisk;
    std::string source;
    bool source_is_file = false;
    std::string dest;
    bool dest_is_file = false;
    bool dest_is_folder = false;
    bool dest_is_boot = false;
    CarveFilter grep;
    std::vector<uint64_t> mft_recnos;
    bool mft_selected_only = false;
};

CarveFilter current_grep_filter(const AppState& a) { return a.grep; }

void draw_signature_table(float height) {
    const auto& cat = carve_catalog();
    ImGui::Text("Signature grep table: %d greppable / %d skipped (no reliable magic)", carve_greppable_count(),
                carve_skipped_count());
    if (ImGui::BeginTable("carve_sigs", 5,
                           ImGuiTableFlags_Borders | ImGuiTableFlags_RowBg | ImGuiTableFlags_ScrollY |
                               ImGuiTableFlags_Resizable,
                           ImVec2(0, height))) {
        ImGui::TableSetupColumn("Ext", ImGuiTableColumnFlags_WidthFixed, 90);
        ImGui::TableSetupColumn("Category", ImGuiTableColumnFlags_WidthFixed, 100);
        ImGui::TableSetupColumn("Grep", ImGuiTableColumnFlags_WidthFixed, 80);
        ImGui::TableSetupColumn("Max", ImGuiTableColumnFlags_WidthFixed, 80);
        ImGui::TableSetupColumn("Magic / skip reason");
        ImGui::TableHeadersRow();
        for (const auto& e : cat) {
            ImGui::TableNextRow();
            ImGui::TableSetColumnIndex(0);
            ImGui::TextUnformatted(e.ext.c_str());
            ImGui::TableSetColumnIndex(1);
            ImGui::TextUnformatted(e.category.c_str());
            ImGui::TableSetColumnIndex(2);
            if (e.greppable)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1), "yes");
            else
                ImGui::TextDisabled("skip");
            ImGui::TableSetColumnIndex(3);
            if (e.greppable)
                ImGui::Text("%u KiB", e.max_bytes / 1024);
            else
                ImGui::TextDisabled("-");
            ImGui::TableSetColumnIndex(4);
            ImGui::TextUnformatted(e.magic_desc.c_str());
        }
        ImGui::EndTable();
    }
}

void draw_mft_table(AppState& a) {
    std::lock_guard<std::mutex> g(a.mft_mu);
    if (!a.mft.ok && a.mft.records.empty()) {
        ImGui::TextDisabled("Load NTFS $MFT from the ticked damaged source to pick FILE records.");
        return;
    }
    ImGui::Text("%s", a.mft.message.c_str());
    if (static_cast<int>(a.mft_checked.size()) != static_cast<int>(a.mft.records.size()))
        a.mft_checked.assign(a.mft.records.size(), 0);
    if (ImGui::BeginTable("mft", 8,
                           ImGuiTableFlags_Borders | ImGuiTableFlags_RowBg | ImGuiTableFlags_ScrollY |
                               ImGuiTableFlags_Resizable,
                           ImVec2(0, 180))) {
        ImGui::TableSetupColumn("Pick", ImGuiTableColumnFlags_WidthFixed, 44);
        ImGui::TableSetupColumn("#", ImGuiTableColumnFlags_WidthFixed, 50);
        ImGui::TableSetupColumn("Name");
        ImGui::TableSetupColumn("Path");
        ImGui::TableSetupColumn("Size", ImGuiTableColumnFlags_WidthFixed, 80);
        ImGui::TableSetupColumn("Kind", ImGuiTableColumnFlags_WidthFixed, 70);
        ImGui::TableSetupColumn("State", ImGuiTableColumnFlags_WidthFixed, 80);
        ImGui::TableSetupColumn("DATA", ImGuiTableColumnFlags_WidthFixed, 90);
        ImGui::TableHeadersRow();
        for (size_t i = 0; i < a.mft.records.size(); ++i) {
            const auto& r = a.mft.records[i];
            if (r.recno < 5 && r.name.empty()) continue;
            ImGui::TableNextRow();
            ImGui::TableSetColumnIndex(0);
            ImGui::PushID(int(i));
            bool on = i < a.mft_checked.size() && a.mft_checked[i] != 0;
            if (ImGui::Checkbox("##m", &on) && i < a.mft_checked.size()) a.mft_checked[i] = on ? 1 : 0;
            ImGui::PopID();
            ImGui::TableSetColumnIndex(1);
            ImGui::Text("%llu", static_cast<unsigned long long>(r.recno));
            ImGui::TableSetColumnIndex(2);
            ImGui::TextUnformatted(r.name.empty() ? "(unnamed)" : r.name.c_str());
            ImGui::TableSetColumnIndex(3);
            ImGui::TextUnformatted(r.path.c_str());
            ImGui::TableSetColumnIndex(4);
            ImGui::Text("%llu", static_cast<unsigned long long>(r.data_size));
            ImGui::TableSetColumnIndex(5);
            ImGui::TextUnformatted(r.is_dir ? "dir" : "file");
            ImGui::TableSetColumnIndex(6);
            if (r.skipped_bad)
                ImGui::TextColored(ImVec4(1, 0.4f, 0.3f, 1), "bad");
            else if (r.deleted)
                ImGui::TextColored(ImVec4(1, 0.7f, 0.3f, 1), "deleted");
            else if (r.from_mirr)
                ImGui::TextColored(ImVec4(0.6f, 0.8f, 1, 1), "mirr");
            else if (r.in_use)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1), "in-use");
            else
                ImGui::TextDisabled("free");
            ImGui::TableSetColumnIndex(7);
            if (r.is_dir)
                ImGui::TextDisabled("index");
            else if (r.resident)
                ImGui::TextUnformatted("resident");
            else if (!r.runs.empty())
                ImGui::Text("runs %d", int(r.runs.size()));
            else
                ImGui::TextDisabled("none");
        }
        ImGui::EndTable();
    }
    if (ImGui::CollapsingHeader("MFT tree (parent FILE_NAME refs)")) {
        std::unordered_map<uint64_t, std::vector<size_t>> kids;
        for (size_t i = 0; i < a.mft.records.size(); ++i) {
            const auto& r = a.mft.records[i];
            if (r.skipped_bad || r.name.empty()) continue;
            if (r.recno == 5) continue;
            kids[r.parent_recno].push_back(i);
        }
        std::function<void(uint64_t, int)> walk = [&](uint64_t parent, int depth) {
            if (depth > 12) return;
            auto it = kids.find(parent);
            if (it == kids.end()) return;
            for (size_t i : it->second) {
                const auto& r = a.mft.records[i];
                ImGui::PushID(int(i));
                if (r.is_dir) {
                    if (ImGui::TreeNode("%s  [#%llu]", r.name.c_str(),
                                        static_cast<unsigned long long>(r.recno))) {
                        walk(r.recno, depth + 1);
                        ImGui::TreePop();
                    }
                } else {
                    bool on = i < a.mft_checked.size() && a.mft_checked[i] != 0;
                    if (ImGui::Checkbox("##t", &on) && i < a.mft_checked.size()) a.mft_checked[i] = on ? 1 : 0;
                    ImGui::SameLine();
                    ImGui::Text("%s  (#%llu, %llu bytes%s)", r.name.c_str(),
                                static_cast<unsigned long long>(r.recno),
                                static_cast<unsigned long long>(r.data_size), r.deleted ? ", deleted" : "");
                }
                ImGui::PopID();
            }
        };
        walk(5, 0);
    }
}

std::string disk_stem(const DiskInfo& d) {
    return sanitize_stem(d.model.empty() ? d.path : d.model);
}

bool path_is_raw_disk(const std::string& p) {
    if (p.rfind("\\\\.\\PhysicalDrive", 0) == 0) return true;
    if (p.rfind("/dev/", 0) == 0) return true;
    return false;
}

bool dest_conflicts_source(const AppState& a, const std::string& dest) {
    if (dest.empty()) return false;
    for (int i : checked_indices(a)) {
        if (a.disks[static_cast<size_t>(i)].path == dest) return true;
    }
    return false;
}

std::string image_output_dir(const AppState& a, std::string& err) {
    namespace fs = std::filesystem;
    if (a.dest_folder[0]) {
        if (path_is_raw_disk(a.dest_folder)) {
            err = "Image HDD must be a folder or .img file on the healthy disk, not the raw disk path. "
                  "Choose a folder on that HDD, or use Disk-to-disk to clone onto the dest disk.";
            return {};
        }
        return a.dest_folder;
    }
    if (a.dest_path[0] && !a.dest_is_file) {
        if (path_is_raw_disk(a.dest_path)) {
            err = "Image HDD (destination) stores a .img file. Click 'Choose folder on Image HDD' "
                  "(or pick an .img path). For a full clone onto that disk, use Disk-to-disk.";
            return {};
        }
        fs::path base = a.dest_path;
        if (fs::is_directory(base) || (!fs::exists(base) && base.extension().empty())) return base.string();
    }
    if (a.dest_path[0] && a.dest_is_file) return {};  // single file, not a dir
    err = "Choose the Image HDD folder (destination) where .img/.dd files will be written.";
    return {};
}

std::vector<PendingJob> build_jobs(AppState& a, std::string& err) {
    std::vector<PendingJob> jobs;
    auto checked = checked_indices(a);
    auto mode = static_cast<JobMode>(a.job_mode);
    if (!a.scanned) {
        err = "Start scan first, then tick the damaged disk(s) (source).";
        return {};
    }

    if (mode == JobMode::RestoreImageToDisk) {
        if (a.image_file[0] == 0) {
            err = "Choose the .img/.dd file to restore onto the dest disk.";
            return {};
        }
        if (a.dest_path[0] == 0 || a.dest_is_file) {
            err = "Set dest HDD (the healthy disk that will be overwritten by the image).";
            return {};
        }
        if (std::strcmp(a.dest_path, a.image_file) == 0) {
            err = "Source image and dest disk cannot be the same path.";
            return {};
        }
        PendingJob j;
        j.mode = mode;
        j.source = a.image_file;
        j.source_is_file = true;
        j.dest = a.dest_path;
        j.dest_is_file = false;
        if (auto* d = selected(a, a.dest_index)) j.dest_is_boot = d->is_boot_disk;
        jobs.push_back(j);
        return jobs;
    }

    if (checked.empty()) {
        err = "After the scan, tick the damaged disk(s) (source) you recover FROM.";
        return {};
    }
    if (a.dest_index >= 0) {
        for (int i : checked) {
            if (i == a.dest_index) {
                err = "Dest / Image HDD cannot also be ticked as a damaged source. Untick that row.";
                return {};
            }
        }
    }

    if (mode == JobMode::FileRecovery || mode == JobMode::GrepScan) {
        if (a.dest_folder[0] == 0) {
            err = (mode == JobMode::GrepScan)
                      ? "Choose a folder for Grep scan hits (carved/ files)."
                      : "Choose a folder for recovered files (not the damaged disk).";
            return {};
        }
        for (int i : checked) {
            PendingJob j;
            j.mode = mode;
            j.source = a.disks[static_cast<size_t>(i)].path;
            j.source_is_file = a.disks[static_cast<size_t>(i)].bus == "File";
            std::string stem = disk_stem(a.disks[static_cast<size_t>(i)]);
            j.dest = (std::filesystem::path(a.dest_folder) / stem).string();
            j.dest_is_folder = true;
            j.grep = current_grep_filter(a);
            jobs.push_back(j);
        }
        return jobs;
    }

    if (mode == JobMode::ImageOntoDrive) {
        namespace fs = std::filesystem;
        if (a.image_save_as[0] && checked.size() == 1) {
            if (path_is_raw_disk(a.image_save_as) || dest_conflicts_source(a, a.image_save_as)) {
                err = "Choose a .img file path on the healthy Image HDD, not the damaged source disk.";
                return {};
            }
            PendingJob j;
            j.mode = mode;
            j.source = a.disks[static_cast<size_t>(checked[0])].path;
            j.source_is_file = a.disks[static_cast<size_t>(checked[0])].bus == "File";
            j.dest = a.image_save_as;
            j.dest_is_file = true;
            jobs.push_back(j);
            return jobs;
        }
        std::string dir = image_output_dir(a, err);
        if (dir.empty()) return {};
        for (int i : checked) {
            PendingJob j;
            j.mode = mode;
            j.source = a.disks[static_cast<size_t>(i)].path;
            j.source_is_file = a.disks[static_cast<size_t>(i)].bus == "File";
            j.dest = (fs::path(dir) / (disk_stem(a.disks[static_cast<size_t>(i)]) + ".img")).string();
            j.dest_is_file = true;
            jobs.push_back(j);
        }
        return jobs;
    }

    // Disk-to-disk: ticked = damaged source; dest_path = healthy dest HDD (or .img file).
    if (a.dest_path[0] == 0) {
        err = "Set dest HDD / copy disk (healthy destination), or choose an image file/folder.";
        return {};
    }
    if (dest_conflicts_source(a, a.dest_path)) {
        err = "Dest HDD cannot be the same as a ticked damaged source.";
        return {};
    }
    if (checked.size() == 1 && !a.dest_is_file) {
        PendingJob j;
        j.mode = mode;
        j.source = a.disks[static_cast<size_t>(checked[0])].path;
        j.source_is_file = a.disks[static_cast<size_t>(checked[0])].bus == "File";
        j.dest = a.dest_path;
        j.dest_is_file = false;
        if (auto* d = selected(a, a.dest_index)) j.dest_is_boot = d->is_boot_disk;
        jobs.push_back(j);
        return jobs;
    }
    for (int i : checked) {
        PendingJob j;
        j.mode = mode;
        j.source = a.disks[static_cast<size_t>(i)].path;
        j.source_is_file = a.disks[static_cast<size_t>(i)].bus == "File";
        std::string stem = disk_stem(a.disks[static_cast<size_t>(i)]);
        if (a.dest_is_file || checked.size() > 1) {
            namespace fs = std::filesystem;
            fs::path base = a.dest_path[0] ? a.dest_path : a.dest_folder;
            if (a.dest_is_file && checked.size() == 1) {
                j.dest = a.dest_path;
            } else {
                fs::path dir = fs::is_directory(base) ? base : base.parent_path();
                if (dir.empty()) dir = base;
                j.dest = (dir / (stem + ".img")).string();
            }
            j.dest_is_file = true;
        } else {
            j.dest = a.dest_path;
            j.dest_is_file = false;
            if (auto* d = selected(a, a.dest_index)) j.dest_is_boot = d->is_boot_disk;
        }
        jobs.push_back(j);
    }
    return jobs;
}

void start_load_mft(AppState& a) {
    if (a.running || a.mft_loading) return;
    auto checked = checked_indices(a);
    if (checked.empty()) {
        a.status_message = "Tick the damaged disk (source), then Load NTFS $MFT.";
        return;
    }
    int idx = checked[0];
    std::string path = a.disks[static_cast<size_t>(idx)].path;
    bool is_file = a.disks[static_cast<size_t>(idx)].bus == "File";
    a.stop_job = false;
    a.mft_loading = true;
    a.running = true;
    a.status_message = "Loading NTFS $MFT...";
    a.worker = std::make_unique<std::thread>([path, is_file, &a]() {
        auto src = open_disk(path, false, is_file);
        if (!src) {
            a.status_message = "Cannot open source for MFT scan";
            append_job_log(a, a.status_message);
            a.last_result = CloneResult::SourceError;
            a.mft_loading = false;
            a.running = false;
            return;
        }
        auto vol = scan_ntfs_on_disk(*src, &a.stop_job, [&a](const std::string& m) { append_job_log(a, m); });
        {
            std::lock_guard<std::mutex> g(a.mft_mu);
            a.mft = std::move(vol);
            a.mft_checked.assign(a.mft.records.size(), 0);
        }
        a.status_message = a.mft.message;
        a.last_result = a.mft.ok ? CloneResult::Ok : CloneResult::SourceError;
        a.mft_loading = false;
        a.running = false;
    });
}

void start_recover_mft_selected(AppState& a) {
    if (a.running) return;
    auto checked = checked_indices(a);
    if (checked.empty()) {
        a.status_message = "Tick the damaged disk (source) first.";
        return;
    }
    if (a.dest_folder[0] == 0) {
        a.status_message = "Choose a folder for recovered files.";
        return;
    }
    std::vector<uint64_t> recnos;
    {
        std::lock_guard<std::mutex> g(a.mft_mu);
        if (!a.mft.ok) {
            a.status_message = "Load NTFS $MFT first.";
            return;
        }
        for (size_t i = 0; i < a.mft.records.size() && i < a.mft_checked.size(); ++i)
            if (a.mft_checked[i]) recnos.push_back(a.mft.records[i].recno);
    }
    if (recnos.empty()) {
        a.status_message = "Pick one or more MFT records (table or tree).";
        return;
    }
    int idx = checked[0];
    std::string path = a.disks[static_cast<size_t>(idx)].path;
    bool is_file = a.disks[static_cast<size_t>(idx)].bus == "File";
    std::string dest = (std::filesystem::path(a.dest_folder) / disk_stem(a.disks[static_cast<size_t>(idx)])).string();
    a.stop_job = false;
    a.running = true;
    a.status_message = "Recovering selected MFT records...";
    a.worker = std::make_unique<std::thread>([path, is_file, dest, recnos, &a]() {
        auto src = open_disk(path, false, is_file);
        if (!src) {
            a.status_message = "Cannot open source";
            a.last_result = CloneResult::SourceError;
            a.running = false;
            return;
        }
        NtfsVolumeInfo vol;
        {
            std::lock_guard<std::mutex> g(a.mft_mu);
            vol = a.mft;
        }
        auto st = recover_mft_records(*src, vol, recnos, dest, &a.stop_job,
                                      [&a](const std::string& m) { append_job_log(a, m); });
        a.status_message = st.message;
        a.last_result = st.ok ? CloneResult::Ok : CloneResult::SourceError;
        a.running = false;
    });
}

void start_clone(AppState& a, bool confirmed) {
    if (a.running) return;
    join_worker(a);

    a.settings.io_mode = static_cast<IoMode>(a.io_mode);
    a.settings.rebuild_assist = (a.settings.io_mode == IoMode::RebuildAssist) || a.settings.rebuild_assist;

    std::string err;
    auto jobs = build_jobs(a, err);
    if (jobs.empty()) {
        a.status_message = err;
        return;
    }

    for (auto& j : jobs) {
        SafetyRequest req;
        req.source_path = j.source;
        req.dest_path = j.dest;
        req.source_is_file = j.source_is_file;
        req.dest_is_file = j.dest_is_file;
        req.dest_is_folder = j.dest_is_folder;
        req.dest_is_boot_disk = j.dest_is_boot;
        req.typed_confirmation = a.boot_confirm;
        auto chk = check_clone_safety(req);
        if (!chk.ok) {
            if (chk.needs_boot_confirm) {
                a.show_boot_confirm = true;
                a.status_message = chk.message;
                return;
            }
            a.status_message = chk.message;
            return;
        }
    }
    if (!confirmed) {
        a.show_confirm = true;
        return;
    }

    a.stop_job = false;
    a.running = true;
    a.queue_total = static_cast<int>(jobs.size());
    a.queue_index = 0;
    a.status_message = "Running " + std::to_string(jobs.size()) + " job(s)...";
    a.worker = std::make_unique<std::thread>([jobs, &a]() {
        a.last_result = CloneResult::Ok;
        for (size_t n = 0; n < jobs.size() && !a.stop_job.load(); ++n) {
            a.queue_index = static_cast<int>(n + 1);
            const auto& j = jobs[n];
            append_job_log(a, std::string("=== ") + job_mode_name(j.mode) + " ===");
            append_job_log(a, "Source: " + j.source);
            append_job_log(a, "Dest: " + j.dest);
            if (j.mode == JobMode::FileRecovery) {
                auto src = open_disk(j.source, false, j.source_is_file);
                if (!src) {
                    a.last_result = CloneResult::SourceError;
                    append_job_log(a, "Cannot open source");
                    break;
                }
                auto st = recover_files(*src, j.dest, &a.stop_job, [&a](const std::string& m) { append_job_log(a, m); });
                a.status_message = st.message;
                if (!st.ok && st.files_written == 0) a.last_result = CloneResult::SourceError;
                continue;
            }
            if (j.mode == JobMode::GrepScan) {
                auto src = open_disk(j.source, false, j.source_is_file);
                if (!src) {
                    a.last_result = CloneResult::SourceError;
                    append_job_log(a, "Cannot open source");
                    break;
                }
                auto st = grep_scan_disk(*src, j.dest, j.grep, &a.stop_job,
                                         [&a](const std::string& m) { append_job_log(a, m); }, &a.grep_progress);
                a.status_message = st.message;
                if (!st.ok && st.carved == 0) a.last_result = CloneResult::SourceError;
                continue;
            }
            std::string e;
            if (!a.engine.prepare(j.source, j.source_is_file, j.dest, j.dest_is_file, a.log_path, a.settings,
                                  a.boot_confirm, e)) {
                a.last_result = CloneResult::SafetyAbort;
                a.status_message = e;
                append_job_log(a, e);
                break;
            }
            a.last_result = a.engine.run();
            if (a.last_result != CloneResult::Ok && a.last_result != CloneResult::Stopped) break;
        }
        a.running = false;
    });
}

void set_dest_disk(AppState& a, int i, bool image_hdd) {
    a.dest_index = i;
    a.dest_is_file = false;
    std::snprintf(a.dest_path, sizeof(a.dest_path), "%s", a.disks[static_cast<size_t>(i)].path.c_str());
    if (i < static_cast<int>(a.recover_checked.size())) a.recover_checked[static_cast<size_t>(i)] = 0;
    if (image_hdd) {
        a.status_message =
            "Image HDD (destination) set. Now choose a folder on that healthy disk for the .img file(s).";
    } else {
        a.status_message = "Dest HDD / copy disk (healthy destination) set. Ticks stay on the damaged source.";
    }
}

void draw_disk_table(AppState& a) {
    ImGui::PushStyleColor(ImGuiCol_Button, ImVec4(0.12f, 0.45f, 0.55f, 1));
    if (ImGui::Button(a.scanning ? "Scanning..." : "Start scan", ImVec2(160, 36))) {
        if (!a.scanning && !a.running) {
            a.scanning = true;
            a.scanned = false;
            a.disks.clear();
            a.recover_checked.clear();
            a.dest_index = -1;
            a.scan_worker = std::make_unique<std::thread>([&a]() {
                auto disks = enumerate_disks();
                a.disks = std::move(disks);
                a.recover_checked.assign(a.disks.size(), 0);
                a.scanning = false;
                a.scanned = true;
                a.status_message = a.disks.empty()
                                       ? "Scan finished - no disks found. Run as Administrator and try again."
                                       : "Scan finished. TICK damaged disk (source). Use Set dest / Set image HDD for the healthy disk.";
            });
        }
    }
    ImGui::PopStyleColor();
    ImGui::SameLine();
    if (!is_elevated()) {
        ImGui::TextColored(ImVec4(1, 0.55f, 0.2f, 1),
                            "Not running as administrator/root - physical disks may be missing.");
    } else {
        ImGui::TextUnformatted("Elevated: physical disks can be opened.");
    }
    if (!a.scanned && !a.scanning) {
        ImGui::TextWrapped("Scan first. After the scan, tick Damaged disk (source) you recover FROM. "
                           "The healthy dest / Image HDD is a separate button - ticks never mean destination.");
        return;
    }
    if (a.scanning) {
        ImGui::TextUnformatted("Scanning disks (PhysicalDrive / USB / SCSI)...");
        return;
    }

    auto mode = static_cast<JobMode>(a.job_mode);
    ImGui::TextColored(ImVec4(1.0f, 0.55f, 0.25f, 1),
                       "TICK = Damaged disk (source)  -  recover FROM this HDD/USB");
    ImGui::SameLine();
    if (mode == JobMode::ImageOntoDrive)
        ImGui::TextColored(ImVec4(0.4f, 0.85f, 0.5f, 1), "   |   Set image HDD = healthy destination for .img files");
    else if (mode == JobMode::FileRecovery)
        ImGui::TextColored(ImVec4(0.4f, 0.85f, 0.5f, 1), "   |   Destination is a folder (below), not a tick");
    else if (mode == JobMode::GrepScan)
        ImGui::TextColored(ImVec4(0.4f, 0.85f, 0.5f, 1), "   |   Grep scan writes carved/ into the folder below");
    else if (mode == JobMode::RestoreImageToDisk)
        ImGui::TextColored(ImVec4(0.4f, 0.85f, 0.5f, 1), "   |   Set dest HDD = disk that the .img will overwrite");
    else
        ImGui::TextColored(ImVec4(0.4f, 0.85f, 0.5f, 1), "   |   Set dest HDD = Dest HDD / copy disk (healthy)");

    if (ImGui::BeginTable("disks", 7,
                           ImGuiTableFlags_Borders | ImGuiTableFlags_RowBg | ImGuiTableFlags_ScrollY,
                           ImVec2(0, 190))) {
        ImGui::TableSetupColumn("Damaged disk (source)", ImGuiTableColumnFlags_WidthFixed, 210);
        ImGui::TableSetupColumn("Path");
        ImGui::TableSetupColumn("Model");
        ImGui::TableSetupColumn("Bus");
        ImGui::TableSetupColumn("Size");
        ImGui::TableSetupColumn("Boot");
        ImGui::TableSetupColumn("Healthy dest / Image HDD", ImGuiTableColumnFlags_WidthFixed, 220);
        ImGui::TableHeadersRow();
        for (int i = 0; i < static_cast<int>(a.disks.size()); ++i) {
            const auto& d = a.disks[static_cast<size_t>(i)];
            if (static_cast<int>(a.recover_checked.size()) < static_cast<int>(a.disks.size()))
                a.recover_checked.resize(a.disks.size(), 0);
            ImGui::TableNextRow();
            ImGui::TableSetColumnIndex(0);
            ImGui::PushID(i);
            bool on = a.recover_checked[static_cast<size_t>(i)] != 0;
            if (ImGui::Checkbox("##rec", &on)) {
                a.recover_checked[static_cast<size_t>(i)] = on ? 1 : 0;
                if (on && a.dest_index == i) {
                    a.dest_index = -1;
                    a.dest_path[0] = 0;
                }
            }
            ImGui::SameLine();
            if (on)
                ImGui::TextColored(ImVec4(1.0f, 0.55f, 0.25f, 1), "SOURCE");
            ImGui::TableSetColumnIndex(1);
            ImGui::TextUnformatted(d.path.c_str());
            ImGui::TableSetColumnIndex(2);
            ImGui::TextUnformatted(d.model.c_str());
            ImGui::TableSetColumnIndex(3);
            ImGui::TextUnformatted(d.bus.c_str());
            ImGui::TableSetColumnIndex(4);
            ImGui::TextUnformatted(format_bytes(d.size_bytes).c_str());
            ImGui::TableSetColumnIndex(5);
            if (d.is_boot_disk) ImGui::TextColored(ImVec4(1, 0.3f, 0.3f, 1), "BOOT");
            ImGui::TableSetColumnIndex(6);
            if (a.dest_index == i) {
                if (mode == JobMode::ImageOntoDrive)
                    ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1), "IMAGE HDD");
                else
                    ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1), "DEST HDD");
                ImGui::SameLine();
            }
            if (mode == JobMode::FileRecovery || mode == JobMode::GrepScan) {
                ImGui::TextDisabled("use folder below");
            } else if (mode == JobMode::ImageOntoDrive) {
                if (ImGui::SmallButton("Set image HDD")) set_dest_disk(a, i, true);
            } else if (mode == JobMode::RestoreImageToDisk) {
                if (ImGui::SmallButton("Set dest HDD")) set_dest_disk(a, i, false);
            } else {
                if (ImGui::SmallButton("Set dest HDD")) set_dest_disk(a, i, false);
            }
            ImGui::PopID();
        }
        ImGui::EndTable();
    }
    int nchk = static_cast<int>(checked_indices(a).size());
    ImGui::Text("Damaged source(s) ticked: %d", nchk);
    if (a.dest_index >= 0 && selected(a, a.dest_index)) {
        ImGui::SameLine();
        ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1), "  |  %s: %s",
                           (mode == JobMode::ImageOntoDrive) ? "Image HDD" : "Dest HDD",
                           a.disks[static_cast<size_t>(a.dest_index)].path.c_str());
    }
    ImGui::SameLine();
    if (ImGui::SmallButton("Add image file as damaged source...")) {
        auto f = native_open_file("Add image as a damaged source (recover FROM)", "Images\0*.img;*.dd;*.bin\0All\0*.*\0");
        if (!f.empty()) {
            DiskInfo info;
            info.path = f;
            info.display_name = f;
            info.model = "Image file";
            info.bus = "File";
            auto s = open_disk(f, false, true);
            if (s) info.size_bytes = s->size_bytes();
            a.disks.push_back(info);
            a.recover_checked.push_back(0);
        }
    }
}

}  // namespace

int run_gui(int argc, char** argv) {
    (void)argc;
    (void)argv;
    if (!platform_init("HDDSuperClone for Windows", 1440, 1020)) {
        std::fprintf(stderr, "Failed to create window\n");
        return 1;
    }

    AppState a;
    {
        std::string sd = application_dir() + "/scripts";
        std::snprintf(a.script_dir, sizeof(a.script_dir), "%s", sd.c_str());
        a.scripts = list_scripts(sd);
        load_carve_signatures(application_dir() + "/carve_signatures.txt");
    }
    ImGuiIO& io = ImGui::GetIO();
    io.IniFilename = nullptr;
    ImGuiStyle& style = ImGui::GetStyle();
    style.WindowRounding = 6.0f;
    style.FrameRounding = 4.0f;
    style.Colors[ImGuiCol_WindowBg] = ImVec4(0.10f, 0.11f, 0.14f, 1);
    style.Colors[ImGuiCol_Button] = ImVec4(0.18f, 0.42f, 0.62f, 1);
    style.Colors[ImGuiCol_ButtonHovered] = ImVec4(0.24f, 0.52f, 0.78f, 1);

    while (!platform_should_close()) {
        platform_poll();
        if (a.scan_worker && !a.scanning && a.scan_worker->joinable()) {
            a.scan_worker->join();
            a.scan_worker.reset();
        }
        if (a.worker && !a.running && a.worker->joinable()) {
            a.worker->join();
            a.worker.reset();
            auto p = a.engine.progress();
            if (a.last_result == CloneResult::Ok &&
                (p.finished || static_cast<JobMode>(a.job_mode) == JobMode::FileRecovery ||
                 static_cast<JobMode>(a.job_mode) == JobMode::GrepScan || a.mft.ok)) {
                auto mode = static_cast<JobMode>(a.job_mode);
                if (mode == JobMode::FileRecovery)
                    a.status_message = "File recovery finished.";
                else if (mode == JobMode::GrepScan)
                    a.status_message = "Grep scan finished.";
                else if (a.mft_loading)
                    a.status_message = "MFT load finished.";
                else
                    a.status_message = "Clone finished.";
            } else if (a.last_result == CloneResult::Stopped) {
                a.status_message = "Stopped. Resume by starting again with the same progress log.";
            } else {
                a.status_message = p.last_error.empty() ? "Clone ended with an error." : p.last_error;
            }
        }

        platform_new_frame();
        ImGuiViewport* vp = ImGui::GetMainViewport();
        ImGui::SetNextWindowPos(vp->WorkPos);
        ImGui::SetNextWindowSize(vp->WorkSize);
        ImGui::Begin("HDDSuperClone", nullptr,
                     ImGuiWindowFlags_NoDecoration | ImGuiWindowFlags_NoMove |
                         ImGuiWindowFlags_MenuBar);

        if (ImGui::BeginMenuBar()) {
            if (ImGui::BeginMenu("File")) {
                if (ImGui::MenuItem("Load progress log...")) {
                    auto f = native_open_file("Open progress log", "Log\0*.log;*.progress.log\0All\0*.*\0");
                    if (!f.empty()) std::snprintf(a.log_path, sizeof(a.log_path), "%s", f.c_str());
                }
                if (ImGui::MenuItem("Choose log path...")) {
                    auto f = native_save_file("Save progress log", "Log\0*.log\0All\0*.*\0");
                    if (!f.empty()) std::snprintf(a.log_path, sizeof(a.log_path), "%s", f.c_str());
                }
                if (ImGui::MenuItem("Quit")) {
                    a.engine.request_stop();
                    a.stop_job = true;
                    join_worker(a);
                    join_scan(a);
                    break;
                }
                ImGui::EndMenu();
            }
            if (ImGui::BeginMenu("Scan")) {
                if (ImGui::MenuItem("Start disk scan", nullptr, false, !a.scanning && !a.running)) {
                    a.scanning = true;
                    a.scanned = false;
                    a.disks.clear();
                    a.recover_checked.clear();
                    a.dest_index = -1;
                    a.scan_worker = std::make_unique<std::thread>([&a]() {
                        auto disks = enumerate_disks();
                        a.disks = std::move(disks);
                        a.recover_checked.assign(a.disks.size(), 0);
                        a.scanning = false;
                        a.scanned = true;
                        a.status_message = a.disks.empty()
                                               ? "Scan finished - no disks found. Run as Administrator and try again."
                                               : "Scan finished. TICK damaged disk (source).";
                    });
                }
                ImGui::Separator();
                if (ImGui::MenuItem("Grep scan...", nullptr, a.job_mode == int(JobMode::GrepScan))) {
                    a.job_mode = int(JobMode::GrepScan);
                    a.status_message =
                        "Grep scan: tick damaged source, pick categories, choose a folder, then Start Grep scan.";
                }
                ImGui::EndMenu();
            }
            if (ImGui::BeginMenu("Recover")) {
                if (ImGui::MenuItem("Grep scan", "signature grep of damaged HDD/USB",
                                    a.job_mode == int(JobMode::GrepScan))) {
                    a.job_mode = int(JobMode::GrepScan);
                    a.status_message =
                        "Grep scan menu: magic-byte search. Tick source, choose extensions/categories, pick dest folder.";
                }
                if (ImGui::MenuItem("File recovery (FAT/NTFS + carve)", nullptr,
                                    a.job_mode == int(JobMode::FileRecovery))) {
                    a.job_mode = int(JobMode::FileRecovery);
                }
                ImGui::Separator();
                if (ImGui::MenuItem("Load NTFS $MFT", nullptr, false, !a.running)) start_load_mft(a);
                if (ImGui::MenuItem("Recover selected MFT records", nullptr, false, !a.running))
                    start_recover_mft_selected(a);
                ImGui::Separator();
                if (ImGui::MenuItem("Signature catalog", nullptr, a.show_grep_catalog))
                    a.show_grep_catalog = !a.show_grep_catalog;
                ImGui::EndMenu();
            }
            if (ImGui::BeginMenu("Help")) {
                ImGui::TextUnformatted("HDDSuperClone Windows port 2.4.0-windows");
                ImGui::TextUnformatted("Based on Scott Dwyer's HDDSuperClone (GPL-2).");
                ImGui::Separator();
                ImGui::TextUnformatted("Recovery methods on this build:");
                ImGui::TextUnformatted("  Generic, ATA/SCSI pass-through, Direct AHCI (user-mode DIRECT+reset),");
                ImGui::TextUnformatted("  Direct IDE (PIO), USB-direct (BOT/SCSI), Rebuild Assist/FPDMA,");
                ImGui::TextUnformatted("  VHDX virtual disk, USB HID relay, HDDSuperTool scripts.");
                ImGui::TextUnformatted("  NTFS $MFT walk (FILE records, runlists, $MFTMirr).");
                ImGui::TextUnformatted("  Grep scan: data-driven magic-byte signatures (photos/video/db/docs/archives).");
                ImGui::TextWrapped("True kernel AHCI MMIO still needs a signed Windows driver; see driver/hscahci.");
                ImGui::EndMenu();
            }
            ImGui::EndMenuBar();
        }

        ImGui::TextUnformatted("Sector-level clone / recovery of failing disks.");
        ImGui::TextColored(ImVec4(1.0f, 0.75f, 0.35f, 1),
                           "Damaged disk (source) = TICK after scan.  Dest / Image HDD = healthy disk you write TO.");
        ImGui::Separator();

        ImGui::Text("What is this job?");
        ImGui::PushStyleVar(ImGuiStyleVar_FramePadding, ImVec2(10, 8));
        if (ImGui::RadioButton("Disk-to-disk  -  clone damaged source onto dest HDD / copy disk", a.job_mode == 0))
            a.job_mode = 0;
        if (ImGui::RadioButton("Image onto Image HDD  -  save .img/.dd OF the damaged disk onto a healthy HDD",
                               a.job_mode == 1))
            a.job_mode = 1;
        if (ImGui::RadioButton("File recovery only  -  recover files from damaged disk into a folder",
                               a.job_mode == 2))
            a.job_mode = 2;
        if (ImGui::RadioButton("Grep scan  -  signature grep of ticked damaged HDD/USB (photos, video, databases, ...)",
                               a.job_mode == 4))
            a.job_mode = 4;
        if (ImGui::RadioButton("Restore image to dest disk  -  write an existing .img ONTO a physical disk (overwrite)",
                               a.job_mode == 3))
            a.job_mode = 3;
        ImGui::PopStyleVar();
        {
            auto mode = static_cast<JobMode>(a.job_mode);
            if (mode == JobMode::DiskToDisk)
                ImGui::TextWrapped("TICK the damaged HDD/USB (source). Then Set dest HDD (healthy copy disk). "
                                   "Several damaged sources become one .img file per disk in a dest folder.");
            else if (mode == JobMode::ImageOntoDrive)
                ImGui::TextWrapped("TICK the damaged HDD/USB (source). Then Set image HDD (healthy) and choose a "
                                   "folder on it. This CREATES a .img of the damaged disk; it does not restore an image.");
            else if (mode == JobMode::RestoreImageToDisk)
                ImGui::TextWrapped("Choose an existing .img/.dd file, then Set dest HDD. That dest disk is overwritten. "
                                   "Ticks are not used. This is restore, not imaging the damaged disk.");
            else if (mode == JobMode::GrepScan)
                ImGui::TextWrapped("Grep scan: TICK the damaged HDD/USB. Choose categories (photos, video, databases, "
                                   "documents, archives, or all). Hits are written as carved/carved_NNNN.ext. "
                                   "Types without reliable magic are listed as skip.");
            else
                ImGui::TextWrapped("TICK the damaged HDD/USB (source). Choose a folder for recovered files. "
                                   "NTFS $MFT walk (or FAT) + signature carving fallback. Load MFT to pick records.");
        }
        ImGui::Separator();
        draw_disk_table(a);

        ImGui::Separator();
        {
            auto mode = static_cast<JobMode>(a.job_mode);
            if (mode == JobMode::ImageOntoDrive)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1),
                                   "Image HDD (destination)  -  healthy disk / folder that RECEIVES the .img file");
            else if (mode == JobMode::FileRecovery)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1),
                                   "Recovered-files folder (destination)  -  not the damaged disk");
            else if (mode == JobMode::GrepScan)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1),
                                   "Grep scan folder (destination)  -  carved hits, not the damaged disk");
            else if (mode == JobMode::RestoreImageToDisk)
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1),
                                   "Dest HDD (overwrite)  -  physical disk that receives the restored image");
            else
                ImGui::TextColored(ImVec4(0.4f, 0.9f, 0.5f, 1),
                                   "Dest HDD / copy disk (destination)  -  healthy disk you clone ONTO");
            ImGui::TextDisabled("%s", job_mode_name(mode));
            if (mode == JobMode::ImageOntoDrive) {
                ImGui::TextUnformatted("Folder on Image HDD (stores .img of the damaged source)");
                ImGui::SetNextItemWidth(-280);
                ImGui::InputText("##imgfolder", a.dest_folder, sizeof(a.dest_folder));
                ImGui::SameLine();
                if (ImGui::Button("Choose folder on Image HDD...")) {
                    auto f = native_pick_folder("Folder on the healthy Image HDD for .img files");
                    if (!f.empty()) {
                        std::snprintf(a.dest_folder, sizeof(a.dest_folder), "%s", f.c_str());
                        a.dest_is_file = false;
                    }
                }
                ImGui::TextUnformatted("Or save as a single .img file");
                ImGui::SetNextItemWidth(-280);
                ImGui::InputText("##imgfile", a.image_save_as, sizeof(a.image_save_as));
                ImGui::SameLine();
                if (ImGui::Button("Choose .img file path...")) {
                    auto f = native_save_file("Save image of damaged disk", "Images\0*.img;*.dd\0All\0*.*\0");
                    if (!f.empty()) std::snprintf(a.image_save_as, sizeof(a.image_save_as), "%s", f.c_str());
                }
                ImGui::TextDisabled("Ticks above = damaged source. Set image HDD marks which healthy disk you meant.");
            } else if (mode == JobMode::RestoreImageToDisk) {
                ImGui::TextUnformatted("Existing image file (source to restore FROM)");
                ImGui::SetNextItemWidth(-280);
                ImGui::InputText("##img", a.image_file, sizeof(a.image_file));
                ImGui::SameLine();
                if (ImGui::Button("Choose .img to restore...")) {
                    auto f = native_open_file("Image file to write onto dest HDD",
                                              "Images\0*.img;*.dd;*.bin;*.vhd;*.vhdx\0All\0*.*\0");
                    if (!f.empty()) std::snprintf(a.image_file, sizeof(a.image_file), "%s", f.c_str());
                }
                ImGui::TextUnformatted("Dest HDD that will be overwritten");
                ImGui::SetNextItemWidth(-1);
                ImGui::InputText("##dst", a.dest_path, sizeof(a.dest_path));
                ImGui::TextDisabled("Use Set dest HDD in the table. This overwrites that physical disk.");
            } else if (mode == JobMode::FileRecovery || mode == JobMode::GrepScan) {
                ImGui::TextUnformatted(mode == JobMode::GrepScan ? "Folder for Grep scan hits (carved/)"
                                                                 : "Folder for recovered files");
                ImGui::SetNextItemWidth(-220);
                ImGui::InputText("##folder", a.dest_folder, sizeof(a.dest_folder));
                ImGui::SameLine();
                if (ImGui::Button("Choose folder...")) {
                    auto f = native_pick_folder(mode == JobMode::GrepScan ? "Grep scan output folder"
                                                                          : "Recovered files folder");
                    if (!f.empty()) std::snprintf(a.dest_folder, sizeof(a.dest_folder), "%s", f.c_str());
                }
                if (mode == JobMode::GrepScan) {
                    ImGui::Separator();
                    ImGui::TextUnformatted("Grep scan categories (extensions use the signature table)");
            if (ImGui::Checkbox("All greppable types", &a.grep.all)) {
                if (!a.grep.all) {
                    a.grep.photo = a.grep.video = a.grep.audio = a.grep.document = a.grep.archive =
                        a.grep.database = a.grep.mail = a.grep.executable = a.grep.media = false;
                    a.grep.photo = true;
                }
            }
                    ImGui::BeginDisabled(a.grep.all);
                    ImGui::Checkbox("Photos", &a.grep.photo);
                    ImGui::SameLine();
                    ImGui::Checkbox("Video", &a.grep.video);
                    ImGui::SameLine();
                    ImGui::Checkbox("Audio", &a.grep.audio);
                    ImGui::SameLine();
                    ImGui::Checkbox("Documents", &a.grep.document);
                    ImGui::SameLine();
                    ImGui::Checkbox("Archives", &a.grep.archive);
                    ImGui::Checkbox("Databases", &a.grep.database);
                    ImGui::SameLine();
                    ImGui::Checkbox("Mail", &a.grep.mail);
                    ImGui::SameLine();
                    ImGui::Checkbox("Executables", &a.grep.executable);
                    ImGui::SameLine();
                    ImGui::Checkbox("Media extras", &a.grep.media);
                    ImGui::EndDisabled();
                    ImGui::TextDisabled("Filter: %s", carve_filter_summary(a.grep).c_str());
                    uint64_t done = a.grep_progress.bytes_done.load();
                    uint64_t tot = a.grep_progress.bytes_total.load();
                    int hits = a.grep_progress.hits.load();
                    float gfrac = (tot > 0) ? float(double(done) / double(tot)) : 0.0f;
                    ImGui::ProgressBar(gfrac, ImVec2(-1, 0),
                                       (std::to_string(hits) + " hits").c_str());
                    ImGui::Text("Grep progress: %llu / %llu bytes   hits %d",
                                static_cast<unsigned long long>(done), static_cast<unsigned long long>(tot), hits);
                    if (a.show_grep_catalog) draw_signature_table(160.0f);
                } else {
                    ImGui::Separator();
                    if (ImGui::Button("Load NTFS $MFT") && !a.running) start_load_mft(a);
                    ImGui::SameLine();
                    if (ImGui::Button("Recover selected MFT records") && !a.running) start_recover_mft_selected(a);
                    ImGui::SameLine();
                    ImGui::TextDisabled("Carve is the fallback after the MFT/FAT walk.");
                    draw_mft_table(a);
                    if (ImGui::CollapsingHeader("Signature grep catalog (carve fallback)")) draw_signature_table(140.0f);
                }
            } else {
                ImGui::TextUnformatted("Dest HDD / copy disk (WRITES HERE)");
                ImGui::SetNextItemWidth(-1);
                ImGui::InputText("##dst", a.dest_path, sizeof(a.dest_path));
                if (ImGui::Button("Destination image file / folder...")) {
                    auto f = native_save_file("Destination image (or type a folder path)", "Images\0*.img;*.dd\0All\0*.*\0");
                    if (!f.empty()) {
                        std::snprintf(a.dest_path, sizeof(a.dest_path), "%s", f.c_str());
                        a.dest_is_file = true;
                        a.dest_index = -1;
                    }
                }
            }
        }

        ImGui::TextUnformatted("Progress log (resume)");
        ImGui::SetNextItemWidth(-1);
        ImGui::InputText("##log", a.log_path, sizeof(a.log_path));

        const char* modes[] = {
            "Auto-detect",
            "Generic (block I/O)",
            "ATA pass-through",
            "SCSI pass-through",
            "Direct AHCI (pass-through DIRECT + reset)",
            "Direct IDE (ATA PIO)",
            "USB-direct (BOT / USB SCSI)",
            "Rebuild Assist / FPDMA",
        };
        ImGui::TextUnformatted("Recovery / I/O mode");
        ImGui::SetNextItemWidth(420);
        ImGui::Combo("##iomode", &a.io_mode, modes, IM_ARRAYSIZE(modes));
        ImGui::SameLine();
        ImGui::TextDisabled("(?)");
        if (ImGui::IsItemHovered()) {
            ImGui::SetTooltip(
                "Direct AHCI uses IOCTL_ATA_PASS_THROUGH_DIRECT + DEVICE RESET.\n"
                "Kernel MMIO AHCI (Linux hscahci) is not loadable unsigned on 64-bit Windows.\n"
                "USB-direct uses SCSI BOT; WinUSB after Zadig talks to the device without USBSTOR.\n"
                "Rebuild Assist issues READ FPDMA QUEUED and splits on NCQ error LBA.");
        }

        if (ImGui::CollapsingHeader("Advanced recovery (relay, virtual disk, scripts)")) {
            ImGui::Checkbox("Rebuild Assist (enable log 0x15 + FPDMA)", &a.settings.rebuild_assist);
            ImGui::SameLine();
            ImGui::Checkbox("Virtual disk destination (VHDX / sparse image)", &a.settings.virtual_disk_dest);
            ImGui::Checkbox("USB relay power-cycle on read error", &a.settings.relay_on_error);
            ImGui::SameLine();
            if (ImGui::Button("Scan USB relays")) {
                a.relays = enumerate_relays();
                if (!a.relays.empty()) {
                    a.relay_index = 0;
                    a.settings.relay_path = a.relays[0].path;
                }
            }
            if (!a.relays.empty()) {
                std::vector<const char*> names;
                for (auto& r : a.relays) names.push_back(r.name.empty() ? r.path.c_str() : r.name.c_str());
                ImGui::SetNextItemWidth(360);
                if (ImGui::Combo("Relay", &a.relay_index, names.data(), static_cast<int>(names.size()))) {
                    if (a.relay_index >= 0 && a.relay_index < static_cast<int>(a.relays.size()))
                        a.settings.relay_path = a.relays[static_cast<size_t>(a.relay_index)].path;
                }
                ImGui::SameLine();
                ImGui::SetNextItemWidth(80);
                ImGui::InputInt("Ch", &a.settings.relay_channel);
                if (a.settings.relay_channel < 0) a.settings.relay_channel = 0;
                if (a.settings.relay_channel > 8) a.settings.relay_channel = 8;
            } else {
                ImGui::TextDisabled("No dcttech HID relay (16C0:05DF) found.");
            }

            ImGui::Separator();
            ImGui::TextUnformatted("HDDSuperTool scripts");
            ImGui::SetNextItemWidth(-180);
            ImGui::InputText("##scriptdir", a.script_dir, sizeof(a.script_dir));
            ImGui::SameLine();
            if (ImGui::Button("Reload scripts")) a.scripts = list_scripts(a.script_dir);
            if (!a.scripts.empty()) {
                std::vector<const char*> sn;
                for (auto& s : a.scripts) sn.push_back(s.c_str());
                ImGui::SetNextItemWidth(360);
                ImGui::Combo("##script", &a.script_index, sn.data(), static_cast<int>(sn.size()));
                ImGui::SameLine();
                if (ImGui::Button("Run script") && !a.running) {
                    ScriptEngine se;
                    se.set_script_dir(a.script_dir);
                    std::unique_ptr<DiskSession> src;
                    auto checked = checked_indices(a);
                    if (!checked.empty())
                        src = open_disk(a.disks[static_cast<size_t>(checked[0])].path, false, false);
                    if (src) se.set_disk(src.get());
                    std::string path = std::string(a.script_dir) + "/" +
                                       a.scripts[static_cast<size_t>(a.script_index)];
                    auto sr = se.run_file(path);
                    a.script_output = sr.output;
                    a.status_message = sr.ok ? "Script finished." : "Script ended with errors.";
                }
            } else {
                ImGui::TextDisabled("No scripts in that folder (installer copies scripts/ next to the exe).");
            }
            if (!a.script_output.empty()) {
                ImGui::BeginChild("scriptout", ImVec2(0, 90), true);
                ImGui::TextUnformatted(a.script_output.c_str());
                ImGui::EndChild();
            }
        }

        bool p1 = !a.settings.no_phase1, p2 = !a.settings.no_phase2, p3 = !a.settings.no_phase3,
             p4 = !a.settings.no_phase4, tr = !a.settings.no_trim, sc = !a.settings.no_scrape;
        if (ImGui::Checkbox("Phase 1", &p1)) a.settings.no_phase1 = !p1;
        ImGui::SameLine();
        if (ImGui::Checkbox("Phase 2", &p2)) a.settings.no_phase2 = !p2;
        ImGui::SameLine();
        if (ImGui::Checkbox("Phase 3", &p3)) a.settings.no_phase3 = !p3;
        ImGui::SameLine();
        if (ImGui::Checkbox("Phase 4", &p4)) a.settings.no_phase4 = !p4;
        ImGui::SameLine();
        if (ImGui::Checkbox("Trim", &tr)) a.settings.no_trim = !tr;
        ImGui::SameLine();
        if (ImGui::Checkbox("Scrape", &sc)) a.settings.no_scrape = !sc;
        ImGui::Checkbox("Skip on error (phases 1-2)", &a.settings.skip_enabled);
        ImGui::SameLine();
        ImGui::Checkbox("Skip fast", &a.settings.skip_fast);

        if (ImGui::BeginTable("settings", 3, ImGuiTableFlags_SizingStretchProp)) {
            ImGui::TableNextColumn();
            ImGui::TextUnformatted("Cluster (sectors)");
            ImGui::SetNextItemWidth(-1);
            ImGui::InputInt("##cluster", &a.settings.cluster_size);
            ImGui::TableNextColumn();
            ImGui::TextUnformatted("Retries");
            ImGui::SetNextItemWidth(-1);
            ImGui::InputInt("##retries", &a.settings.retries);
            ImGui::TableNextColumn();
            ImGui::TextUnformatted("Min skip (KiB)");
            ImGui::SetNextItemWidth(-1);
            int skip_kb = static_cast<int>((a.settings.min_skip_sectors * a.settings.sector_size) / 1024);
            if (ImGui::InputInt("##skipkb", &skip_kb)) {
                if (skip_kb < 64) skip_kb = 64;
                a.settings.min_skip_sectors = (static_cast<int64_t>(skip_kb) * 1024) / a.settings.sector_size;
            }
            ImGui::EndTable();
        }

        auto prog = a.engine.progress();
        draw_map(a.engine.map(), 28.0f);
        ImGui::Text("Finished %s / %s   phase: %s   LBA %llu",
                    format_bytes(prog.stats.finished_sectors * static_cast<uint64_t>(a.settings.sector_size))
                        .c_str(),
                    format_bytes(prog.stats.total_sectors * static_cast<uint64_t>(a.settings.sector_size))
                        .c_str(),
                    prog.phase_name.c_str(),
                    static_cast<unsigned long long>(prog.current_lba));
        float frac = (prog.stats.total_sectors > 0)
                          ? static_cast<float>(prog.stats.finished_sectors) /
                                static_cast<float>(prog.stats.total_sectors)
                          : 0.0f;
        ImGui::ProgressBar(frac, ImVec2(-1, 0));
        ImGui::Text("Non-tried %llu  trimmed-pending %llu  bad %llu  skips %d  rate %.1f KiB/s",
                    static_cast<unsigned long long>(prog.stats.nontried_sectors),
                    static_cast<unsigned long long>(prog.stats.nontrimmed_sectors),
                    static_cast<unsigned long long>(prog.stats.bad_sectors), prog.skip_count,
                    prog.rate_bps / 1024.0);

        if (!a.running) {
            ImGui::PushStyleColor(ImGuiCol_Button, ImVec4(0.15f, 0.55f, 0.25f, 1));
            const char* go = "Start disk clone";
            if (static_cast<JobMode>(a.job_mode) == JobMode::FileRecovery) go = "Start file recovery";
            else if (static_cast<JobMode>(a.job_mode) == JobMode::GrepScan) go = "Start Grep scan";
            else if (static_cast<JobMode>(a.job_mode) == JobMode::ImageOntoDrive) go = "Save image of damaged disk";
            else if (static_cast<JobMode>(a.job_mode) == JobMode::RestoreImageToDisk) go = "Restore image onto dest disk";
            if (ImGui::Button(go, ImVec2(240, 36))) start_clone(a, false);
            ImGui::PopStyleColor();
        } else {
            if (ImGui::Button(prog.paused ? "Resume" : "Pause", ImVec2(120, 36))) {
                a.engine.set_paused(!prog.paused);
            }
            ImGui::SameLine();
            ImGui::PushStyleColor(ImGuiCol_Button, ImVec4(0.65f, 0.18f, 0.18f, 1));
            if (ImGui::Button("Stop", ImVec2(120, 36))) {
                a.stop_job = true;
                a.engine.request_stop();
            }
            ImGui::PopStyleColor();
            ImGui::SameLine();
            ImGui::Text("Job %d / %d", a.queue_index, a.queue_total);
        }

        ImGui::TextWrapped("%s", a.status_message.c_str());

        ImGui::BeginChild("log", ImVec2(0, 120), true);
        {
            std::lock_guard<std::mutex> g(a.log_mu);
            for (const auto& ln : a.job_log) ImGui::TextUnformatted(ln.c_str());
        }
        auto lines = a.engine.log_snapshot();
        for (const auto& ln : lines) ImGui::TextUnformatted(ln.c_str());
        if (ImGui::GetScrollY() >= ImGui::GetScrollMaxY() - 8) ImGui::SetScrollHereY(1.0f);
        ImGui::EndChild();

        if (a.show_confirm) {
            ImGui::OpenPopup("Confirm overwrite");
            a.show_confirm = false;
        }
        if (ImGui::BeginPopupModal("Confirm overwrite", nullptr, ImGuiWindowFlags_AlwaysAutoResize)) {
            ImGui::TextWrapped("%s",
                               "This will write to the destination (dest HDD, Image HDD folder, or restore target). "
                               "The ticked row is the damaged source. There is no undo.");
            if (ImGui::Button("Yes, start the job", ImVec2(220, 0))) {
                ImGui::CloseCurrentPopup();
                start_clone(a, true);
            }
            ImGui::SameLine();
            if (ImGui::Button("Cancel", ImVec2(120, 0))) ImGui::CloseCurrentPopup();
            ImGui::EndPopup();
        }
        if (a.show_boot_confirm) {
            ImGui::OpenPopup("Boot disk warning");
            a.show_boot_confirm = false;
        }
        if (ImGui::BeginPopupModal("Boot disk warning", nullptr, ImGuiWindowFlags_AlwaysAutoResize)) {
            ImGui::TextWrapped("%s",
                               "Destination is the system/boot disk. Cloning onto it will destroy "
                               "the running operating system.\n\nType OVERWRITE BOOT DISK to proceed.");
            ImGui::InputText("##boot", a.boot_confirm, sizeof(a.boot_confirm));
            if (ImGui::Button("Proceed")) {
                ImGui::CloseCurrentPopup();
                start_clone(a, false);
            }
            ImGui::SameLine();
            if (ImGui::Button("Cancel")) {
                a.boot_confirm[0] = 0;
                ImGui::CloseCurrentPopup();
            }
            ImGui::EndPopup();
        }

        ImGui::End();
        platform_render();
    }

    a.engine.request_stop();
    a.stop_job = true;
    join_worker(a);
    join_scan(a);
    platform_shutdown();
    return 0;
}

}  // namespace hsc
