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

const state = {
  mode: "sata",
  selectedPort: null,
  lockedPort: null,
  familyForm: null,
  familyName: null,
  running: false,
  runTimer: null,
  ports: [
    { id: "sata0", type: "sata", label: "SATA:0", base: "01F0/03F6", model: "WDC WD10JPVX-22JC3T0", serial: "WX21A84K3821", fw: "020PP", form: "2.5", family: "PALMER", head: "H3", media: "M7", cap: "1.00 TB", locked: false },
    { id: "sata1", type: "sata", label: "SATA:1", base: "F070/F062", model: "WDC WD5000LPVX-08V0TT0", serial: "WX61E93P1102", fw: "01A01", form: "2.5", family: "FIREBIRD", head: "H1", media: "M2", cap: "500 GB", locked: false },
    { id: "sata2", type: "sata", label: "SATA:2", base: "8080/8002", model: "— empty —", serial: "—", fw: "—", form: "—", family: "—", head: "—", media: "—", cap: "—", locked: false, empty: true },
    { id: "com3", type: "terminal", label: "COM3", base: "SIO 115200", model: "Terminal target", serial: "SIO", fw: "—", form: "—", family: "—", head: "—", media: "—", cap: "—", locked: false }
  ]
};

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

function log(msg, cls = "") {
  const line = document.createElement("div");
  if (cls) line.className = cls;
  const t = new Date().toLocaleTimeString();
  line.textContent = `[${t}] ${msg}`;
  const cons = $("#console");
  cons.appendChild(line);
  cons.scrollTop = cons.scrollHeight;
  const block = $("#logBlock");
  block.textContent = `${block.textContent ? block.textContent + "\n" : ""}${line.textContent}`;
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

function renderPorts() {
  const tree = $("#portTree");
  const sata = state.ports.filter((p) => p.type === "sata");
  const term = state.ports.filter((p) => p.type === "terminal");
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
      btn.className = "port-item";
      if (state.selectedPort === p.id) btn.classList.add("selected");
      if (p.locked) btn.classList.add("locked");
      btn.innerHTML = `<i></i><div><strong>${p.label}${p.locked ? " · LOCKED" : ""}</strong><small>${p.base} · ${p.empty ? "no drive" : p.model}</small></div>`;
      btn.addEventListener("click", () => {
        state.selectedPort = p.id;
        renderPorts();
        refreshDrive();
        log(`Selected ${p.label}`, "ok");
      });
      li.appendChild(btn);
      tree.appendChild(li);
    });
  };

  addGroup("Controllers / SATA", sata);
  addGroup("Terminal", term);
}

function renderFamilies() {
  const fill = (el, list, form) => {
    el.innerHTML = "";
    list.forEach((name, idx) => {
      const li = document.createElement("li");
      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = `${String(idx + 1).padStart(2, "0")} · ${name}`;
      if (state.familyForm === form && state.familyName === name) btn.classList.add("active");
      btn.addEventListener("click", () => selectFamily(form, name));
      li.appendChild(btn);
      el.appendChild(li);
    });
  };
  fill($("#family25"), FAM25, "2.5");
  fill($("#family35"), FAM35, "3.5");
}

function selectFamily(form, name) {
  state.familyForm = form;
  state.familyName = name;
  $("#specFamily").textContent = name;
  $("#specForm").textContent = `${form}"`;
  const path = form === "2.5"
    ? `D:\\\\2.5\\\\46\\\\${name === "PALMER" ? "020PP" : "xxxx"}\\\\`
    : `D:\\\\3.5\\\\01\\\\${name}\\\\`;
  $("#statusRight").textContent = `RPM path: ${path}`;
  renderFamilies();
  log(`Family selected: ${form}" / ${name}`, "warn");
  toast(`Family ${name}`);
  closeMenus();
}

function currentPort() {
  return state.ports.find((p) => p.id === state.selectedPort) || null;
}

