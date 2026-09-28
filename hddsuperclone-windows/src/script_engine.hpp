#pragma once

#include "disk_io.hpp"

#include <cstdint>
#include <map>
#include <string>
#include <vector>

namespace hsc {

struct ScriptResult {
    int exit_code = 0;
    std::string output;
    bool ok = true;
};

// HDDSuperTool-compatible subset: echo, seti, sets, if/endif, buffersize,
// ata28cmd, ata48cmd, printbuffer, wordflipbuffer, include, sleep, usleep,
// softreset, hardreset, end, exit, comments. Unknown commands are skipped
// with a warning so original scripts can run as far as the port supports.
class ScriptEngine {
public:
    void set_disk(DiskSession* disk) { disk_ = disk; }
    void set_script_dir(std::string dir) { script_dir_ = std::move(dir); }
    ScriptResult run_file(const std::string& path);
    ScriptResult run_text(const std::string& text, const std::string& name = "<memory>");

private:
    int run_lines(const std::vector<std::string>& lines, const std::string& name, int depth);
    int64_t eval_int(const std::string& tok);
    std::string eval_str(const std::string& tok);
    bool cond_true(const std::string& expr);

    DiskSession* disk_ = nullptr;
    std::string script_dir_;
    std::map<std::string, int64_t> ints_;
    std::map<std::string, std::string> strs_;
    std::vector<uint8_t> buffer_;
    std::string out_;
};

std::vector<std::string> list_scripts(const std::string& dir);

}  // namespace hsc
