#include "crd/byte_io.hpp"
#include "crd/protocol.hpp"
#include "crd/sha256.hpp"
#include "crd/util.hpp"

#include <cstdio>
#include <cstring>
#include <string>

namespace {

int g_failed = 0;

void expect(bool cond, const char* name) {
    if (cond) {
        std::printf("  PASS  %s\n", name);
    } else {
        std::printf("  FAIL  %s\n", name);
        ++g_failed;
    }
}

} // namespace

int main() {
    std::printf("test_protocol\n");

    const char abc[] = "abc";
    const std::string hex = crd::sha256_hex(reinterpret_cast<const std::uint8_t*>(abc), 3);
    expect(hex == "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad", "sha256(abc)");

    std::uint8_t nonce[16]{};
    for (int i = 0; i < 16; ++i) {
        nonce[i] = static_cast<std::uint8_t>(i + 1);
    }
    std::uint8_t d1[32], d2[32];
    crd::auth_digest(nonce, "secret", d1);
    crd::auth_digest(nonce, "secret", d2);
    expect(std::memcmp(d1, d2, 32) == 0, "auth digest stable");
    crd::auth_digest(nonce, "other", d2);
    expect(std::memcmp(d1, d2, 32) != 0, "auth digest changes with password");

    {
        crd::HelloClient in{1, 7}, out{};
        std::vector<std::uint8_t> p;
        crd::encode_hello_client(p, in);
        expect(crd::decode_hello_client(p, out) && out.proto_version == 1 && out.flags == 7, "hello client");
    }
    {
        crd::HelloServer in{};
        in.proto_version = 1;
        in.desktop_width = 1920;
        in.desktop_height = 1080;
        crd::HelloServer out{};
        std::vector<std::uint8_t> p;
        crd::encode_hello_server(p, in);
        expect(crd::decode_hello_server(p, out) && out.desktop_width == 1920 && out.desktop_height == 1080,
               "hello server");
    }
    {
        crd::AuthChallenge in{}, out{};
        for (int i = 0; i < 16; ++i) {
            in.nonce[i] = static_cast<std::uint8_t>(0xA0 + i);
        }
        std::vector<std::uint8_t> p;
        crd::encode_auth_challenge(p, in);
        expect(crd::decode_auth_challenge(p, out) && std::memcmp(in.nonce, out.nonce, 16) == 0, "auth challenge");
    }
    {
        crd::FrameMsg in{};
        in.width = 64;
        in.height = 48;
        in.seq = 9;
        in.codec = crd::Codec::Jpeg;
        in.bytes = {0xFF, 0xD8, 0xFF, 0xD9};
        crd::FrameMsg out{};
        std::vector<std::uint8_t> p;
        crd::encode_frame(p, in);
        expect(crd::decode_frame(p, out) && out.seq == 9 && out.bytes == in.bytes && out.width == 64, "frame jpeg");
    }
    {
        crd::MouseEvent in{0x03, 100, 200, -120}, out{};
        std::vector<std::uint8_t> p;
        crd::encode_mouse(p, in);
        expect(crd::decode_mouse(p, out) && out.x == 100 && out.y == 200 && out.wheel == -120 && out.flags == 0x03,
               "mouse");
    }
    {
        crd::KeyEvent in{0x41, 1, 0}, out{};
        std::vector<std::uint8_t> p;
        crd::encode_key(p, in);
        expect(crd::decode_key(p, out) && out.vk == 0x41 && out.down == 1, "key");
    }
    {
        std::vector<std::uint8_t> bad{1, 2, 3};
        crd::HelloClient hc{};
        expect(!crd::decode_hello_client(bad, hc), "reject truncated hello");
    }
    {
        std::vector<std::uint8_t> p;
        crd::write_u32le(p, 0x01020304);
        crd::ByteReader r(p);
        std::uint32_t v = 0;
        expect(r.u32le(v) && v == 0x01020304 && r.empty(), "u32le endian");
    }
    {
        crd::RoleHello in{};
        in.proto_version = 2;
        in.role = crd::PeerRole::Viewer;
        in.id = "123456789";
        crd::RoleHello out{};
        std::vector<std::uint8_t> p;
        expect(crd::encode_role_hello(p, in) && crd::decode_role_hello(p, out) && out.id == "123456789" &&
                   out.role == crd::PeerRole::Viewer,
               "role hello viewer id");
    }
    expect(crd::normalize_id("123 456 789") == "123456789", "normalize id");
    expect(crd::format_id("123456789") == "123 456 789", "format id");

    if (g_failed) {
        std::printf("%d test(s) failed\n", g_failed);
        return 1;
    }
    std::printf("all protocol tests passed\n");
    return 0;
}
