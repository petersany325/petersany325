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

const FAMILY_FW = {
  ATLANTIS: {
    form: "3.5",
    choices: [
      { label: "701537", epath: "D:\\\\3.5\\\\1\\\\701537\\\\" },
      { label: "771668", epath: "D:\\\\3.5\\\\1\\\\771668\\\\" },
      { label: "701590", epath: "D:\\\\3.5\\\\1\\\\701590\\\\" }
    ],
    flags: { arco_type: 0, tar_file: "0xC8" }
  },
  FIREBIRD: {
    form: "2.5",
    choices: [
      { label: "0353B", epath: "D:\\\\2.5\\\\10\\\\0353B\\\\" },
      { label: "0379C", epath: "D:\\\\2.5\\\\10\\\\0379C\\\\" }
    ],
    flags: { arco_type: 0, tar_file: "0x290B" }
  },
  PALMER: {
    form: "2.5",
    choices: [
      { label: "020PP", epath: "D:\\\\2.5\\\\46\\\\020PP\\\\" },
      { label: "0506B", epath: "D:\\\\2.5\\\\46\\\\0506B\\\\" }
    ],
    flags: { arco_type: 1, tar_file: "0x2420" }
  }
};

const FAMILY_OPS = [
  { id: "cuthead", label: "Cut Head…", desc: "AC_HDDEPOPCTRL" },
  {
    id: "zone", label: "Zone Ops ▸", children: [
      { id: "zone-list", label: "Zone List" },
      { id: "zone-cut", label: "Cut Zone" },
      { id: "zone-del", label: "Del Zone" }
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
  tool: null,
  running: false,
  runTimer: null,
  ports: [
    { id: "sata0", type: "sata", label: "SATA:0", base: "01F0/03F6", model: "WDC WD10JPVX-22JC3T0", serial: "WX21A84K3821", fw: "020PP", form: "2.5", family: "PALMER", locked: false },
    { id: "sata1", type: "sata", label: "SATA:1", base: "F070/F062", model: "WDC WD5000LPVX-08V0TT0", serial: "WX61E93P1102", fw: "01A01", form: "2.5", family: "FIREBIRD", locked: false },
    { id: "sata2", type: "sata", label: "SATA:2", base: "8080/8002", model: "— empty —", locked: false, empty: true },
    { id: "com3", type: "terminal", label: "COM3", base: "SIO 115200", model: "Terminal target", locked: false }
  ]
};

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
        <button type="button" data-tool="cuthead">Cut Head</button>
        <button type="button" data-tool="zone">Zone</button>
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
        <button type="button" data-action="about">About Factory Desk</button>
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

function refreshFamilyHeader() {
  $("#transportLabel").textContent = state.mode === "sata" ? "SATA · PassThrough exclusive" : "Terminal · SIO / COM";
  if (!state.familyName) {
    $("#familyTitle").textContent = "Select a Family";
    $("#familyMeta").textContent = "Each family has its own tool menu: Cut Head, Zone, P-List, G-List, Modules, ARCO, SF…";
    $("#familyRibbon").hidden = true;
    $("#telemFamily").textContent = "—";
    $("#telemFw").textContent = "—";
    $("#statusLeft").textContent = "No family loaded";
    $("#statusRight").textContent = "RPM path: —";
    return;
  }
  $("#familyTitle").textContent = `${state.familyName} · ${state.familyForm}"`;
  $("#familyMeta").textContent = `FW ${state.fwLabel || "—"}  ·  ${state.epath || "—"}  ·  tools extensible per family`;
  $("#familyRibbon").hidden = false;
  $("#telemFamily").textContent = state.familyName;
  $("#telemFw").textContent = state.fwLabel || "—";
  $("#statusLeft").textContent = `Family ${state.familyName} loaded — tools ready`;
  $("#statusRight").textContent = `RPM path: ${state.epath || "—"}`;
  setQueue("family", "done");
}

function selectFamily(form, name) {
  state.familyForm = form;
  state.familyName = name;
  const ref = FAMILY_FW[name];
  let fw = "generic";
  let path = form === "2.5" ? `D:\\\\2.5\\\\??\\\\${name}\\\\` : `D:\\\\3.5\\\\??\\\\${name}\\\\`;
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
  openTool("plist");
  log(`Family menu loaded: ${form}" / ${name} / FW ${fw}`, "warn");
  toast(`${name} · ${fw}`);
  closeMenus();
}

function openTool(toolId, familyName = state.familyName) {
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
    (toolId.startsWith("plist") && b.dataset.tool === "plist") ||
    (toolId.startsWith("glist") && b.dataset.tool === "glist") ||
    (toolId.startsWith("zone") && b.dataset.tool === "zone") ||
    (toolId.startsWith("arco") && b.dataset.tool === "arco") ||
    (toolId.startsWith("sf") && b.dataset.tool === "sf")));

  const ws = $("#toolWorkspace");
  const fam = state.familyName;
  const title = `${fam} · ${toolLabel(toolId)}`;
  setQueue("tool", "running");

  const shells = {
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

  const key = toolId in shells ? toolId : toolId.split("-")[0];
  ws.innerHTML = (shells[toolId] || shells[key] || shells.plist)();
  log(`Opened ${fam} tool: ${toolId}`, "ok");
  setQueue("tool", "done");
  closeMenus();
}

function toolLabel(id) {
  const map = {
    cuthead: "Cut Head", zone: "Zone Ops", plist: "P-List", glist: "G-List",
    modules: "Modules / DIR", arco: "ARCO", sf: "SF Chain", dcm: "DCM / Capacity"
  };
  return map[id] || map[id.split("-")[0]] || id;
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
  if (!state.familyName && p.family) selectFamily(p.form, p.family);
  renderPorts();
  log(`Port ${p.label} exclusive lock (driver passthrough)`, "ok");
  toast(`${p.label} locked`);
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
      selectFamily(p.form, p.family);
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
    about: () => toast("Family-centric Factory Desk prototype"),
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
          state.epath = `${form === "2.5" ? "D:\\\\2.5" : "D:\\\\3.5"}\\\\??\\\\${name}\\\\`;
        }
        refreshFamilyHeader();
      }
      openTool(famOp.dataset.famOp);
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
  refreshFamilyHeader();
  bindUi();
  log("Family-centric menu prototype ready", "ok");
  log("Family → 2.5/3.5 → [NAME] → Cut Head / Zone / P-List / G-List / …", "warn");
}

init();
