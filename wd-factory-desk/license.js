/**
 * Factory Desk — License / Activation (prototype)
 *
 * Sales model:
 *   1) Customer installs → sees Machine ID
 *   2) Customer pays → seller issues serial (or machine-bound license)
 *   3) Serial activates → signed license blob stored locally, bound to Machine ID
 *
 * IMPORTANT: Browser JS cannot be made uncrackable. This prototype proves the UX
 * and the cryptographic *shape*. The production Win x64 build must:
 *   - verify Ed25519 signatures with the public key embedded in native code
 *   - keep the private key ONLY on the seller machine / activation server
 *   - re-check license before privileged I/O (driver / PassThrough path)
 *   - use a multi-factor hardware fingerprint (not one registry value)
 * See LICENSE-SYSTEM.md
 */

const License = (() => {
  const STORE_KEY = "wdfd.license.v1";
  const PRODUCT = "WDFD";
  const EDITIONS = {
    STD: { name: "Standard", seats: 1 },
    PRO: { name: "Professional", seats: 1 },
    LAB: { name: "Lab / Multi-port", seats: 4 }
  };

  /**
   * Prototype HMAC secret — REPLACE in native build with Ed25519.
   * Obfuscated split so a casual string search is less trivial in the HTML demo.
   * Still bypassable in JS; real security is native + offline private key.
   */
  const _p = [
    0x57, 0x44, 0x46, 0x44, 0x2d, 0x50, 0x52, 0x4f, 0x54, 0x4f, 0x2d,
    0x4b, 0x33, 0x79, 0x2d, 0x32, 0x30, 0x32, 0x36, 0x2d, 0x78, 0x39,
    0x51, 0x6d, 0x4e, 0x37, 0x76, 0x4c, 0x70, 0x32, 0x21, 0x73
  ];
  function vendorMaterial() {
    return new TextEncoder().encode(String.fromCharCode(..._p));
  }

  async function hmacHex(keyBytes, msg) {
    const key = await crypto.subtle.importKey(
      "raw", keyBytes, { name: "HMAC", hash: "SHA-256" }, false, ["sign"]
    );
    const sig = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(msg));
    return [...new Uint8Array(sig)].map((b) => b.toString(16).padStart(2, "0")).join("");
  }

  async function sha256Hex(text) {
    const buf = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(text));
    return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
  }

  /** Stable-ish machine fingerprint for the prototype (browser). Native: SMBIOS + disk + MachineGuid. */
  async function machineId() {
    const parts = [
      navigator.userAgent || "",
      navigator.language || "",
      String(screen.width) + "x" + String(screen.height) + "x" + String(screen.colorDepth),
      String(navigator.hardwareConcurrency || 0),
      Intl.DateTimeFormat().resolvedOptions().timeZone || "",
      localStorage.getItem("wdfd.mid.seed") || seedMachine()
    ];
    const h = await sha256Hex(parts.join("|"));
    // Format: WDFD-M-XXXX-XXXX-XXXX-XXXX
    const body = h.slice(0, 16).toUpperCase();
    return `WDFD-M-${body.slice(0, 4)}-${body.slice(4, 8)}-${body.slice(8, 12)}-${body.slice(12, 16)}`;
  }

  function seedMachine() {
    const s = crypto.getRandomValues(new Uint8Array(16));
    const hex = [...s].map((b) => b.toString(16).padStart(2, "0")).join("");
    localStorage.setItem("wdfd.mid.seed", hex);
    return hex;
  }

  function normalizeSerial(raw) {
    return String(raw || "").toUpperCase().replace(/[^A-Z0-9]/g, "");
  }

  function formatSerial(norm) {
    // WDFD + edition(3) + meta(8) + mac(8) + sig(8)  ≈ 31 chars without product prefix packing
    const s = norm.startsWith(PRODUCT) ? norm : PRODUCT + norm;
    const chunks = s.match(/.{1,4}/g) || [];
    return chunks.join("-");
  }

  /**
   * Serial payload (seller-issued):
   *   PRODUCT(4) + EDITION(3) + EXP_YYMM(4) + SERIAL_ID(4) + BIND_FLAG(1) + SIG(8)
   * BIND_FLAG: 0 = open seat (binds on first activate), 1 = pre-bound (next 8 nibbles = machine hash fragment — not in open serial)
   *
   * Open serial length after normalize: 4+3+4+4+1+8 = 24 hex-ish alnum → we use base36-ish A-Z0-9
   */
  async function issueSerial({ edition = "PRO", daysValid = 365, serialId = null } = {}) {
    const ed = (edition || "PRO").toUpperCase().slice(0, 3).padEnd(3, "X");
    if (!EDITIONS[ed]) throw new Error("Unknown edition");
    const exp = expiryCode(daysValid);
    const sid = (serialId || randomId(4)).toUpperCase();
    const core = `${PRODUCT}${ed}${exp}${sid}0`;
    const sig = (await hmacHex(vendorMaterial(), `SERIAL|${core}`)).slice(0, 8).toUpperCase();
    return formatSerial(core + sig);
  }

  /** Machine-bound license key (preferred for one-PC sales): includes machine fragment. */
  async function issueBoundLicense({ machineId: mid, edition = "PRO", daysValid = 365, serialId = null } = {}) {
    const ed = (edition || "PRO").toUpperCase().slice(0, 3).padEnd(3, "X");
    if (!EDITIONS[ed]) throw new Error("Unknown edition");
    const exp = expiryCode(daysValid);
    const sid = (serialId || randomId(4)).toUpperCase();
    const mfrag = (await sha256Hex(mid)).slice(0, 8).toUpperCase();
    const core = `${PRODUCT}${ed}${exp}${sid}1${mfrag}`;
    const sig = (await hmacHex(vendorMaterial(), `SERIAL|${core}`)).slice(0, 8).toUpperCase();
    return formatSerial(core + sig);
  }

  function expiryCode(daysValid) {
    const d = new Date();
    d.setUTCDate(d.getUTCDate() + Math.max(1, daysValid | 0));
    const yy = String(d.getUTCFullYear()).slice(2);
    const mm = String(d.getUTCMonth() + 1).padStart(2, "0");
    return `${yy}${mm}`;
  }

  function parseExpiry(code) {
    const yy = 2000 + parseInt(code.slice(0, 2), 10);
    const mm = parseInt(code.slice(2, 4), 10);
    if (!yy || !mm || mm < 1 || mm > 12) return null;
    return new Date(Date.UTC(yy, mm, 0, 23, 59, 59)); // end of month
  }

  function randomId(n) {
    const alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    const bytes = crypto.getRandomValues(new Uint8Array(n));
    return [...bytes].map((b) => alphabet[b % alphabet.length]).join("");
  }

  async function parseAndVerifySerial(raw) {
    const norm = normalizeSerial(raw);
    if (!norm.startsWith(PRODUCT)) return { ok: false, error: "Invalid product prefix" };

    // Open: 4+3+4+4+1+8 = 24
    // Bound: 4+3+4+4+1+8+8 = 32
    const body = norm.slice(0, -8);
    const sig = norm.slice(-8);
    const expect = (await hmacHex(vendorMaterial(), `SERIAL|${body}`)).slice(0, 8).toUpperCase();
    if (sig !== expect) return { ok: false, error: "Serial signature invalid" };

    const edition = body.slice(4, 7);
    const expCode = body.slice(7, 11);
    const serialId = body.slice(11, 15);
    const bindFlag = body.slice(15, 16);
    const mfrag = bindFlag === "1" ? body.slice(16, 24) : null;

    if (!EDITIONS[edition]) return { ok: false, error: "Unknown edition" };
    const expiresAt = parseExpiry(expCode);
    if (!expiresAt) return { ok: false, error: "Bad expiry" };
    if (expiresAt.getTime() < Date.now()) return { ok: false, error: "Serial expired" };

    return {
      ok: true,
      edition,
      editionName: EDITIONS[edition].name,
      serialId,
      bindFlag,
      mfrag,
      expiresAt,
      normalized: norm
    };
  }

  async function activate(rawSerial) {
    const mid = await machineId();
    const parsed = await parseAndVerifySerial(rawSerial);
    if (!parsed.ok) return parsed;

    if (parsed.bindFlag === "1") {
      const localFrag = (await sha256Hex(mid)).slice(0, 8).toUpperCase();
      if (parsed.mfrag !== localFrag) {
        return { ok: false, error: "Serial is bound to another machine" };
      }
    }

    const issuedAt = new Date().toISOString();
    const payload = {
      v: 1,
      product: PRODUCT,
      edition: parsed.edition,
      serialId: parsed.serialId,
      machineId: mid,
      issuedAt,
      expiresAt: parsed.expiresAt.toISOString(),
      serialNorm: parsed.normalized
    };
    const canon = canonicalize(payload);
    const seal = await hmacHex(vendorMaterial(), `LICENSE|${canon}`);
    const record = { ...payload, seal };
    localStorage.setItem(STORE_KEY, JSON.stringify(record));
    return { ok: true, license: record, editionName: parsed.editionName };
  }

  function canonicalize(p) {
    return [
      p.v, p.product, p.edition, p.serialId, p.machineId,
      p.issuedAt, p.expiresAt, p.serialNorm
    ].join("|");
  }

  async function loadLicense() {
    const raw = localStorage.getItem(STORE_KEY);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  async function validateStored() {
    const lic = await loadLicense();
    if (!lic) return { ok: false, reason: "not_activated", message: "Not activated" };

    const mid = await machineId();
    if (lic.machineId !== mid) {
      return { ok: false, reason: "machine_mismatch", message: "License bound to another PC" };
    }

    const canon = canonicalize(lic);
    const seal = await hmacHex(vendorMaterial(), `LICENSE|${canon}`);
    if (seal !== lic.seal) {
      return { ok: false, reason: "tamper", message: "License tampered or corrupt" };
    }

    if (new Date(lic.expiresAt).getTime() < Date.now()) {
      return { ok: false, reason: "expired", message: "License expired" };
    }

    if (!EDITIONS[lic.edition]) {
      return { ok: false, reason: "edition", message: "Unknown edition" };
    }

    // Heartbeat token — changes every hour; UI/tools can require recent check
    const beat = await hmacHex(vendorMaterial(), `BEAT|${mid}|${lic.serialId}|${hourBucket()}`);

    return {
      ok: true,
      license: lic,
      editionName: EDITIONS[lic.edition].name,
      beat,
      daysLeft: Math.max(0, Math.ceil((new Date(lic.expiresAt) - Date.now()) / 86400000))
    };
  }

  function hourBucket() {
    return Math.floor(Date.now() / 3600000);
  }

  function deactivate() {
    localStorage.removeItem(STORE_KEY);
  }

  /** Require valid license; returns status. Call before privileged ops. */
  async function requireLicense() {
    return validateStored();
  }

  return {
    EDITIONS,
    machineId,
    issueSerial,
    issueBoundLicense,
    activate,
    validateStored,
    requireLicense,
    deactivate,
    formatSerial,
    normalizeSerial,
    parseAndVerifySerial
  };
})();

if (typeof globalThis !== "undefined") globalThis.License = License;
if (typeof window !== "undefined") window.License = License;
