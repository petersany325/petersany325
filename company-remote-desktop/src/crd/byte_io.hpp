#pragma once

#include <cstdint>
#include <cstring>
#include <string>
#include <vector>

namespace crd {

inline void write_u8(std::vector<std::uint8_t>& out, std::uint8_t v) { out.push_back(v); }

inline void write_u16le(std::vector<std::uint8_t>& out, std::uint16_t v) {
    out.push_back(static_cast<std::uint8_t>(v));
    out.push_back(static_cast<std::uint8_t>(v >> 8));
}

inline void write_u32le(std::vector<std::uint8_t>& out, std::uint32_t v) {
    out.push_back(static_cast<std::uint8_t>(v));
    out.push_back(static_cast<std::uint8_t>(v >> 8));
    out.push_back(static_cast<std::uint8_t>(v >> 16));
    out.push_back(static_cast<std::uint8_t>(v >> 24));
}

inline void write_i16le(std::vector<std::uint8_t>& out, std::int16_t v) {
    write_u16le(out, static_cast<std::uint16_t>(v));
}

inline void write_bytes(std::vector<std::uint8_t>& out, const void* data, std::size_t n) {
    const auto* p = static_cast<const std::uint8_t*>(data);
    out.insert(out.end(), p, p + n);
}

class ByteReader {
public:
    ByteReader(const std::uint8_t* data, std::size_t size) : data_(data), size_(size) {}
    explicit ByteReader(const std::vector<std::uint8_t>& buf) : data_(buf.data()), size_(buf.size()) {}

    bool u8(std::uint8_t& v) {
        if (pos_ + 1 > size_) {
            return false;
        }
        v = data_[pos_++];
        return true;
    }

    bool u16le(std::uint16_t& v) {
        if (pos_ + 2 > size_) {
            return false;
        }
        v = static_cast<std::uint16_t>(data_[pos_] | (data_[pos_ + 1] << 8));
        pos_ += 2;
        return true;
    }

    bool u32le(std::uint32_t& v) {
        if (pos_ + 4 > size_) {
            return false;
        }
        v = static_cast<std::uint32_t>(data_[pos_]) | (static_cast<std::uint32_t>(data_[pos_ + 1]) << 8) |
            (static_cast<std::uint32_t>(data_[pos_ + 2]) << 16) | (static_cast<std::uint32_t>(data_[pos_ + 3]) << 24);
        pos_ += 4;
        return true;
    }

    bool i16le(std::int16_t& v) {
        std::uint16_t u = 0;
        if (!u16le(u)) {
            return false;
        }
        v = static_cast<std::int16_t>(u);
        return true;
    }

    bool bytes(void* dest, std::size_t n) {
        if (pos_ + n > size_) {
            return false;
        }
        std::memcpy(dest, data_ + pos_, n);
        pos_ += n;
        return true;
    }

    bool skip(std::size_t n) {
        if (pos_ + n > size_) {
            return false;
        }
        pos_ += n;
        return true;
    }

    const std::uint8_t* remaining_data() const { return data_ + pos_; }
    std::size_t remaining() const { return size_ - pos_; }
    bool empty() const { return pos_ >= size_; }

private:
    const std::uint8_t* data_ = nullptr;
    std::size_t size_ = 0;
    std::size_t pos_ = 0;
};

} // namespace crd
