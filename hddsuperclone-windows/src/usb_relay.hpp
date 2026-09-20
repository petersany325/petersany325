#pragma once

#include <cstdint>
#include <string>
#include <vector>

namespace hsc {

struct RelayInfo {
    std::string path;
    std::string name;
    uint16_t vid = 0;
    uint16_t pid = 0;
    int channels = 8;
};

// dcttech / "cheap red" USB HID relay (VID 0x16C0 PID 0x05DF) report bytes.
void encode_dcttech_set(uint8_t out[8], int channel /*1-8, 0=all*/, bool on);

std::vector<RelayInfo> enumerate_relays();
bool relay_set(const std::string& path, int channel, bool on, std::string& error);
bool relay_power_cycle(const std::string& path, int channel, int off_ms, int on_ms, std::string& error);

}  // namespace hsc
