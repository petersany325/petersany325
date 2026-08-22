# License / Activation System

Commercial lock for **Windex WD** (HamGap) sales: serial activation, machine binding, tamper-resistant license blob.

## Sales flow (recommended)

```
1. Customer installs app
2. Activation screen shows Machine ID  (WXWD-M-XXXX-…)
3. Customer pays + sends Machine ID
4. Seller runs keygen → issues machine-bound key
5. Customer enters key → license sealed to that PC
```

**Why machine-bound keys?** Shared serials stop working on other PCs. Open seat serials are easier to resell/leak; they bind only after first activate.

## What the prototype implements

| Piece | Behavior |
|-------|----------|
| Machine ID | Fingerprint hash (browser stand-in) |
| Serial | HMAC-signed payload: edition + expiry + id [+ machine frag] |
| License blob | Stored locally, sealed with HMAC over machine + serial + dates |
| Re-check | Startup + before privileged-style actions |
| Tamper | Seal mismatch → lock out |
| Keygen | `license-keygen.html` (seller only) |

Files: `license.js`, gate in `index.html`, seller UI `license-keygen.html`.

## Editions

- `STD` — Standard  
- `PRO` — Professional  
- `LAB` — Lab / multi-port  

## Why HTML alone is not enough

Any pure JS lock can be patched in DevTools. The prototype is for **UX + crypto shape**. Hard-to-hack sales protection belongs in the **native Win7/10/11 x64** build.

## Hardening roadmap (native product)

1. **Asymmetric signatures (required)**  
   - Seller holds **Ed25519 private key** (HSM / offline PC only).  
   - App embeds **public key only**.  
   - Licenses are signed blobs; HMAC shared secret must not ship in the customer binary.

2. **Hardware fingerprint (multi-factor)**  
   Combine (hashed, not raw):  
   - Windows `MachineGuid`  
   - System drive volume serial / NVMe serial  
   - SMBIOS UUID / board serial  
   - CPUID leaf mix  
   Allow 1-factor drift for hardware upgrades via seller re-issue.

3. **Check at the privilege boundary**  
   Re-validate license inside the driver / PassThrough service before exclusive disk I/O — not only in the UI process. UI-only checks are trivial to NOP.

4. **Anti-tamper / anti-debug (release builds)**  
   - Integrity check of critical code pages  
   - Debugger / VM detection policy (lab edition may allow VM)  
   - Obfuscate verification paths; never rely on obfuscation alone

5. **Optional online activation**  
   First activate hits your server → short-lived countersignature. Offline grace for shops without net. Rate-limit and revoke stolen seats.

6. **Revocation list**  
   Ship signed revoke list updates (or online check) for leaked keys.

7. **Separate keygen**  
   Never bundle seller private key with the product installer.

## Prototype vs production mapping

| Prototype | Production |
|-----------|------------|
| HMAC with demo material | Ed25519 sign (private) / verify (public) |
| `localStorage` license | `%ProgramData%\FactoryDesk\license.dat` ACL-locked |
| Browser fingerprint | SMBIOS + disk + MachineGuid mix |
| Gate overlay | Win32 modal before main frame |
| JS `requireLicense()` | Native check in I/O service |

## Seller ops

1. Open `license-keygen.html` on a private PC.  
2. Paste customer Machine ID.  
3. Choose edition + months.  
4. **Issue machine-bound key** → send to customer.  
5. Prefer bound keys for single-PC sales.

## Customer ops

1. Launch Windex WD → activation gate.  
2. Copy Machine ID → pay / WhatsApp seller.  
3. Paste serial → Activate.  
4. Help → License shows status / Deactivate (support only).
