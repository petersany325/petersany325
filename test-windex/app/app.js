const FAM25 = [
  "AZTEC_PL","ARIES","BIGBEAR","DENALI","ESPRIT","EUROPA","EVEREST5","EVERESTV","EVEREST","FIREBIRD",
  "HELIOS","HUBBLE","HUBBLELT","JAM_4KV","JAM_4K","JAMAICA5","JAMAICA","MARINER5","MARINER","MARN5_4K",
  "McKinley","MERCURY","FBLITE","PLUTO","VIKING","SATURN","SHASTA_P","SHASTA2D","SHASTA3D","SHASTA",
  "SHREK","SHREKLT","ZEPHYR","INCA","VENUS","BIGBEARH","ARROWHD","HUBLT2","MAKALU","MONSTERM",
  "HUBSTRS","AROWHDLY","BKKB","BLADE","TREVINO","PALMER","PEBBLEB","SPYGLASS"
];

const FAM35 = [
  "ATLANTIS","CYPRESS","DF1_PL","DF2_PL4K","DF2_VENI","DF3_PL4K","DF4_PL4K","DF4PL_RE","DIABLO3D","DIABLO3S",
  "DRACO","DRAGFLY1","DRAGFLY2","DRAGFLY3","DRAGFLY4","DRAGON","MANTI_RE","GEKKO","HULK","JUP_ZUMA",
  "KERMIT","MARS","MANTIS","MANPL_RE","MIDORI","MOJITO","PINCLITE","PINNACLE","SADLE_2D","YOSEMITE",
  "SADLE_BK","SADLE_G6","TRESXLB","SPART_RE","SADL6MR","SUMT_RE","TAH_LTVR","TAHOE_LT","TAHOE_PL","TAHOE_XL",
  "TAHOE2D","TAHOE","TAHXLVR","GIANT","TD2_PMR","TRAILS","TRESSELS","TRESSELB","MOZRT_RE","VULCN_RE",
  "DF4_4KLT","SDB6GM","TRAILXLS","TRESXLS","VIVALDI","KOJN_RE","HULK_RE","TD3_PMR","SPIDER","TD3PMR_RE",
  "MALIBU","REMBRNDT","TRAILXLB","TRESXLB2","APOLLO","REMBRNAE"
];

/** Default Windows project folder (user lab path) */
const PROJECT_ROOT = "D:\\\\test windex";

const FAMILY_FW = {
  ATLANTIS: {
    form: "3.5",
    choices: [
      { label: "701537", epath: `${PROJECT_ROOT}\\\\FW\\\\3.5\\\\1\\\\701537\\\\` },
      { label: "771668", epath: `${PROJECT_ROOT}\\\\FW\\\\3.5\\\\1\\\\771668\\\\` },
      { label: "701590", epath: `${PROJECT_ROOT}\\\\FW\\\\3.5\\\\1\\\\701590\\\\` }
    ],
    flags: { arco_type: 0, tar_file: "0xC8" }
  },
  FIREBIRD: {
    form: "2.5",
    choices: [
      { label: "0353B", epath: `${PROJECT_ROOT}\\\\FW\\\\2.5\\\\10\\\\0353B\\\\` },
      { label: "0379C", epath: `${PROJECT_ROOT}\\\\FW\\\\2.5\\\\10\\\\0379C\\\\` }
    ],
    flags: { arco_type: 0, tar_file: "0x290B" }
  },
  PALMER: {
    form: "2.5",
    choices: [
      { label: "020PP", epath: `${PROJECT_ROOT}\\\\FW\\\\2.5\\\\46\\\\020PP\\\\` },
      { label: "0506B", epath: `${PROJECT_ROOT}\\\\FW\\\\2.5\\\\46\\\\0506B\\\\` }
    ],
    flags: { arco_type: 1, tar_file: "0x2420" }
  }
};

const FAMILY_OPS = [
  {
    id: "backup", label: "Backup ▸", children: [
      { id: "backup-all", label: "Full Backup (ROM+SA+Track)" },
      { id: "backup-rom", label: "Backup ROM (SVROM)" },
      { id: "backup-rom-mod", label: "Backup ROM Modules" },
      { id: "backup-sa", label: "Backup SA Modules" },
      { id: "backup-track", label: "Backup Tracks (svtrack)" },
      { id: "backup-paths", label: "Firmware Paths…" }
    ]
  },
  { id: "cuthead", label: "Cut Head…", desc: "AC_HDDEPOPCTRL" },
  {
    id: "zone", label: "Zone Ops ▸", children: [
      { id: "zone-list", label: "Zone List" },
      { id: "zone-cut", label: "Cut Zone" },
      { id: "zone-del", label: "Del Zone" }
    ]
  },
  {
    id: "atascan", label: "ATA Scan ▸", children: [
      { id: "ata-full", label: "Full Surface Scan" },
      { id: "ata-quick", label: "Quick Scan" },
      { id: "ata-verify", label: "Read Verify Scan" },
      { id: "ata-write", label: "Write + Verify Scan" },
      { id: "ata-range", label: "LBA Range Scan…" },
      { id: "ata-pending", label: "Pending / Current Defects" },
      { id: "ata-to-glist", label: "Add Errors → G-List" },
      { id: "ata-to-plist", label: "Add Errors → P-List" },
      { id: "ata-stop", label: "Stop Scan" },
      { id: "ata-report", label: "Scan Report" }
    ]
  },
  {
    id: "chsscan", label: "CHS Scan ▸", children: [
      { id: "chs-rd", label: "Read CHS Scan (rdchs)" },
      { id: "chs-wr", label: "Write CHS Scan (wrchs)" },
      { id: "chs-rv", label: "Verify CHS (RVCHS)" },
      { id: "chs-raw", label: "RAW CHS Scan" },
      { id: "chs-relo", label: "With Relocation" },
      { id: "chs-norelo", label: "Skip Relocation" },
      { id: "chs-ecc", label: "ECC Scan (eVCHS_ECCSCAN)" },
      { id: "chs-tone-w", label: "Tone Scan Write" },
      { id: "chs-tone-r", label: "Tone Scan Read" },
      { id: "chs-sa-defect", label: "Scan SA Defect → RPList" },
      { id: "chs-head", label: "Per-Head CHS Scan…" },
      { id: "chs-cylrange", label: "Cylinder Range…" },
      { id: "chs-stop", label: "Stop CHS Scan" },
      { id: "chs-report", label: "CHS Report" }
    ]
  },
  {
    id: "plist", label: "P-List ▸", children: [
      { id: "plist-view", label: "View P-List (0x33)" },
      { id: "plist-add", label: "Add Defect" },
      { id: "plist-clear", label: "Clear P-List" }
    ]
  },
  {
    id: "glist", label: "G-List ▸", children: [
      { id: "glist-view", label: "View G-List (0x34)" },
      { id: "glist-gtop", label: "G → P (gtop)" },
      { id: "glist-clear", label: "Clear G-List" }
    ]
  },
  { id: "modules", label: "Modules / DIR", desc: "0x0B / 0x20B" },
  {
    id: "arco", label: "ARCO ▸", children: [
      { id: "arco-full", label: "Full ARCO" },
      { id: "arco-hot", label: "Hot ARCO" },
      { id: "arco-mini", label: "Mini ARCO" }
    ]
  },
  {
    id: "sf", label: "SF Chain ▸", children: [
      { id: "sf-start", label: "Start SF" },
      { id: "sf-clear", label: "Clear SF Log" },
      { id: "sf-recover", label: "Recover SF" }
    ]
  },
  { id: "dcm", label: "DCM / Capacity", desc: "0x47 / tar_file" }
];

const state = {
  mode: "sata",
  selectedPort: null,
  familyForm: null,
  familyName: null,
  fwLabel: null,
  epath: null,
  /** Active FW source for write/repair: pack (ARCO+SF) or backup (original HDD dump) */
  fwSource: "pack",
  /** Project root on this PC */
  projectRoot: PROJECT_ROOT,
  /** Root 1: factory ARCO+SF package tree */
  packRoot: `${PROJECT_ROOT}\\\\FW`,
  /** Root 2: backup of original HDD firmware (ROM/modules/SA/tracks) */
  backupRoot: `${PROJECT_ROOT}\\\\Backup`,
  backupDone: { rom: false, romMod: false, sa: false, track: false },
  tool: null,
  running: false,
  runTimer: null,
  identity: null,
  ports: [
    { id: "sata0", type: "sata", label: "SATA:0", base: "01F0/03F6", vendor: "WDC", model: "WDC WD10JPVX-22JC3T0", serial: "WX21A84K3821", ataFw: "01A01", form: "2.5", family: "PALMER", familyId: "0x29A", locked: false },
    { id: "sata1", type: "sata", label: "SATA:1", base: "F070/F062", vendor: "WDC", model: "WDC WD5000LPVX-08V0TT0", serial: "WX61E93P1102", ataFw: "01A01", form: "2.5", family: "FIREBIRD", familyId: "0xE8", locked: false },
    { id: "sata2", type: "sata", label: "SATA:2", base: "8080/8002", vendor: "—", model: "— empty —", serial: "—", ataFw: "—", form: "—", family: "—", familyId: "—", locked: false, empty: true },
    { id: "com3", type: "terminal", label: "COM3", base: "SIO 115200", vendor: "WDC", model: "Terminal target", serial: "SIO-PENDING", ataFw: "—", form: "—", family: "—", familyId: "—", locked: false }
  ]
};

