# Company Remote Desktop — wire protocol (v1)

LAN TCP, little-endian, no TLS in this MVP. One viewer per host.

## Framing

Every message is:

| Field | Size | Notes |
| --- | --- | --- |
| `payload_size` | `uint32le` | Bytes that follow (`type` + payload). Max 12 MiB. |
| `type` | `uint8` | See table below. |
| `payload` | `payload_size - 1` | Type-specific. |

`payload_size` is never zero.

## Message types

| Type | ID | Direction | Payload |
| --- | --- | --- | --- |
| `HELLO_CLIENT` | `0x01` | viewer → host | `u16 version`, `u16 flags` |
| `HELLO_SERVER` | `0x02` | host → viewer | `u16 version`, `u16 flags`, `u32 width`, `u32 height` |
| `AUTH_CHALLENGE` | `0x03` | host → viewer | `u8 nonce[16]` |
| `AUTH_RESPONSE` | `0x04` | viewer → host | `u8 sha256[32]` |
| `AUTH_RESULT` | `0x05` | host → viewer | `u8 status` |
| `FRAME` | `0x10` | host → viewer | `u32 w`, `u32 h`, `u32 seq`, `u8 codec`, `u32 nbytes`, `u8 jpeg[nbytes]` |
| `MOUSE` | `0x20` | viewer → host | `u8 flags`, `i16 x`, `i16 y`, `i16 wheel` |
| `KEY` | `0x21` | viewer → host | `u16 vk`, `u8 down`, `u8 extended` |
| `HEARTBEAT` | `0x30` | either | `u32 tick_ms` |
| `DISCONNECT` | `0x31` | either | `u8 reason` |

`version` must be `1`. `flags` are reserved (send `0`).

`HELLO_SERVER` width/height are the streamed frame size (after optional `--scale`).

## Auth (shared secret)

Password is **not** sent on the wire.

```
digest = SHA-256( nonce[16] || UTF-8(password) )
```

`AUTH_RESULT.status`:

| Value | Meaning |
| --- | --- |
| `0` | OK — host begins streaming |
| `1` | Bad password |
| `2` | Busy (another viewer is connected) |
| `3` | Protocol mismatch |

A second TCP client while a session is live is closed after `AUTH_RESULT=Busy` and `DISCONNECT`.

## Session sequence

```
viewer                          host
   |-- HELLO_CLIENT ----------->|
   |<-- HELLO_SERVER -----------|
   |<-- AUTH_CHALLENGE ---------|
   |-- AUTH_RESPONSE ---------->|
   |<-- AUTH_RESULT ------------|
   |<-- FRAME / HEARTBEAT ------|
   |-- MOUSE / KEY / HEARTBEAT >|
   |-- DISCONNECT ------------->|
```

## Frames

`codec = 1` is JPEG (WIC on Windows). `seq` increases per sent frame.

Mouse `x,y` are in **frame pixels** (same space as `FRAME` / `HELLO_SERVER`). The host maps them onto the real primary desktop before `SendInput`.

Mouse `flags` bits:

| Bit | Name |
| --- | --- |
| 0 | Move |
| 1 | LeftDown |
| 2 | LeftUp |
| 3 | RightDown |
| 4 | RightUp |
| 5 | MiddleDown |
| 6 | MiddleUp |
| 7 | Wheel (`wheel` is Win32 wheel delta, typically ±120) |

`KEY.vk` is a Windows virtual-key code. `extended` is 1 for extended keys (arrows, right Ctrl, etc.).

`DISCONNECT.reason`: `0` user, `1` error, `2` busy, `3` shutdown.

## Heartbeat

The host sends `HEARTBEAT` about every 2 seconds. The viewer may send them as well. There is no mandatory timeout in MVP; a dropped TCP connection ends the session.

## Follow-ups (not in MVP)

- TLS 1.2+ (or QUIC) around the same messages
- H.264 (`codec = 2`) instead of JPEG
- IPv6, multi-monitor, clipboard, file transfer, AD SSO
