#include "app.hpp"

#include "clone_engine.hpp"
#include "disk_io.hpp"
#include "platform.hpp"
#include "safety.hpp"
#include "script_engine.hpp"
#include "usb_relay.hpp"

#include "imgui.h"

#include <algorithm>
#include <cmath>
#include <cstdio>
#include <cstring>
#include <memory>
#include <string>
#include <thread>
#include <vector>

namespace hsc {
namespace {

struct AppState {
    std::vector<DiskInfo> disks;
    int source_index = -1;
    int dest_index = -1;
    bool source_is_file = false;
    bool dest_is_file = false;
    char source_path[1024] = {};
    char dest_path[1024] = {};
    char log_path[1024] = "clone.progress.log";
    char domain_path[1024] = {};
    char ddrescue_path[1024] = {};
    char boot_confirm[64] = {};
    CloneSettings settings;
    int io_mode = 0;
    CloneEngine engine;
    std::unique_ptr<std::thread> worker;
    CloneResult last_result = CloneResult::Ok;
    std::string status_message = "Select a source disk, then a destination. Nothing is written until you start.";
    bool show_confirm = false;
    bool show_boot_confirm = false;
    bool running = false;
    bool refresh_needed = true;
    char image_filter_src[1024] = {};
    char image_filter_dst[1024] = {};
    std::vector<RelayInfo> relays;
    int relay_index = -1;
    char script_dir[1024] = {};
    std::vector<std::string> scripts;
    int script_index = 0;
    std::string script_output;
    bool script_running = false;
};

void join_worker(AppState& a) {
    if (a.worker && a.worker->joinable()) a.worker->join();
    a.worker.reset();
    a.running = false;
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

void start_clone(AppState& a, bool confirmed) {
    if (a.running) return;
    join_worker(a);

    a.settings.io_mode = static_cast<IoMode>(a.io_mode);
    a.settings.rebuild_assist = (a.settings.io_mode == IoMode::RebuildAssist) || a.settings.rebuild_assist;
    SafetyRequest req;
    req.source_path = a.source_path;
    req.dest_path = a.dest_path;
    req.source_is_file = a.source_is_file;
    req.dest_is_file = a.dest_is_file;
    if (auto* d = selected(a, a.dest_index)) req.dest_is_boot_disk = d->is_boot_disk;
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
    if (!confirmed) {
        a.show_confirm = true;
        return;
    }

    std::string err;
    if (!a.engine.prepare(a.source_path, a.source_is_file, a.dest_path, a.dest_is_file, a.log_path,
                           a.settings, a.boot_confirm, err)) {
        a.status_message = err;
        return;
    }
    a.running = true;
                a.status_message = "Cloning...";
    a.worker = std::make_unique<std::thread>([&a]() {
        a.last_result = a.engine.run();
        a.running = false;
    });
}

void draw_disk_table(AppState& a) {
    if (ImGui::Button("Refresh disks")) a.refresh_needed = true;
    ImGui::SameLine();
    if (!is_elevated()) {
        ImGui::TextColored(ImVec4(1, 0.55f, 0.2f, 1),
                            "Not running as administrator/root — physical disks may be missing.");
    } else {
        ImGui::TextUnformatted("Elevated: physical disks can be opened.");
    }

    if (ImGui::BeginTable("disks", 7,
                           ImGuiTableFlags_Borders | ImGuiTableFlags_RowBg | ImGuiTableFlags_ScrollY,
                           ImVec2(0, 180))) {
        ImGui::TableSetupColumn("Path");
        ImGui::TableSetupColumn("Model");
        ImGui::TableSetupColumn("Serial");
        ImGui::TableSetupColumn("Bus");
        ImGui::TableSetupColumn("Size");
        ImGui::TableSetupColumn("Boot");
        ImGui::TableSetupColumn("Choose");
        ImGui::TableHeadersRow();
        for (int i = 0; i < static_cast<int>(a.disks.size()); ++i) {
            const auto& d = a.disks[static_cast<size_t>(i)];
            ImGui::TableNextRow();
            ImGui::TableSetColumnIndex(0);
            ImGui::TextUnformatted(d.path.c_str());
            ImGui::TableSetColumnIndex(1);
            ImGui::TextUnformatted(d.model.c_str());
            ImGui::TableSetColumnIndex(2);
            ImGui::TextUnformatted(d.serial.c_str());
            ImGui::TableSetColumnIndex(3);
            ImGui::TextUnformatted(d.bus.c_str());
            ImGui::TableSetColumnIndex(4);
            ImGui::TextUnformatted(format_bytes(d.size_bytes).c_str());
            ImGui::TableSetColumnIndex(5);
            if (d.is_boot_disk) ImGui::TextColored(ImVec4(1, 0.3f, 0.3f, 1), "BOOT");
            else ImGui::TextUnformatted("");
            ImGui::TableSetColumnIndex(6);
            ImGui::PushID(i);
            if (ImGui::SmallButton("Source")) {
                a.source_index = i;
                a.source_is_file = false;
                std::snprintf(a.source_path, sizeof(a.source_path), "%s", d.path.c_str());
            }
            ImGui::SameLine();
            if (ImGui::SmallButton("Dest")) {
                a.dest_index = i;
                a.dest_is_file = false;
                std::snprintf(a.dest_path, sizeof(a.dest_path), "%s", d.path.c_str());
            }
            ImGui::PopID();
        }
        ImGui::EndTable();
    }
}

}  // namespace

int run_gui(int argc, char** argv) {
    (void)argc;
    (void)argv;
    if (!platform_init("HDDSuperClone for Windows", 1320, 920)) {
        std::fprintf(stderr, "Failed to create window\n");
        return 1;
    }

    AppState a;
    {
        std::string sd = application_dir() + "/scripts";
        std::snprintf(a.script_dir, sizeof(a.script_dir), "%s", sd.c_str());
        a.scripts = list_scripts(sd);
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
        if (a.refresh_needed) {
            a.disks = enumerate_disks();
            a.refresh_needed = false;
        }
        if (a.worker && !a.running && a.worker->joinable()) {
            a.worker->join();
            a.worker.reset();
            auto p = a.engine.progress();
            if (a.last_result == CloneResult::Ok && p.finished) {
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
                    join_worker(a);
                    break;
                }
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
                ImGui::TextWrapped("True kernel AHCI MMIO still needs a signed Windows driver; see driver/hscahci.");
                ImGui::EndMenu();
            }
            ImGui::EndMenuBar();
        }

        ImGui::TextUnformatted("Sector-level clone / recovery of failing disks -- not a file copy utility.");
        ImGui::Separator();
        draw_disk_table(a);

        ImGui::Separator();
        if (ImGui::BeginTable("pick", 2, ImGuiTableFlags_BordersInnerV | ImGuiTableFlags_SizingStretchSame)) {
            ImGui::TableNextColumn();
            ImGui::TextUnformatted("Source (read only)");
            ImGui::SetNextItemWidth(-1);
            ImGui::InputText("##src", a.source_path, sizeof(a.source_path));
            if (ImGui::Button("Source image file...")) {
                auto f = native_open_file("Source image", "Images\0*.img;*.dd;*.bin\0All\0*.*\0");
                if (!f.empty()) {
                    std::snprintf(a.source_path, sizeof(a.source_path), "%s", f.c_str());
                    a.source_is_file = true;
                    a.source_index = -1;
                }
            }
            ImGui::TableNextColumn();
            ImGui::TextUnformatted("Destination (WRITES HERE)");
            ImGui::SetNextItemWidth(-1);
            ImGui::InputText("##dst", a.dest_path, sizeof(a.dest_path));
            if (ImGui::Button("Destination image file...")) {
                auto f = native_save_file("Destination image", "Images\0*.img;*.dd\0All\0*.*\0");
                if (!f.empty()) {
                    std::snprintf(a.dest_path, sizeof(a.dest_path), "%s", f.c_str());
                    a.dest_is_file = true;
                    a.dest_index = -1;
                }
            }
            ImGui::EndTable();
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

        if (ImGui::CollapsingHeader("Advanced recovery (relay, virtual disk, scripts)",
                                    ImGuiTreeNodeFlags_DefaultOpen)) {
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
                    auto src = open_disk(a.source_path, false, a.source_is_file);
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
            if (ImGui::Button("Start clone", ImVec2(180, 36))) start_clone(a, false);
            ImGui::PopStyleColor();
        } else {
            if (ImGui::Button(prog.paused ? "Resume" : "Pause", ImVec2(120, 36))) {
                a.engine.set_paused(!prog.paused);
            }
            ImGui::SameLine();
            ImGui::PushStyleColor(ImGuiCol_Button, ImVec4(0.65f, 0.18f, 0.18f, 1));
            if (ImGui::Button("Stop", ImVec2(120, 36))) a.engine.request_stop();
            ImGui::PopStyleColor();
        }

        ImGui::TextWrapped("%s", a.status_message.c_str());

        ImGui::BeginChild("log", ImVec2(0, 140), true);
        auto lines = a.engine.log_snapshot();
        for (const auto& ln : lines) ImGui::TextUnformatted(ln.c_str());
        if (ImGui::GetScrollY() >= ImGui::GetScrollMaxY() - 8) ImGui::SetScrollHereY(1.0f);
        ImGui::EndChild();

        if (a.show_confirm) {
            ImGui::OpenPopup("Confirm overwrite");
            a.show_confirm = false;
        }
        if (ImGui::BeginPopupModal("Confirm overwrite", nullptr, ImGuiWindowFlags_AlwaysAutoResize)) {
            ImGui::TextWrapped(
                "This will overwrite EVERY SECTOR of:\n\n%s\n\nfrom source:\n%s\n\n"
                "There is no undo. Continue?",
                a.dest_path, a.source_path);
            if (ImGui::Button("Yes, write destination", ImVec2(220, 0))) {
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
    join_worker(a);
    platform_shutdown();
    return 0;
}

}  // namespace hsc