function sessionBackupDir() {
  const fam = state.familyName || state.identity?.family || "UNKNOWN";
  const sn = state.identity?.serial || currentPort()?.serial || "NOSN";
  return `${state.backupRoot}\\\\${fam}\\\\${sn}\\\\`;
}

function activeFwPath() {
  if (state.fwSource === "backup") return sessionBackupDir();
  return state.epath || `${state.packRoot}\\\\${state.familyName || "?"}\\\\`;
}

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

function log(msg, cls = "") {
  const line = document.createElement("div");
  if (cls) line.className = cls;
  line.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
  const cons = $("#console");
  cons.appendChild(line);
  cons.scrollTop = cons.scrollHeight;
}

function toast(msg) {
  const el = $("#toast");
  el.hidden = false;
  el.textContent = msg;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => { el.hidden = true; }, 2200);
}

function closeMenus() {
  $$(".menu.open, .submenu.open").forEach((el) => el.classList.remove("open"));
}

function setQueue(step, status) {
  const li = $(`.queue [data-step="${step}"]`);
  if (!li) return;
  li.classList.remove("done", "running");
  if (status === "done" || status === "running") li.classList.add(status);
  li.querySelector("em").textContent = status;
}

function familyOpsMenuHtml(form, name, idx) {
  const ops = FAMILY_OPS.map((op) => {
    if (op.children) {
      return `<div class="submenu">
        <button type="button" class="submenu-trigger">${op.label}</button>
        <div class="submenu-panel ops">
          <div class="hint">${name}</div>
          ${op.children.map((c) => `<button type="button" data-fam-op="${c.id}" data-form="${form}" data-name="${name}">${c.label}</button>`).join("")}
        </div>
      </div>`;
    }
    return `<button type="button" data-fam-op="${op.id}" data-form="${form}" data-name="${name}">${op.label}</button>`;
  }).join("");

  return `<div class="submenu">
    <button type="button" class="submenu-trigger">${String(idx + 1).padStart(2, "0")} · ${name} ▸</button>
    <div class="submenu-panel ops">
      <div class="hint">Family tools · extensible</div>
      <button type="button" data-family="${form}" data-name="${name}">Load Family / FW…</button>
      <hr />
      ${ops}
    </div>
  </div>`;
}

function buildMenubar() {
  const fam25 = FAM25.map((n, i) => familyOpsMenuHtml("2.5", n, i)).join("");
  const fam35 = FAM35.map((n, i) => familyOpsMenuHtml("3.5", n, i)).join("");

  $("#menubar").innerHTML = `
    <div class="menu">
      <button type="button" class="menu-trigger">File</button>
      <div class="menu-panel">
        <button type="button" data-action="log">New Session</button>
        <button type="button" data-action="open-settings">License Settings…</button>
        <button type="button" data-action="log">Open Log…</button>
        <button type="button" data-action="log">Save Report…</button>
        <hr /><button type="button" data-action="log">Exit</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu-trigger">Port</button>
      <div class="menu-panel">
        <button type="button" data-action="detect">Detect Controllers</button>
        <button type="button" data-action="add-port">Add Selected Port</button>
        <button type="button" data-action="identify-drive">Identify Drive</button>
        <button type="button" data-action="read-rom4f">Read ROM Module 4F</button>
        <button type="button" data-action="release">Release Port</button>
        <button type="button" data-action="reset-port">Reset Port</button>
        <hr />
        <div class="submenu">
          <button type="button" class="submenu-trigger">Transport ▸</button>
          <div class="submenu-panel">
            <button type="button" data-action="set-sata">SATA PassThrough</button>
            <button type="button" data-action="set-terminal">Terminal (SIO)</button>
          </div>
        </div>
        <button type="button" data-action="log">Install Driver…</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu-trigger">Family</button>
      <div class="menu-panel">
        <button type="button" data-action="family-auto">Auto Detect</button>
        <hr />
        <div class="submenu">
          <button type="button" class="submenu-trigger">2.5 inch ▸</button>
          <div class="submenu-panel scroll">${fam25}</div>
        </div>
        <div class="submenu">
          <button type="button" class="submenu-trigger">3.5 inch ▸</button>
          <div class="submenu-panel scroll">${fam35}</div>
        </div>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu-trigger">Tools</button>
      <div class="menu-panel">
        <div class="menu-label">Active family tools</div>
        <button type="button" data-tool="backup">Backup (first)</button>
        <button type="button" data-tool="backup-paths">Firmware Paths…</button>
        <hr />
        <button type="button" data-tool="cuthead">Cut Head</button>
        <button type="button" data-tool="zone">Zone</button>
        <button type="button" data-tool="atascan">ATA Scan</button>
        <button type="button" data-tool="chsscan">CHS Scan</button>
        <button type="button" data-tool="plist">P-List</button>
        <button type="button" data-tool="glist">G-List</button>
        <button type="button" data-tool="modules">Modules / DIR</button>
        <hr />
        <button type="button" data-tool="arco">ARCO</button>
        <button type="button" data-tool="sf">SF Chain</button>
        <button type="button" data-tool="dcm">DCM / Capacity</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu-trigger">Process</button>
      <div class="menu-panel">
        <button type="button" data-action="run-all">Run ARCO + SF</button>
        <button type="button" data-action="stop">Stop</button>
        <button type="button" data-action="log">Edit PST…</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu-trigger">Help</button>
      <div class="menu-panel">
        <button type="button" data-action="license-status">License…</button>
        <button type="button" data-action="open-settings">License Settings…</button>
        <button type="button" data-action="about">About Windex WD</button>
      </div>
    </div>
  `;
}

function renderPorts() {
  const tree = $("#portTree");
  tree.innerHTML = "";
  const addGroup = (title, items) => {
    const g = document.createElement("li");
    g.className = "port-group";
    g.textContent = title;
    tree.appendChild(g);
    items.forEach((p) => {
      const li = document.createElement("li");
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "port-item" + (state.selectedPort === p.id ? " selected" : "") + (p.locked ? " locked" : "");
      btn.innerHTML = `<i></i><div><strong>${p.label}${p.locked ? " · LOCKED" : ""}</strong><small>${p.base} · ${p.empty ? "no drive" : p.model}</small></div>`;
      btn.addEventListener("click", () => {
        state.selectedPort = p.id;
        renderPorts();
        log(`Selected ${p.label}`, "ok");
      });
      li.appendChild(btn);
      tree.appendChild(li);
    });
  };
  addGroup("Controllers / SATA", state.ports.filter((p) => p.type === "sata"));
  addGroup("Terminal", state.ports.filter((p) => p.type === "terminal"));
}

function currentPort() {
  return state.ports.find((p) => p.id === state.selectedPort) || null;
}

function setIdentifyTrace(lines) {
  const el = $("#identifyTrace");
  if (!el) return;
  el.textContent = Array.isArray(lines) ? lines.join("\n") : String(lines);
}

function paintIdentity() {
  const id = state.identity;
  $("#idVendor").textContent = id?.vendor || "—";
  $("#idFamily").textContent = id?.family || "—";
  $("#idModel").textContent = id?.model || "—";
  $("#idSerial").textContent = id?.serial || "—";
  $("#idAtaFw").textContent = id?.ataFw || "—";
  $("#idRom4f").textContent = id?.rom4f || "—";
  $("#idCtrlFw").textContent = id?.ctrlFw || "—";
  $("#idFamHex").textContent = id?.familyId || "—";
}

