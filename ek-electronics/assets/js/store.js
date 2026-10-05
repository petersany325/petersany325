const WA_NUMBER = "27105002140";
const PRODUCTS = [
  { id: "ent-hdd-8", name: "Enterprise SAS HDD 8TB", cat: "Enterprise / Server HDDs (SAS / SATA)", price: 1899, grade: "Grade A refurbished", img: "assets/img/hdd.jpg", stock: 42 },
  { id: "desk-hdd-4", name: "Desktop HDD 4TB 3.5\"", cat: "Desktop HDDs (3.5”)", price: 749, grade: "Grade A+", img: "assets/img/hdd.jpg", stock: 88 },
  { id: "lap-hdd-1", name: "Laptop HDD 1TB 2.5\"", cat: "Laptop HDDs (2.5”)", price: 429, grade: "Certified", img: "assets/img/laptop.jpg", stock: 61 },
  { id: "sata-ssd-960", name: "SATA SSD 960GB", cat: "SATA SSDs", price: 1099, grade: "New / OEM", img: "assets/img/ssd.jpg", stock: 54 },
  { id: "nvme-2", name: "NVMe SSD 2TB M.2", cat: "NVMe SSDs (M.2 / PCIe)", price: 2499, grade: "Enterprise pull", img: "assets/img/chips.jpg", stock: 27 },
  { id: "ext-ssd", name: "External SSD 1TB USB-C", cat: "External SSDs", price: 1649, grade: "Retail kit", img: "assets/img/dock.jpg", stock: 33 },
  { id: "ent-ssd", name: "Enterprise SSD 1.92TB", cat: "Enterprise / Server SSDs", price: 3290, grade: "Health 98%+", img: "assets/img/ssd.jpg", stock: 19 },
  { id: "ddr4-32", name: "Desktop Memory 32GB DDR4", cat: "Desktop Memory (DDR3 / DDR4 / DDR5)", price: 890, grade: "Tested", img: "assets/img/ram.jpg", stock: 70 },
  { id: "sodimm-16", name: "Laptop SODIMM 16GB DDR4", cat: "Laptop Memory (SODIMM)", price: 520, grade: "Tested", img: "assets/img/ram.jpg", stock: 46 },
  { id: "ecc-64", name: "Server ECC RDIMM 64GB", cat: "Server Memory (ECC / Registered)", price: 2180, grade: "Server pull", img: "assets/img/ram.jpg", stock: 14 },
  { id: "gpu-16", name: "Graphics Card 16GB", cat: "Graphics Cards (GPUs)", price: 4590, grade: "Refurbished", img: "assets/img/gpu.jpg", stock: 8 },
  { id: "cpu-8c", name: "Xeon 8-core Processor", cat: "Processors (CPUs)", price: 1890, grade: "Pulled / tested", img: "assets/img/chips.jpg", stock: 21 },
  { id: "mb-atx", name: "ATX Server Motherboard", cat: "Motherboards", price: 2450, grade: "Refurbished", img: "assets/img/mb.jpg", stock: 11 },
  { id: "psu-750", name: "750W Power Supply", cat: "Power Supplies", price: 980, grade: "80+ Gold", img: "assets/img/psu.jpg", stock: 25 },
  { id: "nic-10g", name: "10Gb Network Card", cat: "Network Cards (NICs)", price: 760, grade: "OEM", img: "assets/img/cables.jpg", stock: 30 },
  { id: "raid", name: "RAID Controller Card", cat: "RAID / Controller Cards", price: 1420, grade: "Tested", img: "assets/img/mb.jpg", stock: 9 },
  { id: "surv", name: "Surveillance HDD 6TB", cat: "Surveillance Drives (CCTV/NVR)", price: 1299, grade: "Grade A", img: "assets/img/hdd.jpg", stock: 37 },
  { id: "ext-hdd", name: "External Portable 2TB", cat: "External Portable Drives", price: 980, grade: "Retail", img: "assets/img/dock.jpg", stock: 40 },
  { id: "dock", name: "USB-C Dual Docking Station", cat: "Docking Stations (SATA/USB-C)", price: 690, grade: "New", img: "assets/img/dock.jpg", stock: 22 },
  { id: "encl", name: "HDD/SSD External Enclosure", cat: "HDD/SSD External Enclosures", price: 310, grade: "New", img: "assets/img/dock.jpg", stock: 58 },
  { id: "cables", name: "SATA & Power Cable Pack", cat: "SATA & Power Cables", price: 89, grade: "New", img: "assets/img/cables.jpg", stock: 120 },
  { id: "usb-sata", name: "USB to SATA Adapter", cat: "USB to SATA Adapters", price: 149, grade: "New", img: "assets/img/cables.jpg", stock: 77 },
  { id: "mount", name: "Drive Mounting Kit", cat: "Drive Mounting Kits", price: 129, grade: "New", img: "assets/img/cables.jpg", stock: 64 },
  { id: "fan", name: "Cooling Fan 120mm", cat: "Cooling Fans and Accessories", price: 99, grade: "New", img: "assets/img/psu.jpg", stock: 90 }
];

