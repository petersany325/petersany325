#include "script_engine.hpp"

#include <algorithm>
#include <cctype>
#include <cstdlib>
#include <cstdio>
#include <fstream>
#include <sstream>

#ifdef _WIN32
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#else
#include <dirent.h>
#endif

namespace hsc {
namespace {

std::string trim(std::string s) {
    while (!s.empty() && std::isspace(static_cast<unsigned char>(s.front()))) s.erase(s.begin());
    while (!s.empty() && std::isspace(static_cast<unsigned char>(s.back()))) s.pop_back();
    return s;
}

std::vector<std::string> split_ws(const std::string& s) {
    std::vector<std::string> v;
    std::istringstream in(s);
    std::string t;
    while (in >> t) v.push_back(t);
    return v;
}

}  // namespace

std::vector<std::string> list_scripts(const std::string& dir) {
    std::vector<std::string> out;
#ifdef _WIN32
    WIN32_FIND_DATAA fd;
    HANDLE h = FindFirstFileA((dir + "\\*").c_str(), &fd);
    if (h == INVALID_HANDLE_VALUE) return out;
    do {
        if (!(fd.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY)) out.push_back(fd.cFileName);
    } while (FindNextFileA(h, &fd));
    FindClose(h);
#else
    DIR* d = opendir(dir.c_str());
    if (!d) return out;
    while (auto* e = readdir(d)) {
        std::string n = e->d_name;
        if (n == "." || n == "..") continue;
        out.push_back(n);
    }
    closedir(d);
#endif
    std::sort(out.begin(), out.end());
    return out;
}

int64_t ScriptEngine::eval_int(const std::string& tok) {
    if (!tok.empty() && tok[0] == '$') {
        auto it = ints_.find(tok);
        if (it != ints_.end()) return it->second;
        return 0;
    }
    if (tok.size() > 2 && tok[0] == '0' && (tok[1] == 'x' || tok[1] == 'X'))
        return static_cast<int64_t>(std::strtoll(tok.c_str(), nullptr, 16));
    return static_cast<int64_t>(std::strtoll(tok.c_str(), nullptr, 0));
}

std::string ScriptEngine::eval_str(const std::string& tok) {
    if (!tok.empty() && tok[0] == '$') {
        auto it = strs_.find(tok);
        if (it != strs_.end()) return it->second;
        auto ii = ints_.find(tok);
        if (ii != ints_.end()) return std::to_string(ii->second);
        return "";
    }
    return tok;
}

bool ScriptEngine::cond_true(const std::string& expr) {
    auto p = expr.find('=');
    if (p == std::string::npos) return eval_int(trim(expr)) != 0;
    std::string a = trim(expr.substr(0, p));
    std::string b = trim(expr.substr(p + 1));
    return eval_int(a) == eval_int(b);
}

ScriptResult ScriptEngine::run_file(const std::string& path) {
    std::ifstream in(path);
    if (!in) {
        ScriptResult r;
        r.ok = false;
        r.exit_code = 1;
        r.output = "Cannot open script: " + path + "\n";
        return r;
    }
    std::ostringstream ss;
    ss << in.rdbuf();
    return run_text(ss.str(), path);
}

ScriptResult ScriptEngine::run_text(const std::string& text, const std::string& name) {
    out_.clear();
    std::vector<std::string> lines;
    std::istringstream in(text);
    std::string line;
    while (std::getline(in, line)) {
        if (!line.empty() && line.back() == '\r') line.pop_back();
        lines.push_back(line);
    }
    int rc = run_lines(lines, name, 0);
    ScriptResult r;
    r.exit_code = rc;
    r.output = out_;
    r.ok = rc == 0 || rc == -100;
    return r;
}

int ScriptEngine::run_lines(const std::vector<std::string>& lines, const std::string& name, int depth) {
    if (depth > 16) {
        out_ += "include depth exceeded\n";
        return 1;
    }
    bool skip = false;
    int skip_depth = 0;
    for (size_t i = 0; i < lines.size(); ++i) {
        std::string raw = trim(lines[i]);
        if (raw.empty() || raw[0] == '#') continue;
        auto sp = raw.find(' ');
        std::string cmd = sp == std::string::npos ? raw : raw.substr(0, sp);
        std::string rest = sp == std::string::npos ? "" : trim(raw.substr(sp + 1));
        std::transform(cmd.begin(), cmd.end(), cmd.begin(),
                       [](unsigned char c) { return static_cast<char>(std::tolower(c)); });

        if (cmd == "if") {
            bool t = cond_true(rest);
            if (!t) {
                skip = true;
                skip_depth = 1;
            }
            continue;
        }
        if (cmd == "endif") {
            if (skip_depth > 0) --skip_depth;
            if (skip_depth == 0) skip = false;
            continue;
        }
        if (skip) {
            if (cmd == "if") ++skip_depth;
            continue;
        }

        if (cmd == "echo") {
            std::string s = rest;
            if (!s.empty() && (s.front() == '\'' || s.front() == '"')) {
                char q = s.front();
                if (s.size() >= 2 && s.back() == q) s = s.substr(1, s.size() - 2);
            } else {
                auto parts = split_ws(rest);
                s.clear();
                for (auto& p : parts) {
                    if (!s.empty()) s += " ";
                    s += eval_str(p);
                }
            }
            out_ += s + "\n";
        } else if (cmd == "seti") {
            auto eq = rest.find('=');
            if (eq != std::string::npos) {
                std::string namev = trim(rest.substr(0, eq));
                std::string val = trim(rest.substr(eq + 1));
                auto amp = val.find('&');
                if (amp != std::string::npos) {
                    ints_[namev] = eval_int(trim(val.substr(0, amp))) & eval_int(trim(val.substr(amp + 1)));
                } else if (val.find("buffer") == 0) {
                    auto toks = split_ws(val);
                    int off = toks.size() > 1 ? static_cast<int>(eval_int(toks[1])) : 0;
                    if (off >= 0 && off < static_cast<int>(buffer_.size())) ints_[namev] = buffer_[static_cast<size_t>(off)];
                    else ints_[namev] = 0;
                } else {
                    ints_[namev] = eval_int(val);
                }
            }
        } else if (cmd == "sets") {
            auto eq = rest.find('=');
            if (eq != std::string::npos) {
                std::string namev = trim(rest.substr(0, eq));
                std::string val = trim(rest.substr(eq + 1));
                if (val.find("buffer") == 0) {
                    auto toks = split_ws(val);
                    int off = toks.size() > 1 ? static_cast<int>(eval_int(toks[1])) : 0;
                    int n = toks.size() > 2 ? static_cast<int>(eval_int(toks[2])) : 0;
                    std::string s;
                    for (int k = 0; k < n && off + k < static_cast<int>(buffer_.size()); ++k)
                        s.push_back(static_cast<char>(buffer_[static_cast<size_t>(off + k)]));
                    strs_[namev] = s;
                } else {
                    strs_[namev] = eval_str(val);
                }
            }
        } else if (cmd == "buffersize") {
            int n = static_cast<int>(eval_int(rest));
            if (n < 0) n = 0;
            if (n > 1024 * 1024) n = 1024 * 1024;
            buffer_.assign(static_cast<size_t>(n), 0);
        } else if (cmd == "printbuffer") {
            auto toks = split_ws(rest);
            int off = toks.empty() ? 0 : static_cast<int>(eval_int(toks[0]));
            int n = toks.size() > 1 ? static_cast<int>(eval_int(toks[1])) : 16;
            char line[128];
            for (int k = 0; k < n; ++k) {
                int idx = off + k;
                unsigned v = (idx >= 0 && idx < static_cast<int>(buffer_.size())) ? buffer_[static_cast<size_t>(idx)] : 0;
                std::snprintf(line, sizeof(line), "%02X%s", v, ((k + 1) % 16) ? " " : "\n");
                out_ += line;
            }
            if (n % 16) out_ += "\n";
        } else if (cmd == "wordflipbuffer") {
            for (size_t k = 0; k + 1 < buffer_.size(); k += 2) std::swap(buffer_[k], buffer_[k + 1]);
        } else if (cmd == "ata28cmd" || cmd == "ata48cmd") {
            auto toks = split_ws(rest);
            uint8_t cmdb = 0;
            uint64_t lba = 0;
            uint16_t count = 1;
            if (cmd == "ata28cmd" && toks.size() >= 7) {
                count = static_cast<uint16_t>(eval_int(toks[1]));
                lba = static_cast<uint64_t>(eval_int(toks[2])) |
                      (static_cast<uint64_t>(eval_int(toks[3])) << 8) |
                      (static_cast<uint64_t>(eval_int(toks[4])) << 16);
                cmdb = static_cast<uint8_t>(eval_int(toks[6]));
            } else if (toks.size() >= 1) {
                cmdb = static_cast<uint8_t>(eval_int(toks.back()));
            }
            if (disk_ && !buffer_.empty()) {
                DiskSession::AtaTaskfile tf;
                tf.command = cmdb;
                tf.lba = lba;
                tf.count = count ? count : 1;
                tf.ext48 = (cmd == "ata48cmd");
                tf.data_in = true;
                tf.dma = (cmdb == 0x25 || cmdb == 0x29 || cmdb == 0x60);
                IoResult ir = disk_->send_ata(tf, buffer_.data(), static_cast<uint32_t>(buffer_.size()), 5000);
                if (!ir.ok && (cmdb == 0xEC || cmdb == 0x24 || cmdb == 0x25 || cmdb == 0x29)) {
                    ir = disk_->read_sectors(lba, count ? count : 1, buffer_.data(), IoMode::AtaPassthrough, 5000);
                }
                out_ += ir.ok ? "ata command ok\n" : ("ata command failed: " + ir.message + "\n");
            } else {
                out_ += "no disk open; ata command not sent\n";
            }
        } else if (cmd == "softreset") {
            if (disk_) {
                auto ir = disk_->device_reset(2000);
                out_ += ir.ok ? "soft reset ok\n" : ("soft reset: " + ir.message + "\n");
            }
        } else if (cmd == "hardreset") {
            if (disk_) {
                auto ir = disk_->device_reset(5000);
                out_ += ir.ok ? "hard reset attempted\n" : ("hard reset: " + ir.message + "\n");
            }
        } else if (cmd == "sleep" || cmd == "usleep") {
            // ignore delay in batch/test runs beyond logging
            out_ += "sleep\n";
        } else if (cmd == "include") {
            std::string p = rest;
            if (!script_dir_.empty() && p.find('/') == std::string::npos && p.find('\\') == std::string::npos)
                p = script_dir_ + "/" + p;
            auto nested = run_file(p);
            out_ += nested.output;
        } else if (cmd == "end" || cmd == "exit") {
            return cmd == "end" ? -100 : 0;
        } else if (cmd == "gosub" || cmd == "previousscript" || cmd == "userinput" ||
                   cmd == "setreadpio" || cmd == "setreaddma" || cmd == "reopendisk" ||
                   cmd == "hex" || cmd == "decimal") {
            // recognized original commands; no-op in this port
        } else {
            out_ += "skip unsupported command: " + cmd + "\n";
        }
        (void)name;
        (void)i;
    }
    return 0;
}

}  // namespace hsc