function identifyDrive() {
  const p = currentPort();
  if (!p || p.empty) return toast("Select a populated port first");
  if (!p.locked) {
    state.ports.forEach((x) => { x.locked = x.id === p.id; });
    renderPorts();
  }

  log(`Identify on ${p.label}: ATA ID + vscon/vscid`, "ok");
  const vendor = p.vendor || ((p.model || "").includes("WDC") ? "WDC" : "UNKNOWN");
  const identity = {
    vendor,
    family: p.family !== "—" ? p.family : null,
    familyId: p.familyId !== "—" ? p.familyId : null,
    model: p.model,
    serial: p.serial,
    ataFw: p.ataFw || "—",
    rom4f: null,
    ctrlFw: null,
    rom4fPack: null,
    form: p.form !== "—" ? p.form : null,
    port: p.label
  };
  const isWd = vendor === "WDC" || vendor === "WD" || (p.model || "").includes("WDC");
  identity.readyForRom4f = !!(isWd && identity.family && identity.familyId);
  state.identity = identity;
  paintIdentity();

  setIdentifyTrace([
    `[1] Port ${p.label} claimed exclusive`,
    `[2] ATA Identify → Model=${identity.model}  SN=${identity.serial}  FW=${identity.ataFw}`,
    `[3] vscon; vscid → Vendor=${identity.vendor}  Family=${identity.family || "UNKNOWN"}  ID=${identity.familyId || "—"}`,
    isWd ? "[4] Vendor = Western Digital (WDC) ✓" : "[4] Vendor is NOT WD — ROM 4F skipped",
    identity.family ? `[5] Family known (${identity.family}) ✓ → next: Read ROM module 0x4F` : "[5] Family unknown — need Auto/manual Family",
    "",
    "Session fields: Vendor, Family, Model, SN, ATA FW, FamilyID"
  ]);

  if (identity.readyForRom4f) {
    $("#familyTitle").textContent = `${identity.family} · identified`;
    $("#familyMeta").textContent = `${identity.model} · SN ${identity.serial} · next: Read ROM 4F for ARCO/SF FW pack`;
    toast("WD + Family OK → Read ROM 4F");
  } else if (isWd) {
    toast("WD detected — resolve Family first");
  } else {
    toast("Non-WD vendor");
  }
}

function readRom4f() {
  const id = state.identity;
  if (!id) return toast("Run Identify first");
  const isWd = id.vendor === "WDC" || id.vendor === "WD";
  if (!isWd) return toast("ROM 4F only for WD vendor");
  if (!id.family || !id.familyId) return toast("Family must be known before ROM 4F");

  log("ROM image: find ROYL (0x4C594F52) → DIR 0x0B → module 0x4F", "warn");
  const map4f = {
    PALMER: { rom4f: "020PP", ctrl: "01A01", pack: "020PP" },
    FIREBIRD: { rom4f: "0353B", ctrl: "01A01", pack: "0353B" },
    ATLANTIS: { rom4f: "701537", ctrl: "77FBP", pack: "701537" }
  };
  const hit = map4f[id.family] || { rom4f: "UNKNOWN", ctrl: id.ataFw, pack: null };
  id.rom4f = hit.rom4f;
  id.ctrlFw = hit.ctrl;
  id.rom4fPack = hit.pack;
  paintIdentity();
  setIdentifyTrace([
    "[ROM] load ROM.BIN / SA ROM",
    "[ROM] marker ROYL @ directory",
    "[ROM] DIR entry 0x0B",
    `[ROM] module 0x4F → Overlay FW Rev = "${id.rom4f}"`,
    `[ROM] module 0x0D → Ctrl FW Rev   = "${id.ctrlFw}"`,
    `[ROM] Choice FW Rev = ${id.rom4f}`,
    "",
    "Next: Resolve FW Pack → epath for ARCO/SF RPM files"
  ]);
  log(`ROM 4F FW Rev = ${id.rom4f}`, "ok");
  toast(`ROM 4F → ${id.rom4f}`);
}

function resolveFwPack() {
  const id = state.identity;
  if (!id?.rom4f) return toast("Read ROM 4F first");
  if (!id.family) return toast("Family required");

  const ref = FAMILY_FW[id.family];
  let choice = null;
  if (ref) {
    choice = ref.choices.find((c) => id.rom4f.toUpperCase().includes(c.label.toUpperCase())) || ref.choices[0];
  }
  const p = currentPort();
  state.familyForm = (p?.form && p.form !== "—") ? p.form : (ref?.form || (FAM25.includes(id.family) ? "2.5" : "3.5"));
  state.familyName = id.family;
  state.fwLabel = choice?.label || id.rom4fPack || id.rom4f;
  state.epath = choice?.epath || `${PROJECT_ROOT}\\\\FW\\\\${state.familyForm}\\\\??\\\\${state.fwLabel}\\\\`;

  refreshFamilyHeader();
  openTool("backup");
  setIdentifyTrace([
    `Vendor=${id.vendor}  Family=${id.family} (${id.familyId})`,
    `Model=${id.model}`,
    `SN=${id.serial}`,
    `ATA FW=${id.ataFw}`,
    `ROM 4F FW=${id.rom4f}`,
    `Ctrl FW=${id.ctrlFw}`,
    "",
    `Path 1 · Pack (ARCO+SF): ${state.epath}`,
    `Path 2 · Backup original: ${sessionBackupDir()}`,
    `Active source: ${state.fwSource}`,
    `arco_type=${ref?.flags?.arco_type ?? "?"}  tar_file=${ref?.flags?.tar_file ?? "?"}`,
    "",
    "Next: Full Backup (ROM + ROM modules + SA + tracks) before repair"
  ]);
  log(`FW pack for ARCO/SF → ${state.fwLabel}`, "ok");
  toast(`FW pack ${state.fwLabel} · backup next`);
}

function refreshFamilyHeader() {
  $("#transportLabel").textContent = state.mode === "sata" ? "SATA · PassThrough exclusive" : "Terminal · SIO / COM";
  if (!state.familyName) {
    $("#familyTitle").textContent = "Select a Family";
    $("#familyMeta").textContent = "Each family: Backup first → Cut Head, Zone, P/G-List, Modules, ARCO, SF…";
    $("#familyRibbon").hidden = true;
    $("#telemFamily").textContent = "—";
    $("#telemFw").textContent = "—";
    $("#statusLeft").textContent = `Project: ${state.projectRoot || PROJECT_ROOT}`;
    $("#statusRight").textContent = `Pack: ${state.packRoot}  |  Backup: ${state.backupRoot}`;
    return;
  }
  const bk = sessionBackupDir();
  const src = state.fwSource === "backup" ? "BACKUP" : "PACK";
  $("#familyTitle").textContent = `${state.familyName} · ${state.familyForm}"`;
  $("#familyMeta").textContent = `FW ${state.fwLabel || "—"}  ·  src=${src}  ·  pack ${state.epath || "—"}  ·  backup ${bk}`;
  $("#familyRibbon").hidden = false;
  $("#telemFamily").textContent = state.familyName;
  $("#telemFw").textContent = state.fwLabel || "—";
  $("#statusLeft").textContent = `Family ${state.familyName} · backup ${backupSummary()} · active=${src}`;
  $("#statusRight").textContent = `Pack: ${state.epath || state.packRoot}  |  Backup: ${bk}`;
  setQueue("family", "done");
}

function backupSummary() {
  const d = state.backupDone;
  const parts = [
    d.rom ? "ROM✓" : "ROM·",
    d.romMod ? "ROMmod✓" : "ROMmod·",
    d.sa ? "SA✓" : "SA·",
    d.track ? "TRK✓" : "TRK·"
  ];
  return parts.join(" ");
}

function selectFamily(form, name) {
  state.familyForm = form;
  state.familyName = name;
  const ref = FAMILY_FW[name];
  let fw = "generic";
  let path = `${PROJECT_ROOT}\\\\FW\\\\${form}\\\\??\\\\${name}\\\\`;
  if (ref) {
    const opts = ref.choices.map((c, i) => `${i + 1}=${c.label}`).join("  ");
    const pick = window.prompt(`Select FW / PCB for ${name}\n${opts}`, "1");
    const idx = Math.max(0, (parseInt(pick, 10) || 1) - 1);
    const choice = ref.choices[Math.min(idx, ref.choices.length - 1)];
    fw = choice.label;
    path = choice.epath;
    log(`Flags arco_type=${ref.flags.arco_type} tar_file=${ref.flags.tar_file}`, "ok");
  }
  state.fwLabel = fw;
  state.epath = path;
  refreshFamilyHeader();
  openTool("backup");
  log(`Family menu loaded: ${form}" / ${name} / FW ${fw} — backup first`, "warn");
  toast(`${name} · ${fw} · backup first`);
  closeMenus();
}