const CATS = [...new Set(PRODUCTS.map((p) => p.cat))];

function money(n) {
  return new Intl.NumberFormat("en-ZA", { style: "currency", currency: "ZAR" }).format(n);
}

function cart() {
  return JSON.parse(localStorage.getItem("ek-cart") || "[]");
}
function saveCart(items) {
  localStorage.setItem("ek-cart", JSON.stringify(items));
  const badge = document.querySelector("[data-cart-count]");
  if (badge) badge.textContent = items.reduce((s, i) => s + i.qty, 0);
}

function addToCart(id, qty = 1) {
  const items = cart();
  const found = items.find((i) => i.id === id);
  if (found) found.qty += qty;
  else items.push({ id, qty });
  saveCart(items);
  toast(`${product(id).name} added to cart`);
}

function product(id) {
  return PRODUCTS.find((p) => p.id === id);
}

function toast(msg) {
  const el = document.createElement("div");
  el.className = "toast";
  el.textContent = msg;
  document.body.append(el);
  setTimeout(() => el.remove(), 2200);
}

function waLink(text) {
  return `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(text)}`;
}

function showPage(id) {
  document.querySelectorAll("[data-page]").forEach((p) => p.classList.toggle("hidden", p.dataset.page !== id));
  document.querySelectorAll(".menu a, .menu button").forEach((a) => a.classList.toggle("active", a.dataset.go === id));
  if (location.hash !== `#${id}`) history.replaceState(null, "", `#${id}`);
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function renderProducts(list, target = "#product-grid") {
  const root = document.querySelector(target);
  if (!root) return;
  root.innerHTML = list.map((p) => `
    <article class="product">
      <img src="${p.img}" alt="${p.name}" width="600" height="450">
      <div class="product-body">
        <div class="cat">${p.cat}</div>
        <strong>${p.name}</strong>
        <div><span class="grade">${p.grade}</span> <span class="muted"> · ${p.stock} in stock</span></div>
        <div class="price">${money(p.price)}</div>
        <div class="cta-row">
          <button class="btn btn-primary" data-add="${p.id}">Add to cart</button>
          <a class="btn btn-outline" href="${waLink(`Hi EK Electronics, I want to order ${p.name} (${p.id}).`)}">WhatsApp</a>
        </div>
      </div>
    </article>`).join("");
}

function renderCart() {
  const tbody = document.querySelector("#cart-body");
  if (!tbody) return;
  const items = cart().map((i) => ({ ...i, p: product(i.id) })).filter((i) => i.p);
  tbody.innerHTML = items.map((i) => `
    <tr>
      <td>${i.p.name}</td>
      <td>${money(i.p.price)}</td>
      <td><input class="qty" type="number" min="1" value="${i.qty}" data-qty="${i.id}"></td>
      <td>${money(i.p.price * i.qty)}</td>
      <td><button class="btn btn-outline" data-remove="${i.id}">Remove</button></td>
    </tr>`).join("") || `<tr><td colspan="5">Your cart is empty.</td></tr>`;
  const total = items.reduce((s, i) => s + i.p.price * i.qty, 0);
  document.querySelectorAll("[data-cart-total]").forEach((el) => { el.textContent = money(total); });
}

function checkoutWhatsApp() {
  const items = cart().map((i) => ({ ...i, p: product(i.id) })).filter((i) => i.p);
  if (!items.length) return toast("Cart is empty");
  const lines = items.map((i) => `• ${i.p.name} x${i.qty} = ${money(i.p.price * i.qty)}`);
  const total = items.reduce((s, i) => s + i.p.price * i.qty, 0);
  const name = document.querySelector("#co-name")?.value || "Customer";
  const phone = document.querySelector("#co-phone")?.value || "";
  const city = document.querySelector("#co-city")?.value || "South Africa";
  const msg = `New order from ${name}\nPhone: ${phone}\nShip to: ${city}\n\n${lines.join("\n")}\n\nTotal: ${money(total)}\nPlease confirm stock and courier.`;
  window.open(waLink(msg), "_blank");
  toast("Order opened in WhatsApp");
}

document.addEventListener("click", (e) => {
  const go = e.target.closest("[data-go]");
  if (go) {
    e.preventDefault();
    showPage(go.dataset.go);
    if (go.dataset.go === "cart" || go.dataset.go === "checkout") renderCart();
  }
  const add = e.target.closest("[data-add]");
  if (add) addToCart(add.dataset.add);
  const remove = e.target.closest("[data-remove]");
  if (remove) {
    saveCart(cart().filter((i) => i.id !== remove.dataset.remove));
    renderCart();
  }
  const cat = e.target.closest("[data-cat]");
  if (cat) {
    document.querySelectorAll("[data-cat]").forEach((b) => b.classList.toggle("active", b === cat));
    const value = cat.dataset.cat;
    const q = document.querySelector("#shop-search")?.value.toLowerCase() || "";
    renderProducts(PRODUCTS.filter((p) => (value === "all" || p.cat === value) && p.name.toLowerCase().includes(q)));
    document.querySelector("#result-count").textContent = `Showing ${document.querySelectorAll("#product-grid .product").length} of ${PRODUCTS.length} results`;
  }
});

document.addEventListener("input", (e) => {
  if (e.target.id === "shop-search" || e.target.id === "header-search") {
    const q = e.target.value.toLowerCase();
    if (e.target.id === "header-search") showPage("shop");
    const active = document.querySelector("[data-cat].active")?.dataset.cat || "all";
    renderProducts(PRODUCTS.filter((p) => (active === "all" || p.cat === active) && p.name.toLowerCase().includes(q)));
  }
  if (e.target.dataset.qty) {
    const items = cart();
    const row = items.find((i) => i.id === e.target.dataset.qty);
    if (row) row.qty = Math.max(1, Number(e.target.value) || 1);
    saveCart(items);
    renderCart();
  }
});

window.addEventListener("hashchange", () => {
  const id = (location.hash || "#home").slice(1) || "home";
  showPage(id);
  if (id === "cart" || id === "checkout") renderCart();
});

document.addEventListener("DOMContentLoaded", () => {
  saveCart(cart());
  renderProducts(PRODUCTS);
  renderProducts(PRODUCTS.slice(0, 8), "#home-products");
  const start = (location.hash || "#home").slice(1) || "home";
  if (start && start !== "home") {
    showPage(start);
    if (start === "cart" || start === "checkout") renderCart();
  }
  const filters = document.querySelector("#filters");
  if (filters) {
    filters.innerHTML = `<button class="active" data-cat="all">All categories</button>` +
      CATS.map((c) => `<button data-cat="${c}">${c}</button>`).join("");
  }
  const count = document.querySelector("#result-count");
  if (count) count.textContent = `Showing 1-${PRODUCTS.length} of 885 results (demo catalogue)`;
});