function refreshDrive() {
  const p = currentPort();
  const locked = state.ports.find((x) => x.locked);
  $("#transportLabel").textContent = state.mode === "sata"
    ? "SATA · PassThrough exclusive"
    : "Terminal · SIO / COM";

  if (!p) {
    $("#driveModel").textContent = "No port selected";
    $("#driveSub").textContent = "Detect controllers, then Add Port to lock exclusive access";
    return;
  }

  $("#driveModel").textContent = p.empty ? `${p.label} — empty channel` : p.model;
  $("#driveSub").textContent = p.locked
    ? "Exclusive lock held — Windows storage detached from this channel"
    : "Port selected — use Add Port / Lock Exclusive before ARCO+SF";

  $("#specSerial").textContent = p.serial;
  $("#specFamily").textContent = state.familyName || p.family;
  $("#specForm").textContent = state.familyForm ? `${state.familyForm}"` : (p.form === "—" ? "—" : `${p.form}"`);
  $("#specFw").textContent = p.fw;
  $("#specHead").textContent = p.head;
  $("#specMedia").textContent = p.media;
  $("#specCap").textContent = p.cap;
  $("#specPort").textContent = `${p.label}  ${p.base}`;

  $("#statusLeft").textContent = locked
    ? `Port ${locked.label} exclusive lock held — Windows storage detached`
    : "No port locked — Windows storage still owns disks";

  if (!p.empty) {
    $("#identifyBlock").textContent = [
      `Model      : ${p.model}`,
      `Serial     : ${p.serial}`,
      `Firmware   : ${p.fw}`,
      `Family     : ${state.familyName || p.family}`,
      `Form       : ${state.familyForm || p.form}`,
      `Capacity   : ${p.cap}`,
      `Transport  : ${state.mode.toUpperCase()}`,
      `Base/Alt   : ${p.base}`,
      `Lock       : ${p.locked ? "EXCLUSIVE" : "none"}`,
      "",
      "vscon / vscid would populate extended tables here."
    ].join("\n");
  }
}

function setMode(mode) {
  state.mode = mode;
  $$(".mode-btn").forEach((b) => b.classList.toggle("active", b.dataset.mode === mode));
  log(`Transport mode → ${mode.toUpperCase()}`, "warn");
  refreshDrive();
  closeMenus();
}

function detectPorts() {
  log("pciscan / lstport … enumerating controllers", "ok");
  log("Found Intel SATA channels 0..2 + COM3", "ok");
  toast("Controllers detected");
  renderPorts();
}

function addPort() {
  const p = currentPort();
  if (!p) return toast("Select a port first");
  if (p.empty) return toast("Empty channel");
  state.ports.forEach((x) => { x.locked = false; });
  p.locked = true;
  state.lockedPort = p.id;
  if (!state.familyName && p.family !== "—") {
    state.familyForm = p.form;
    state.familyName = p.family;
  }
  renderPorts();
  renderFamilies();
  refreshDrive();
  log(`Add Port ${p.label}: claimed exclusive (WdHd-style detach from Windows)`, "ok");
  log(`vscon OK on ${p.label}`, "ok");
  toast(`${p.label} locked`);
}

function releasePort() {
  const p = state.ports.find((x) => x.locked);
  if (!p) return toast("No locked port");
  p.locked = false;
  state.lockedPort = null;
  renderPorts();
  refreshDrive();
  log(`Released ${p.label} — returned to Windows storage stack`, "warn");
  toast("Port released");
}

function resetPort() {
  const p = currentPort();
  if (!p) return toast("Select a port");
  log(state.mode === "terminal" ? `sioreset on ${p.label}` : `reset / ireset on ${p.label}`, "warn");
  log("Port address session kept open after drive reset", "ok");
  toast("Drive reset — port held");
}

function identify() {
  const p = currentPort();
  if (!p || p.empty) return toast("Select a populated port");
  switchTab("identify");
  log(`did / Identify on ${p.label}`, "ok");
  refreshDrive();
}

function setQueue(step, status) {
  const li = $(`.queue [data-step="${step}"]`);
  if (!li) return;
  li.classList.remove("done", "running");
  if (status === "done" || status === "running") li.classList.add(status);
  li.querySelector("em").textContent = status;
}

function stopRun() {
  state.running = false;
  clearInterval(state.runTimer);
  state.runTimer = null;
  setQueue("arco", "idle");
  setQueue("sf", "idle");
  log("Stop requested — msf loop aborted", "err");
  toast("Stopped");
}