function openTool(toolId, familyName = state.familyName) {
  if (!guardLicensed()) return;
  if (!state.familyName && familyName) {
    // ensure family context
  }
  if (!state.familyName) {
    toast("Load a family first (Family menu)");
    return;
  }
  state.tool = toolId;
  $("#telemTool").textContent = toolId;
  $$(".rib").forEach((b) => b.classList.toggle("active", b.dataset.tool === toolId ||
    (toolId.startsWith("backup") && b.dataset.tool === "backup") ||
    (toolId.startsWith("plist") && b.dataset.tool === "plist") ||
    (toolId.startsWith("glist") && b.dataset.tool === "glist") ||
    (toolId.startsWith("zone") && b.dataset.tool === "zone") ||
    (toolId.startsWith("ata") && b.dataset.tool === "atascan") ||
    (toolId.startsWith("chs") && b.dataset.tool === "chsscan") ||
    (toolId.startsWith("arco") && b.dataset.tool === "arco") ||
    (toolId.startsWith("sf") && b.dataset.tool === "sf")));

  const ws = $("#toolWorkspace");
  const fam = state.familyName;
  const title = `${fam} · ${toolLabel(toolId)}`;
  setQueue("tool", "running");

  const shells = {
    backup: () => backupHtml(title, toolId),
    "backup-all": () => backupHtml(title, toolId),
    "backup-rom": () => backupHtml(title, toolId),
    "backup-rom-mod": () => backupHtml(title, toolId),
    "backup-sa": () => backupHtml(title, toolId),
    "backup-track": () => backupHtml(title, toolId),
    "backup-paths": () => backupHtml(title, "backup-paths"),
    cuthead: () => `
      <div class="tool-head"><div><h2>${title}</h2><p>kill → AC_HDDEPOPCTRL · per-family head map</p></div>
      <div class="tool-actions"><button class="primary" data-action="exec-cuthead">Depop Head</button></div></div>
      <div class="op-grid">
        <button type="button" data-action="exec-cuthead"><strong>Cut Head 0</strong><span>manual depop</span></button>
        <button type="button" data-action="exec-cuthead"><strong>Cut Head 1</strong><span>manual depop</span></button>
        <button type="button" data-action="exec-cuthead"><strong>Cut Head 2</strong><span>manual depop</span></button>
        <button type="button" data-action="exec-cuthead"><strong>Cut Head 3</strong><span>manual depop</span></button>
      </div>`,
    zone: () => zoneHtml(title),
    "zone-list": () => zoneHtml(title),
    "zone-cut": () => zoneHtml(title),
    "zone-del": () => zoneHtml(title),
    atascan: () => ataScanHtml(title, toolId),
    "ata-full": () => ataScanHtml(title, toolId),
    "ata-quick": () => ataScanHtml(title, toolId),
    "ata-verify": () => ataScanHtml(title, toolId),
    "ata-write": () => ataScanHtml(title, toolId),
    "ata-range": () => ataScanHtml(title, toolId),
    "ata-pending": () => ataScanHtml(title, toolId),
    "ata-to-glist": () => ataScanHtml(title, toolId),
    "ata-to-plist": () => ataScanHtml(title, toolId),
    "ata-stop": () => ataScanHtml(title, toolId),
    "ata-report": () => ataScanHtml(title, toolId),
    chsscan: () => chsScanHtml(title, toolId),
    "chs-rd": () => chsScanHtml(title, toolId),
    "chs-wr": () => chsScanHtml(title, toolId),
    "chs-rv": () => chsScanHtml(title, toolId),
    "chs-raw": () => chsScanHtml(title, toolId),
    "chs-relo": () => chsScanHtml(title, toolId),
    "chs-norelo": () => chsScanHtml(title, toolId),
    "chs-ecc": () => chsScanHtml(title, toolId),
    "chs-tone-w": () => chsScanHtml(title, toolId),
    "chs-tone-r": () => chsScanHtml(title, toolId),
    "chs-sa-defect": () => chsScanHtml(title, toolId),
    "chs-head": () => chsScanHtml(title, toolId),
    "chs-cylrange": () => chsScanHtml(title, toolId),
    "chs-stop": () => chsScanHtml(title, toolId),
    "chs-report": () => chsScanHtml(title, toolId),
    plist: () => plistHtml(title),
    "plist-view": () => plistHtml(title),
    "plist-add": () => plistHtml(title),
    "plist-clear": () => plistHtml(title),
    glist: () => glistHtml(title),
    "glist-view": () => glistHtml(title),
    "glist-gtop": () => glistHtml(title),
    "glist-clear": () => glistHtml(title),
    modules: () => `
      <div class="tool-head"><div><h2>${title}</h2><p>rdflnom / wrflnom · DIR 0x0B / 0x20B</p></div>
      <div class="tool-actions"><button class="primary" data-action="log">Refresh DIR</button></div></div>
      <table class="data-table"><thead><tr><th>FileID</th><th>Role</th><th>Action</th></tr></thead>
      <tbody>
        <tr><td>0x0B</td><td>Flash DIR</td><td>Edit</td></tr>
        <tr><td>0x28</td><td>PST table</td><td>Edit</td></tr>
        <tr><td>0x47</td><td>DCM / config</td><td>Edit</td></tr>
        <tr><td>0xC4</td><td>ARCO overlay</td><td>Load</td></tr>
      </tbody></table>`,
    arco: () => arcoHtml(title),
    "arco-full": () => arcoHtml(title),
    "arco-hot": () => arcoHtml(title),
    "arco-mini": () => arcoHtml(title),
    sf: () => sfHtml(title),
    "sf-start": () => sfHtml(title),
    "sf-clear": () => sfHtml(title),
    "sf-recover": () => sfHtml(title),
    dcm: () => `
      <div class="tool-head"><div><h2>${title}</h2><p>file 0x47 · tar_file capacity select</p></div>
      <div class="tool-actions"><button class="primary" data-action="log">Apply DCM</button></div></div>
      <div class="op-grid">
        <button type="button"><strong>Read DCM</strong><span>Head / Media from 0x47</span></button>
        <button type="button"><strong>Set Capacity</strong><span>XF tar_file…</span></button>
      </div>`
  };

  const key = toolId in shells ? toolId
    : toolId.startsWith("backup") ? "backup"
    : toolId.startsWith("ata") ? "atascan"
    : toolId.startsWith("chs") ? "chsscan"
    : toolId.split("-")[0];
  ws.innerHTML = (shells[toolId] || shells[key] || shells.plist)();
  log(`Opened ${fam} tool: ${toolId}`, "ok");
  setQueue("tool", "done");
  closeMenus();
}

function toolLabel(id) {
  const map = {
    backup: "Backup", "backup-all": "Full Backup", "backup-rom": "Backup ROM",
    "backup-rom-mod": "Backup ROM Modules", "backup-sa": "Backup SA", "backup-track": "Backup Tracks",
    "backup-paths": "Firmware Paths",
    cuthead: "Cut Head", zone: "Zone Ops", atascan: "ATA Scan", chsscan: "CHS Scan",
    plist: "P-List", glist: "G-List",
    modules: "Modules / DIR", arco: "ARCO", sf: "SF Chain", dcm: "DCM / Capacity"
  };
  if (id.startsWith("ata-")) return "ATA Scan";
  if (id.startsWith("chs-")) return "CHS Scan";
  return map[id] || map[id.split("-")[0]] || id;
}

function backupHtml(title, toolId = "backup") {
  const dir = sessionBackupDir();
  const d = state.backupDone;
  const packPath = state.epath || `${state.packRoot}\\\\${state.familyName || "FAMILY"}\\\\`;
  const mark = (ok) => ok ? "done" : "todo";
  return `
    <div class="tool-head">
      <div>
        <h2>${title}</h2>
        <p>SVROM · ROM modules · rdflnom SA · svtrack · before any repair · focus: <b>${toolId}</b></p>
      </div>
      <div class="tool-actions">
        <button class="primary" data-action="backup-all">Full Backup</button>
        <button data-action="backup-rom">ROM</button>
        <button data-action="backup-rom-mod">ROM Mod</button>
        <button data-action="backup-sa">SA</button>
        <button data-action="backup-track">Tracks</button>
      </div>
    </div>

    <section class="paths-panel">
      <h3 class="paths-title">Two firmware roots</h3>
      <div class="paths-grid">
        <label class="path-field">
          <span>1 · ARCO + SF package (factory pack)</span>
          <div class="path-row">
            <input type="text" id="inputPackRoot" value="${escapeAttr(state.packRoot)}" spellcheck="false" />
          </div>
            <em class="path-hint">Resolved pack folder: ${escapeHtml(packPath)}</em>
        </label>
        <label class="path-field">
          <span>2 · Backup original HDD firmware</span>
          <div class="path-row">
            <input type="text" id="inputBackupRoot" value="${escapeAttr(state.backupRoot)}" spellcheck="false" />
          </div>
          <em class="path-hint">Session dump: ${escapeHtml(dir)} · project ${escapeHtml(state.projectRoot || PROJECT_ROOT)}</em>
        </label>
      </div>
      <div class="path-source-row">
        <span>Active FW source for write / repair:</span>
        <label class="radio-inline"><input type="radio" name="fwSource" value="pack" ${state.fwSource === "pack" ? "checked" : ""} /> Pack (ARCO+SF)</label>
        <label class="radio-inline"><input type="radio" name="fwSource" value="backup" ${state.fwSource === "backup" ? "checked" : ""} /> Original backup</label>
        <button type="button" data-action="save-paths">Save Paths</button>
      </div>
    </section>

    <div class="backup-checklist">
      <div class="bk-item ${mark(d.rom)}"><strong>ROM</strong><span>SVROM → ROM.BIN</span><em>${d.rom ? "saved" : "pending"}</em></div>
      <div class="bk-item ${mark(d.romMod)}"><strong>ROM Modules</strong><span>DIR 0x0B · dump mods (4F, 0D, …)</span><em>${d.romMod ? "saved" : "pending"}</em></div>
      <div class="bk-item ${mark(d.sa)}"><strong>SA Modules</strong><span>rdflnom · 0x0B/33/34/47/…</span><em>${d.sa ? "saved" : "pending"}</em></div>
      <div class="bk-item ${mark(d.track)}"><strong>Tracks</strong><span>svtrack → trkNNN.bin</span><em>${d.track ? "saved" : "pending"}</em></div>
    </div>

    <div class="op-grid" style="margin-top:0.75rem">
      <button type="button" data-action="backup-all"><strong>Full Backup First</strong><span>ROM → ROM mod → SA → tracks</span></button>
      <button type="button" data-action="backup-rom"><strong>Backup ROM</strong><span>SVROM / ROM.BIN</span></button>
      <button type="button" data-action="backup-rom-mod"><strong>Backup ROM Modules</strong><span>overlay + ctrl modules</span></button>
      <button type="button" data-action="backup-sa"><strong>Backup SA Modules</strong><span>resident files dump</span></button>
      <button type="button" data-action="backup-track"><strong>Backup Tracks</strong><span>svtrack · SA cylinders</span></button>
      <button type="button" data-action="use-backup-source"><strong>Use Backup as FW Source</strong><span>switch active path → original</span></button>
    </div>

    <pre class="mono-block compact" id="backupTrace" style="margin-top:0.85rem">Target: ${escapeHtml(dir)}
Layout:
  ROM.BIN
  ROM_MOD\\mod_0x4F.bin  mod_0x0D.bin  …
  SA\\sa_0x0B.bin  sa_0x33.bin  sa_0x34.bin  sa_0x47.bin  …
  TRACK\\trk000.bin  trk001.bin  …

Run Full Backup before manual repair.</pre>
  `;
}

