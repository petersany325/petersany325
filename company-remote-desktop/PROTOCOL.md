# Company Remote Desktop — wire protocol (v2)

Company-hosted **Hub** (rendezvous + full TCP relay). Agents and Viewers connect to the Hub. LAN IP of the training PC is not required. No TLS in this MVP. Hole-punching is a follow-up; the Hub relays the session.

Little-endian TCP frames, one viewer per agent.

## Framing

| Field | Size | Notes |
| --- | --- | --- |
| `payload_size` | `uint32le` | Bytes that follow (`type` + payload). Max 12 MiB. |
| `type` | `uint8` | See table. |
| `payload` | `payload_size - 1` | Type-specific. |

## Roles and IDs

- **Agent ID**: 9 digits, shown as `123 456 789`. Issued by the Hub on first registration.
- **Device secret**: 32 random bytes. Hub stores it; Agent persists it. Used only for Agent↔Hub login.
- **Access password**: set on the Agent. Viewers prove it with SHA-256. Hub never sees the password.

```
device_login = SHA-256( nonce[16] || device_secret[32] )
access_digest = SHA-256( nonce[16] || UTF-8(password) )
```

## Message types

| Type | ID | Direction | Payload |
| --- | --- | --- | --- |
| `HELLO_CLIENT` | `0x01` | legacy LAN viewer→host | `u16 ver`, `u16 flags` |
| `HELLO_SERVER` | `0x02` | agent→viewer (relayed) | `u16 ver`, `u16 flags`, `u32 w`, `u32 h` |
| `AUTH_CHALLENGE` | `0x03` | hub→agent or agent→viewer | `u8 nonce[16]` |
| `AUTH_RESPONSE` | `0x04` | viewer→agent (relayed) | `u8 sha256[32]` |
| `AUTH_RESULT` | `0x05` | hub or agent | `u8 status` |
| `FRAME` | `0x10` | agent→viewer | JPEG frame (same as v1) |
| `MOUSE` | `0x20` | viewer→agent | `u8 flags`, `i16 x`, `i16 y`, `i16 wheel` |
| `KEY` | `0x21` | viewer→agent | `u16 vk`, `u8 down`, `u8 ext` |
| `HEARTBEAT` | `0x30` | agent↔hub, either side in session | `u32 tick_ms` |
| `DISCONNECT` | `0x31` | either | `u8 reason` |
| `ROLE_HELLO` | `0x40` | agent or viewer → hub | `u16 ver`, `u8 role`, `u8 id_len`, `id[]` |
| `ASSIGN_ID` | `0x41` | hub→agent | `u8 id_len`, `id[]`, `u8 secret[32]` |
| `AGENT_LOGIN` | `0x42` | agent→hub | `u8 device_login[32]` |
| `SESSION_INCOMING` | `0x43` | hub→agent | `u8 reserved` |
| `AGENT_READY` | `0x44` | agent→hub | `u32 w`, `u32 h` |

`role`: `1` = Agent, `2` = Viewer. `version` must be `2`.

`AUTH_RESULT.status`: `0` ok, `1` bad password, `2` busy, `3` protocol, `4` offline, `5` unknown ID.

## Agent register (first run)

```
agent                         hub
  |-- ROLE_HELLO (role=agent, id empty) -->
  |<-- ASSIGN_ID (id + device secret) -----
  |   persist id + secret locally
  |-- HEARTBEAT / wait for SESSION_INCOMING
```

## Agent login (later starts)

```
  |-- ROLE_HELLO (role=agent, saved id) -->
  |<-- AUTH_CHALLENGE ---------------------
  |-- AGENT_LOGIN (SHA-256(nonce||secret))
  |<-- AUTH_RESULT ok ---------------------
```

## Viewer session by ID (relay)

```
viewer                        hub                         agent
  |-- ROLE_HELLO (role=viewer, target id) ->|
  |                                         |-- SESSION_INCOMING ------->|
  |<-- AUTH_CHALLENGE (relayed) ------------|<-- AUTH_CHALLENGE ---------|
  |-- AUTH_RESPONSE (access digest) ------->|-- AUTH_RESPONSE ---------->|
  |<-- AUTH_RESULT -------------------------|<-- AUTH_RESULT ------------|
  |<-- HELLO_SERVER ------------------------|<-- HELLO_SERVER -----------|
  |<-- FRAME -------------------------------|<-- FRAME ------------------|
  |-- MOUSE / KEY ------------------------->|-- MOUSE / KEY ------------>|
```

If the ID is unknown/offline/busy the Hub replies `AUTH_RESULT` immediately and does not start a relay.

## Frames and input

Unchanged from v1: JPEG `codec=1`, mouse in streamed-frame pixels, Windows VK codes. See the v1 notes in git history for bit flags.

## Persistence

Hub writes `hub-state.db` (table file, `CRDH1` header): `id`, device secret, created, last_seen. Heartbeats update last_seen. An ID is **online** only while the Agent TCP connection is up.

## Follow-ups

- TLS around the same messages
- UDP/TCP hole-punching so media can skip the Hub
- H.264 (`codec=2`), IPv6, clipboard, multi-monitor, AD SSO