function runProcess(kind) {
  const locked = state.ports.find((x) => x.locked);
  if (!locked) return toast("Lock a port first (Add Port)");
  if (state.running) return toast("Already running");

  state.running = true;
  let prog = 0;
  const fileId = kind === "hot" ? "0x2409" : kind === "mini" ? "0xC4" : "0x2407";
  const testId = kind === "hot" ? "0x4A" : kind === "mini" ? "0x44" : "0x46";

  setQueue("init", "done");
  setQueue("rpm", "running");
  log(`dlfile from RPM path for ${state.familyName || locked.family}`, "ok");
  log(`XF ${fileId},${testId},…  KeySector AC_EXECFILE`, "warn");

  setTimeout(() => {
    if (!state.running) return;
    setQueue("rpm", "done");
    setQueue("arco", "running");
    log(`${kind === "hot" ? "Hot" : kind === "mini" ? "Mini" : "Full"} ARCO started → msf polling`, "ok");
  }, 600);

  state.runTimer = setInterval(() => {
    if (!state.running) return;
    prog += kind === "run-all" ? 2.2 : 3.5;
    if (prog >= 100) prog = 100;
    $("#progressBar").style.width = `${prog}%`;
    $("#telemProg").textContent = `${prog.toFixed(1)}%`;
    $("#telemPtm").textContent = prog < 55 ? "0xC4" : "0xBA";
    $("#telemHd").textContent = String(Math.min(3, Math.floor(prog / 25))).padStart(2, "0");
    $("#telemZn").textContent = String(Math.floor(prog / 8)).padStart(2, "0");
    $("#telemCyl").textContent = String(120000 + Math.floor(prog * 800));

    if (prog >= 55 && kind === "run-all") {
      setQueue("arco", "done");
      setQueue("sf", "running");
    }

    if (prog >= 100) {
      clearInterval(state.runTimer);
      state.runTimer = null;
      state.running = false;
      setQueue("arco", "done");
      if (kind === "run-all" || kind === "sf") setQueue("sf", "done");
      else setQueue("sf", "idle");
      log("PollTestStatus complete — ExtErr 0000", "ok");
      toast("Process complete");
    }
  }, 180);
}

function switchTab(name) {
  $$(".tab").forEach((t) => t.classList.toggle("active", t.dataset.tab === name));
  $$(".tab-panel").forEach((p) => p.classList.toggle("active", p.dataset.panel === name));
  closeMenus();
}

function onAction(action) {
  const map = {
    detect: detectPorts,
    "add-port": addPort,
    release: releasePort,
    "reset-port": resetPort,
    identify,
    lock: addPort,
    offline: () => { addPort(); log("Offline from Windows storage stack", "warn"); },
    "set-sata": () => setMode("sata"),
    "set-terminal": () => setMode("terminal"),
    "family-auto": () => {
      const p = currentPort();
      if (!p || p.empty) return toast("Select a drive first");
      selectFamily(p.form, p.family);
      log("Auto Detect family via vscon;vscid", "ok");
    },
    "arco-full": () => runProcess("full"),
    "arco-hot": () => runProcess("hot"),
    "arco-mini": () => runProcess("mini"),
    "sf-start": () => runProcess("sf"),
    "sf-clear": () => log("clrsflog — cleared 0x31/32/33/34/E6/E0…", "warn"),
    "sf-recover": () => log("recsf — recover from drive PST log", "warn"),
    "run-all": () => runProcess("run-all"),
    stop: stopRun,
    about: () => toast("WD Factory Desk UI prototype — menus + SATA/Terminal"),
    log: () => log("Menu command (prototype stub)", "warn")
  };
  (map[action] || map.log)();
  if (!["arco-full", "arco-hot", "arco-mini", "sf-start", "run-all"].includes(action)) closeMenus();
}

function bindMenus() {
  $$(".menu-trigger").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const menu = btn.parentElement;
      const wasOpen = menu.classList.contains("open");
      closeMenus();
      if (!wasOpen) menu.classList.add("open");
    });
  });

  $$(".submenu-trigger").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const sub = btn.parentElement;
      const open = sub.classList.contains("open");
      $$(".submenu.open").forEach((s) => s.classList.remove("open"));
      if (!open) sub.classList.add("open");
    });
  });

  document.addEventListener("click", () => closeMenus());
  $("#menubar").addEventListener("click", (e) => e.stopPropagation());

  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-action]");
    if (t?.dataset.action) onAction(t.dataset.action);

    const fam = e.target.closest("[data-family]");
    if (fam) selectFamily(fam.dataset.family, fam.dataset.name);

    const tab = e.target.closest("[data-tab]");
    if (tab?.dataset.tab) switchTab(tab.dataset.tab);

    const mode = e.target.closest(".mode-btn");
    if (mode?.dataset.mode) setMode(mode.dataset.mode);
  });
}

function init() {
  renderPorts();
  renderFamilies();
  refreshDrive();
  bindMenus();
  log("Factory Desk UI prototype ready", "ok");
  log("Use Port → Detect, then Add Port. Process menu for ARCO/SF.", "warn");
}

init();