function escapeAttr(s) {
  return String(s ?? "").replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
}

function escapeHtml(s) {
  return String(s ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;");
}

function setBackupTrace(lines) {
  const el = $("#backupTrace");
  if (!el) return;
  el.textContent = Array.isArray(lines) ? lines.join("\n") : String(lines);
}

function saveFwPaths() {
  const packEl = $("#inputPackRoot");
  const bakEl = $("#inputBackupRoot");
  if (packEl) state.packRoot = packEl.value.trim() || state.packRoot;
  if (bakEl) state.backupRoot = bakEl.value.trim() || state.backupRoot;
  const src = document.querySelector('input[name="fwSource"]:checked');
  if (src) state.fwSource = src.value;
  if (state.familyName && state.fwSource === "pack" && state.fwLabel) {
    const ref = FAMILY_FW[state.familyName];
    const choice = ref?.choices?.find((c) => c.label === state.fwLabel);
    if (choice) state.epath = choice.epath;
    else state.epath = `${state.packRoot}\\\\${state.familyName}\\\\${state.fwLabel}\\\\`;
  }
  refreshFamilyHeader();
  log(`Paths saved · pack=${state.packRoot} · backup=${state.backupRoot} · source=${state.fwSource}`, "ok");
  toast("Firmware paths saved");
  if (state.tool?.startsWith("backup")) openTool(state.tool);
}

function ensureBackupContext() {
  if (!state.familyName) {
    const id = state.identity;
    const p = currentPort();
    if (id?.family) {
      state.familyName = id.family;
      state.familyForm = id.form || (FAM25.includes(id.family) ? "2.5" : "3.5");
      state.fwLabel = state.fwLabel || id.rom4f || id.ataFw;
      refreshFamilyHeader();
    } else if (p && !p.empty && p.family !== "—") {
      state.familyName = p.family;
      state.familyForm = p.form;
      state.fwLabel = p.ataFw;
      refreshFamilyHeader();
    } else {
      toast("Identify / load family first");
      return false;
    }
  }
  if (!state.ports.some((x) => x.locked)) {
    const p = currentPort();
    if (p && !p.empty) {
      state.ports.forEach((x) => { x.locked = x.id === p.id; });
      renderPorts();
    } else {
      toast("Lock a port first");
      return false;
    }
  }
  return true;
}

function runBackupStep(kind) {
  if (!guardLicensed()) return;
  if (!ensureBackupContext()) return;
  const dir = sessionBackupDir();
  openTool(kind === "all" ? "backup-all" : `backup-${kind === "rom-mod" ? "rom-mod" : kind}`);

  const steps = {
    rom: () => {
      log(`SVROM → ${dir}ROM.BIN`, "warn");
      setBackupTrace([
        `[ROM] vscon`,
        `[ROM] SVROM → ${dir}ROM.BIN`,
        `[ROM] verify ROYL marker 0x4C594F52`,
        "OK · ROM image saved"
      ]);
      state.backupDone.rom = true;
      toast("ROM backed up");
    },
    "rom-mod": () => {
      log(`ROM modules → ${dir}ROM_MOD\\`, "warn");
      setBackupTrace([
        "[ROMmod] DIR 0x0B / dirrom",
        `[ROMmod] dump module 0x4F → ${dir}ROM_MOD\\mod_0x4F.bin`,
        `[ROMmod] dump module 0x0D → ${dir}ROM_MOD\\mod_0x0D.bin`,
        "[ROMmod] dump remaining ROM-resident entries",
        "OK · ROM modules saved"
      ]);
      state.backupDone.romMod = true;
      toast("ROM modules backed up");
    },
    sa: () => {
      log(`SA modules rdflnom → ${dir}SA\\`, "warn");
      setBackupTrace([
        "[SA] rdflnom 0x0B  → SA\\sa_0x0B.bin  (DIR)",
        "[SA] rdflnom 0x33  → SA\\sa_0x33.bin  (P-List)",
        "[SA] rdflnom 0x34  → SA\\sa_0x34.bin  (G-List)",
        "[SA] rdflnom 0x47  → SA\\sa_0x47.bin  (DCM)",
        "[SA] rdflnom 0x28 / 0x02 / SF logs …",
        "OK · SA modules saved"
      ]);
      state.backupDone.sa = true;
      toast("SA modules backed up");
    },
    track: () => {
      log(`svtrack → ${dir}TRACK\\`, "warn");
      setBackupTrace([
        "[TRK] enumerate SA / critical cylinders",
        `[TRK] svtrack → ${dir}TRACK\\trk000.bin …`,
        "[TRK] map written to TRACK\\trackmap.txt",
        "OK · tracks saved"
      ]);
      state.backupDone.track = true;
      toast("Tracks backed up");
    }
  };

  if (kind === "all") {
    state.running = true;
    setQueue("run", "running");
    log(`Full backup → ${dir}`, "warn");
    const order = ["rom", "rom-mod", "sa", "track"];
    let i = 0;
    let prog = 0;
    clearInterval(state.runTimer);
    state.runTimer = setInterval(() => {
      if (!state.running) return;
      if (i < order.length) {
        steps[order[i]]();
        i += 1;
        prog = (i / order.length) * 100;
        $("#progressBar").style.width = `${prog}%`;
        $("#telemProg").textContent = `${prog.toFixed(1)}%`;
        $("#telemTool").textContent = `backup-${order[i - 1]}`;
        refreshFamilyHeader();
        openTool("backup-all");
      } else {
        clearInterval(state.runTimer);
        state.running = false;
        setQueue("run", "done");
        log("Full backup complete · original HDD FW archived", "ok");
        toast("Full backup done");
        setBackupTrace([
          `Full backup complete → ${dir}`,
          "ROM.BIN ✓",
          "ROM_MOD\\ ✓",
          "SA\\ ✓",
          "TRACK\\ ✓",
          "",
          "Safe to proceed with manual repair structure (next)."
        ]);
        refreshFamilyHeader();
        openTool("backup-all");
      }
    }, 420);
    return;
  }

  if (steps[kind]) {
    steps[kind]();
    refreshFamilyHeader();
    openTool(kind === "rom-mod" ? "backup-rom-mod" : `backup-${kind}`);
  }
}

function plistHtml(title) {
  return `<div class="tool-head"><div><h2>${title}</h2><p>rdflnom 33h · addp / clear</p></div>
    <div class="tool-actions">
      <button class="primary" data-action="log">View</button>
      <button data-action="log">Add</button>
      <button data-action="log">Clear</button>
    </div></div>
    <table class="data-table"><thead><tr><th>Head</th><th>Cyl</th><th>PSN start</th><th>PSN end</th></tr></thead>
    <tbody>
      <tr><td>0</td><td>154220</td><td>0x12A0</td><td>0x12A3</td></tr>
      <tr><td>1</td><td>88102</td><td>0x0A10</td><td>0x0A12</td></tr>
      <tr><td>2</td><td>22011</td><td>0x0440</td><td>0x0441</td></tr>
    </tbody></table>`;
}

function glistHtml(title) {
  return `<div class="tool-head"><div><h2>${title}</h2><p>rdflnom 34h · VG / gtop</p></div>
    <div class="tool-actions">
      <button class="primary" data-action="log">View</button>
      <button data-action="log">G → P</button>
      <button data-action="log">Clear</button>
    </div></div>
    <table class="data-table"><thead><tr><th>Head</th><th>Entries</th><th>Status</th></tr></thead>
    <tbody>
      <tr><td>0</td><td>128</td><td>pending merge</td></tr>
      <tr><td>1</td><td>64</td><td>ok</td></tr>
      <tr><td>2</td><td>12</td><td>ok</td></tr>
    </tbody></table>`;
}

function zoneHtml(title) {
  return `<div class="tool-head"><div><h2>${title}</h2><p>zl / cutzone / delzone · family zone table</p></div>
    <div class="tool-actions">
      <button class="primary" data-action="log">List</button>
      <button data-action="log">Cut Zone</button>
      <button data-action="log">Del Zone</button>
    </div></div>
    <table class="data-table"><thead><tr><th>Zone</th><th>Start Cyl</th><th>End Cyl</th><th>SPT</th></tr></thead>
    <tbody>
      <tr><td>00</td><td>0</td><td>2048</td><td>2048</td></tr>
      <tr><td>07</td><td>120000</td><td>134000</td><td>1800</td></tr>
      <tr><td>12</td><td>200000</td><td>220000</td><td>1600</td></tr>
    </tbody></table>`;
}

function ataScanHtml(title, toolId = "atascan") {
  return `<div class="tool-head"><div><h2>${title}</h2><p>ATA LBA surface path · verify / write-verify · map to G/P list · active: <b>${toolId}</b></p></div>
    <div class="tool-actions">
      <button class="primary" data-action="ata-run">Start</button>
      <button data-action="ata-stop">Stop</button>
      <button data-action="ata-report">Report</button>
    </div></div>
    <div class="scan-params">
      <label>Mode
        <select id="ataMode">
          <option value="full" ${toolId === "ata-full" ? "selected" : ""}>Full Surface</option>
          <option value="quick" ${toolId === "ata-quick" ? "selected" : ""}>Quick</option>
          <option value="verify" ${toolId === "ata-verify" ? "selected" : ""}>Read Verify</option>
          <option value="write" ${toolId === "ata-write" ? "selected" : ""}>Write + Verify</option>
          <option value="range" ${toolId === "ata-range" ? "selected" : ""}>LBA Range</option>
        </select>
      </label>
      <label>Start LBA <input id="ataStart" value="0" /></label>
      <label>End LBA <input id="ataEnd" value="MAX" /></label>
      <label>Block <input id="ataBlock" value="256" /></label>
      <label>Timeout ms <input id="ataTo" value="3000" /></label>
      <label>On error
        <select id="ataErr">
          <option>Log only</option>
          <option ${toolId === "ata-to-glist" ? "selected" : ""}>Add → G-List</option>
          <option ${toolId === "ata-to-plist" ? "selected" : ""}>Add → P-List</option>
          <option>Retry then G-List</option>
        </select>
      </label>
    </div>
    <div class="op-grid" style="margin-top:0.75rem">
      <button type="button" data-action="ata-run"><strong>Full Surface Scan</strong><span>entire user LBA space</span></button>
      <button type="button" data-action="ata-run"><strong>Quick Scan</strong><span>sampled tracks / zones</span></button>
      <button type="button" data-action="ata-run"><strong>Read Verify</strong><span>ATA verify, no data copy out</span></button>
      <button type="button" data-action="ata-run"><strong>Write + Verify</strong><span>destructive pattern pass</span></button>
      <button type="button" data-action="ata-pending"><strong>Pending Defects</strong><span>current / pending sector attrs</span></button>
      <button type="button" data-action="ata-report"><strong>Scan Report</strong><span>errors / speed / map</span></button>
    </div>
    <table class="data-table" style="margin-top:0.85rem"><thead><tr><th>LBA</th><th>Len</th><th>Sense</th><th>Action</th></tr></thead>
    <tbody>
      <tr><td>0x0012A400</td><td>8</td><td>UNC</td><td>→ G-List</td></tr>
      <tr><td>0x00A81C00</td><td>1</td><td>UNC</td><td>retry ok</td></tr>
      <tr><td>0x01F00E20</td><td>16</td><td>timeout</td><td>logged</td></tr>
    </tbody></table>`;
}

function chsScanHtml(title, toolId = "chsscan") {
  return `<div class="tool-head"><div><h2>${title}</h2><p>AC_RDWRCHS · rdchs/wrchs/RVCHS/RAW · ToneScan · SA defect · active: <b>${toolId}</b></p></div>
    <div class="tool-actions">
      <button class="primary" data-action="chs-run">Start</button>
      <button data-action="chs-stop">Stop</button>
      <button data-action="chs-report">Report</button>
    </div></div>
    <div class="scan-params">
      <label>Mode
        <select id="chsMode">
          <option value="rd" ${toolId === "chs-rd" ? "selected" : ""}>Read CHS</option>
          <option value="wr" ${toolId === "chs-wr" ? "selected" : ""}>Write CHS</option>
          <option value="rv" ${toolId === "chs-rv" ? "selected" : ""}>Verify CHS</option>
          <option value="raw" ${toolId === "chs-raw" ? "selected" : ""}>RAW CHS</option>
          <option value="ecc" ${toolId === "chs-ecc" ? "selected" : ""}>ECC Scan</option>
          <option value="tone-w" ${toolId === "chs-tone-w" ? "selected" : ""}>Tone Write</option>
          <option value="tone-r" ${toolId === "chs-tone-r" ? "selected" : ""}>Tone Read</option>
          <option value="sa" ${toolId === "chs-sa-defect" ? "selected" : ""}>SA Defect→RPList</option>
        </select>
      </label>
      <label>Head <input id="chsHd" value="ALL" /></label>
      <label>Start Cyl <input id="chsC0" value="0" /></label>
      <label>End Cyl <input id="chsC1" value="MAX" /></label>
      <label>Sector <input id="chsSec" value="1" /></label>
      <label>Relocation
        <select id="chsRelo">
          <option ${toolId === "chs-relo" ? "selected" : ""}>With Relo</option>
          <option ${toolId === "chs-norelo" ? "selected" : ""}>Skip Relo</option>
        </select>
      </label>
    </div>
    <div class="op-grid" style="margin-top:0.75rem">
      <button type="button" data-action="chs-run"><strong>Read CHS Scan</strong><span>rdchs · AC_RDWRCHS</span></button>
      <button type="button" data-action="chs-run"><strong>Write CHS Scan</strong><span>wrchs / RepWRCHS</span></button>
      <button type="button" data-action="chs-run"><strong>Verify CHS</strong><span>RVCHS</span></button>
      <button type="button" data-action="chs-run"><strong>RAW Path</strong><span>rdchsraw / wrchsraw</span></button>
      <button type="button" data-action="chs-run"><strong>Tone Scan Write</strong><span>ToneScanWrite</span></button>
      <button type="button" data-action="chs-run"><strong>Tone Scan Read</strong><span>ToneScanRead</span></button>
      <button type="button" data-action="chs-run"><strong>ECC Scan</strong><span>eVCHS_ECCSCAN</span></button>
      <button type="button" data-action="chs-run"><strong>SA Defect → RPList</strong><span>scan SA then plist</span></button>
    </div>
    <table class="data-table" style="margin-top:0.85rem"><thead><tr><th>Cyl</th><th>Hd</th><th>Sec</th><th>Result</th></tr></thead>
    <tbody>
      <tr><td>154220</td><td>0</td><td>12</td><td>UNC</td></tr>
      <tr><td>88102</td><td>1</td><td>3</td><td>ECC corrected</td></tr>
      <tr><td>-10</td><td>2</td><td>1</td><td>SA probe ok</td></tr>
    </tbody></table>`;
}

function arcoHtml(title) {
  return `<div class="tool-head"><div><h2>${title}</h2><p>dlfile + XF + msf · FileID depends on family arco_type</p></div>
    <div class="tool-actions">
      <button class="primary" data-action="arco-full">Full</button>
      <button data-action="arco-hot">Hot</button>
      <button data-action="arco-mini">Mini</button>
    </div></div>
    <div class="op-grid">
      <button type="button" data-action="arco-full"><strong>Full ARCO</strong><span>0xC4/0x2407 · Test 0x46</span></button>
      <button type="button" data-action="arco-hot"><strong>Hot ARCO</strong><span>0xC4/0x2409 · Test 0x4A</span></button>
      <button type="button" data-action="arco-mini"><strong>Mini ARCO</strong><span>0xC4/0x2401 · Test 0x44</span></button>
    </div>`;
}

function sfHtml(title) {
  return `<div class="tool-head"><div><h2>${title}</h2><p>family SF chain · PollTestStatus / clrsflog / recsf</p></div>
    <div class="tool-actions">
      <button class="primary" data-action="sf-start">Start</button>
      <button data-action="sf-clear">Clear Log</button>
      <button data-action="sf-recover">Recover</button>
    </div></div>
    <div class="op-grid">
      <button type="button" data-action="sf-start"><strong>Start SF</strong><span>XF chain + msf</span></button>
      <button type="button" data-action="sf-clear"><strong>Clear SF Log</strong><span>0x31…0xE6…</span></button>
      <button type="button" data-action="sf-recover"><strong>Recover SF</strong><span>recsf from drive</span></button>
    </div>`;
}

function setMode(mode) {
  state.mode = mode;
  $$(".mode-btn").forEach((b) => b.classList.toggle("active", b.dataset.mode === mode));
  refreshFamilyHeader();
  log(`Transport → ${mode.toUpperCase()}`, "warn");
  closeMenus();
}

function detectPorts() {
  log("Detect controllers / ports", "ok");
  toast("Controllers detected");
  renderPorts();
}

function addPort() {
  const p = currentPort();
  if (!p || p.empty) return toast("Select a populated port");
  state.ports.forEach((x) => { x.locked = false; });
  p.locked = true;
  renderPorts();
  log(`Port ${p.label} exclusive lock (driver passthrough)`, "ok");
  toast(`${p.label} locked`);
  identifyDrive();
}

function releasePort() {
  const p = state.ports.find((x) => x.locked);
  if (!p) return toast("No locked port");
  p.locked = false;
  renderPorts();
  log(`Released ${p.label}`, "warn");
}

function stopRun() {
  state.running = false;
  clearInterval(state.runTimer);
  state.runTimer = null;
  setQueue("run", "idle");
  log("Stop", "err");
}

function runProcess(kind) {
  if (!guardLicensed()) return;
  if (!state.familyName) return toast("Load a family first");
  if (!state.ports.some((p) => p.locked)) return toast("Add/Lock a port first");
  if (state.running) return toast("Already running");
  state.running = true;
  let prog = 0;
  openTool(kind.startsWith("arco") ? "arco" : kind.startsWith("sf") ? "sf" : state.tool || "arco");
  setQueue("run", "running");
  log(`Execute ${kind} on ${state.familyName} / ${state.fwLabel}`, "warn");
  log(`XF… msf polling`, "ok");
  state.runTimer = setInterval(() => {
    if (!state.running) return;
    prog = Math.min(100, prog + 3.2);
    $("#progressBar").style.width = `${prog}%`;
    $("#telemProg").textContent = `${prog.toFixed(1)}%`;
    $("#telemPtm").textContent = prog < 50 ? "0xC4" : "0xBA";
    $("#telemHd").textContent = String(Math.min(3, Math.floor(prog / 30))).padStart(2, "0");
    $("#telemZn").textContent = String(Math.floor(prog / 9)).padStart(2, "0");
    if (prog >= 100) {
      clearInterval(state.runTimer);
      state.running = false;
      setQueue("run", "done");
      log("Complete · ExtErr 0000", "ok");
      toast("Done");
    }
  }, 160);
  closeMenus();
}

function onAction(action) {
  const map = {
    detect: detectPorts,
    "add-port": addPort,
    release: releasePort,
    "reset-port": () => log("Drive reset — port session held", "warn"),
    "set-sata": () => setMode("sata"),
    "set-terminal": () => setMode("terminal"),
    "family-auto": () => {
      const p = currentPort();
      if (!p || p.empty) return toast("Select a drive");
      identifyDrive();
      if (state.identity?.readyForRom4f) {
        readRom4f();
        resolveFwPack();
      } else if (p.family && p.family !== "—") {
        selectFamily(p.form, p.family);
      }
    },
    "identify-drive": identifyDrive,
    "read-rom4f": readRom4f,
    "resolve-fw": resolveFwPack,
    "backup-all": () => runBackupStep("all"),
    "backup-rom": () => runBackupStep("rom"),
    "backup-rom-mod": () => runBackupStep("rom-mod"),
    "backup-sa": () => runBackupStep("sa"),
    "backup-track": () => runBackupStep("track"),
    "save-paths": saveFwPaths,
    "use-backup-source": () => {
      state.fwSource = "backup";
      refreshFamilyHeader();
      log(`Active FW source → backup ${sessionBackupDir()}`, "warn");
      toast("Using original backup as FW source");
      if (state.tool?.startsWith("backup")) openTool(state.tool);
    },
    "arco-full": () => runProcess("arco-full"),
    "arco-hot": () => runProcess("arco-hot"),
    "arco-mini": () => runProcess("arco-mini"),
    "sf-start": () => runProcess("sf-start"),
    "sf-clear": () => log("clrsflog", "warn"),
    "sf-recover": () => log("recsf", "warn"),
    "run-all": () => runProcess("run-all"),
    stop: stopRun,
    "exec-cuthead": () => log("kill head · AC_HDDEPOPCTRL", "warn"),
    "ata-run": () => runProcess("ata-scan"),
    "ata-stop": () => { stopRun(); log("ATA scan stopped", "err"); },
    "ata-pending": () => log("ATA pending/current defect query", "warn"),
    "ata-report": () => log("ATA scan report exported (prototype)", "ok"),
    "chs-run": () => runProcess("chs-scan"),
    "chs-stop": () => { stopRun(); log("CHS scan stopped", "err"); },
    "chs-report": () => log("CHS scan report exported (prototype)", "ok"),
    about: () => toast("Windex WD · HamGap · Win7/10/11 x64 factory desk"),
    "license-status": showLicenseStatus,
    "open-settings": () => openSettingsModal(),
    "close-settings": closeSettingsModal,
    "settings-activate": activateFromSettings,
    "settings-save-paths": saveSettingsPaths,
    "activate-license": activateFromGate,
    "demo-unlock": demoUnlock,
    "copy-mid": copyMachineId,
    "deactivate-license": async () => {
      if (!window.confirm("Remove local license? App will lock until re-activated.")) return;
      await License.deactivate();
      log("License deactivated", "warn");
      await enforceLicenseGate();
      if (!$("#settingsModal")?.hidden) await fillSettingsModal();
    },
    log: () => log("Command stub (prototype)", "warn")
  };
  (map[action] || map.log)();
}

function bindUi() {
  document.addEventListener("click", (e) => {
    const trigger = e.target.closest(".menu-trigger");
    if (trigger && trigger.parentElement.classList.contains("menu")) {
      e.stopPropagation();
      const menu = trigger.parentElement;
      const open = menu.classList.contains("open");
      closeMenus();
      if (!open) menu.classList.add("open");
      return;
    }

    const subTrig = e.target.closest(".submenu-trigger");
    if (subTrig) {
      e.stopPropagation();
      const sub = subTrig.parentElement;
      const was = sub.classList.contains("open");
      // close sibling submenus at same level
      const parent = sub.parentElement;
      $$(":scope > .submenu.open", parent).forEach((s) => { if (s !== sub) s.classList.remove("open"); });
      if (!was) sub.classList.add("open");
      else sub.classList.remove("open");
      return;
    }

    if (!e.target.closest("#menubar")) closeMenus();

    const fam = e.target.closest("[data-family]");
    if (fam) {
      e.preventDefault();
      selectFamily(fam.dataset.form || fam.dataset.family, fam.dataset.name);
      return;
    }

    const famOp = e.target.closest("[data-fam-op]");
    if (famOp) {
      e.preventDefault();
      const form = famOp.dataset.form;
      const name = famOp.dataset.name;
      if (state.familyName !== name) {
        // load family silently with default FW for tool open
        state.familyForm = form;
        state.familyName = name;
        const ref = FAMILY_FW[name];
        if (ref) {
          state.fwLabel = ref.choices[0].label;
          state.epath = ref.choices[0].epath;
        } else {
          state.fwLabel = "generic";
          state.epath = `${PROJECT_ROOT}\\\\FW\\\\${form}\\\\??\\\\${name}\\\\`;
        }
        refreshFamilyHeader();
      }
      const op = famOp.dataset.famOp;
      if (op === "backup-all") runBackupStep("all");
      else if (op === "backup-rom") runBackupStep("rom");
      else if (op === "backup-rom-mod") runBackupStep("rom-mod");
      else if (op === "backup-sa") runBackupStep("sa");
      else if (op === "backup-track") runBackupStep("track");
      else openTool(op);
      return;
    }

    const tool = e.target.closest("[data-tool]");
    if (tool?.dataset.tool) {
      openTool(tool.dataset.tool);
      return;
    }

    const action = e.target.closest("[data-action]");
    if (action?.dataset.action) onAction(action.dataset.action);

    const mode = e.target.closest(".mode-btn");
    if (mode?.dataset.mode) setMode(mode.dataset.mode);
  });
}

function init() {
  buildMenubar();
  renderPorts();
  bindUi();
  bindLicenseUi();
  loadDesktopBootstrap().then(() => {
    refreshFamilyHeader();
    return enforceLicenseGate();
  }).then((ok) => {
    if (ok) {
      log("License OK · Windex WD desktop ready", "ok");
      log(`Project: ${state.projectRoot || PROJECT_ROOT}`, "warn");
    } else {
      log("Activation required — enter serial or open License Settings", "warn");
    }
  });
}

async function loadDesktopBootstrap() {
  const desk = window.windexDesktop;
  if (!desk?.readSettings) return;
  try {
    const s = await desk.readSettings();
    if (s.projectRoot) state.projectRoot = s.projectRoot;
    if (s.packRoot) state.packRoot = s.packRoot;
    if (s.backupRoot) state.backupRoot = s.backupRoot;
    if (s.fwSource) state.fwSource = s.fwSource;
    const info = await desk.appInfo();
    document.title = `Windex WD ${info.version || ""}`.trim();
    log(`Desktop mode · license → ${info.licensePath}`, "ok");
  } catch (e) {
    log(`Desktop settings load failed: ${e.message || e}`, "err");
  }
}

let _licenseOk = false;
let _licenseInfo = null;

async function openSettingsModal(opts = {}) {
  const modal = $("#settingsModal");
  if (!modal) return;
  modal.hidden = false;
  const tab = opts.tab === "paths" ? "paths" : "license";
  switchSettingsTab(tab);
  await fillSettingsModal();
}

function closeSettingsModal() {
  const modal = $("#settingsModal");
  if (modal) modal.hidden = true;
}

function switchSettingsTab(tab) {
  $$(".stab").forEach((b) => b.classList.toggle("active", b.dataset.stab === tab));
  const lic = $("#pane-license");
  const paths = $("#pane-paths");
  if (lic) lic.hidden = tab !== "license";
  if (paths) paths.hidden = tab !== "paths";
}

async function fillSettingsModal() {
  const status = await License.validateStored();
  const mid = await License.machineId();
  $("#setLicStatus").textContent = status.ok ? "Licensed" : (status.message || "Not activated");
  $("#setLicEdition").textContent = status.ok ? status.editionName : "—";
  $("#setLicSerial").textContent = status.ok ? status.license.serialId : "—";
  $("#setLicDays").textContent = status.ok ? String(status.daysLeft) : "—";
  $("#setLicMid").textContent = mid;
  let licFile = "(browser localStorage)";
  if (window.windexDesktop?.licensePath) {
    try { licFile = await window.windexDesktop.licensePath(); } catch { /* ignore */ }
  }
  $("#setLicFile").textContent = licFile;
  $("#setProjectRoot").value = state.projectRoot || PROJECT_ROOT;
  $("#setPackRoot").value = state.packRoot;
  $("#setBackupRoot").value = state.backupRoot;
  $$('input[name="setFwSource"]').forEach((r) => {
    r.checked = r.value === state.fwSource;
  });
  const err = $("#setLicErr");
  if (err) err.hidden = true;
}

async function activateFromSettings() {
  const input = $("#setSerialInput");
  const err = $("#setLicErr");
  err.hidden = true;
  const res = await License.activate(input?.value || "");
  if (!res.ok) {
    err.hidden = false;
    err.textContent = res.error || "Activation failed";
    return;
  }
  toast(`Licensed · ${res.editionName}`);
  log(`Activated via Settings · ${res.editionName}`, "ok");
  input.value = "";
  await enforceLicenseGate();
  await fillSettingsModal();
}

async function saveSettingsPaths() {
  state.projectRoot = $("#setProjectRoot")?.value.trim() || PROJECT_ROOT;
  state.packRoot = $("#setPackRoot")?.value.trim() || `${state.projectRoot}\\\\FW`;
  state.backupRoot = $("#setBackupRoot")?.value.trim() || `${state.projectRoot}\\\\Backup`;
  const src = document.querySelector('input[name="setFwSource"]:checked');
  if (src) state.fwSource = src.value;
  if (window.windexDesktop?.writeSettings) {
    await window.windexDesktop.writeSettings({
      projectRoot: state.projectRoot,
      packRoot: state.packRoot,
      backupRoot: state.backupRoot,
      fwSource: state.fwSource
    });
  }
  refreshFamilyHeader();
  toast("Paths saved");
  log(`Paths saved · ${state.projectRoot}`, "ok");
}

async function enforceLicenseGate() {
  const gate = $("#licenseGate");
  const app = $("#app");
  const status = await License.validateStored();
  _licenseOk = !!status.ok;
  _licenseInfo = status;
  if (status.ok) {
    gate.hidden = true;
    app.classList.remove("locked");
    $("#statusLeft").textContent = `Licensed · ${status.editionName} · ${status.daysLeft}d left`;
    return true;
  }
  gate.hidden = false;
  app.classList.add("locked");
  const mid = await License.machineId();
  $("#gateMachineId").textContent = mid;
  const err = $("#licenseErr");
  if (status.reason && status.reason !== "not_activated") {
    err.hidden = false;
    err.textContent = status.message || status.reason;
  } else {
    err.hidden = true;
  }
  return false;
}

function guardLicensed() {
  if (_licenseOk) return true;
  toast("Activate license first");
  enforceLicenseGate();
  return false;
}

async function activateFromGate() {
  const input = $("#licenseSerialInput");
  const err = $("#licenseErr");
  const raw = input?.value || "";
  err.hidden = true;
  const res = await License.activate(raw);
  if (!res.ok) {
    err.hidden = false;
    err.textContent = res.error || "Activation failed";
    log(`Activation failed: ${res.error}`, "err");
    return;
  }
  log(`Activated · ${res.editionName} · SN ${res.license.serialId}`, "ok");
  toast(`Licensed · ${res.editionName}`);
  input.value = "";
  await enforceLicenseGate();
}

async function demoUnlock() {
  const err = $("#licenseErr");
  err.hidden = true;
  try {
    const mid = await License.machineId();
    const key = await License.issueBoundLicense({
      machineId: mid,
      edition: "PRO",
      daysValid: 14,
      serialId: "DEMO"
    });
    const res = await License.activate(key);
    if (!res.ok) {
      err.hidden = false;
      err.textContent = res.error || "Demo unlock failed";
      return;
    }
    log("Demo unlock · Professional · 14 days (test build)", "warn");
    toast("Demo unlocked · 14 days");
    await enforceLicenseGate();
    if ($("#settingsModal") && !$("#settingsModal").hidden) await fillSettingsModal();
  } catch (e) {
    err.hidden = false;
    err.textContent = String(e.message || e);
  }
}

async function copyMachineId() {
  const mid = $("#gateMachineId")?.textContent || (await License.machineId());
  try {
    await navigator.clipboard.writeText(mid);
    toast("Machine ID copied");
  } catch {
    window.prompt("Copy Machine ID", mid);
  }
}

async function showLicenseStatus() {
  const status = await License.validateStored();
  const mid = await License.machineId();
  if (!status.ok) {
    toast(status.message || "Not licensed");
    await enforceLicenseGate();
    return;
  }
  const lic = status.license;
  setIdentifyTrace([
    `License: ${status.editionName} (${lic.edition})`,
    `Serial ID: ${lic.serialId}`,
    `Machine: ${lic.machineId}`,
    `Issued: ${lic.issuedAt}`,
    `Expires: ${lic.expiresAt} (${status.daysLeft} days left)`,
    `Seal: ${lic.seal.slice(0, 16)}…`,
    "",
    "Local Machine ID: " + mid,
    "Seller keygen: license-keygen.html"
  ]);
  toast(`${status.editionName} · ${status.daysLeft}d left`);
  log(`License status OK · ${status.editionName}`, "ok");
}

function bindLicenseUi() {
  $("#licenseSerialInput")?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      activateFromGate();
    }
  });
  document.addEventListener("click", (e) => {
    const stab = e.target.closest(".stab");
    if (stab?.dataset.stab) {
      switchSettingsTab(stab.dataset.stab);
      return;
    }
    if (e.target.id === "settingsModal") closeSettingsModal();
  });
  if (window.windexDesktop?.onOpenLicenseSettings) {
    window.windexDesktop.onOpenLicenseSettings((payload) => openSettingsModal(payload));
  }
  // Periodic re-validation (anti-tamper heartbeat for prototype)
  setInterval(async () => {
    if (!_licenseOk) return;
    const status = await License.validateStored();
    if (!status.ok) {
      _licenseOk = false;
      log(`License revoked: ${status.message}`, "err");
      toast(status.message || "License invalid");
      await enforceLicenseGate();
    } else {
      _licenseInfo = status;
    }
  }, 60_000);
}

init();
