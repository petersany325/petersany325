const classes = [
  {
    id: 1,
    age: "3-6",
    title: "کشف حرکت",
    meta: "۴۵ دقیقه · مهارت حرکتی و تعادل · مربی کودک",
  },
  {
    id: 2,
    age: "3-6",
    title: "کارگاه خیال",
    meta: "۶۰ دقیقه · داستان‌گویی و بازی نقش · خلاقیت",
  },
  {
    id: 3,
    age: "7-9",
    title: "شهر مشاغل کوچک",
    meta: "۷۵ دقیقه · نقش‌آفرینی شغلی · کار تیمی",
  },
  {
    id: 4,
    age: "7-9",
    title: "چالش صعود",
    meta: "۶۰ دقیقه · دیوار صعود سبک · اعتماد به نفس",
  },
  {
    id: 5,
    age: "10-12",
    title: "رهبری بازی",
    meta: "۹۰ دقیقه · طراحی بازی گروهی · مهارت اجتماعی",
  },
  {
    id: 6,
    age: "10-12",
    title: "کمپ نیم‌روزه STEM",
    meta: "۳ ساعت · ساخت و آزمایش · فصلی",
  },
];

const parties = {
  basic: {
    name: "پکیج پایه",
    price: 2500000,
    label: "۲٬۵۰۰٬۰۰۰ تومان",
    includes: [
      "۹۰ دقیقه بازی برای تا ۱۰ کودک",
      "میز اختصاصی در کافه",
      "دکوراسیون ساده",
      "بدون اتاق خصوصی",
    ],
  },
  plus: {
    name: "پکیج استاندارد",
    price: 4200000,
    label: "۴٬۲۰۰٬۰۰۰ تومان",
    includes: [
      "۲ ساعت بازی برای تا ۱۲ کودک",
      "اتاق خصوصی ۱ ساعته",
      "میزبان پارتی",
      "پیتزا و نوشیدنی پایه",
    ],
  },
  vip: {
    name: "پکیج ویژه",
    price: 6900000,
    label: "۶٬۹۰۰٬۰۰۰ تومان",
    includes: [
      "۳ ساعت تجربه کامل",
      "اتاق VIP + میزبان",
      "تم قابل سفارش",
      "کیک کوچک + عکس گروهی",
    ],
  },
};

const bookingPrices = {
  open2: 180000,
  openDay: 280000,
  private: 8500000,
};

const money = (n) =>
  new Intl.NumberFormat("fa-IR").format(n) + " تومان";

const toastEl = document.getElementById("toast");
let toastTimer;

function showToast(message) {
  toastEl.hidden = false;
  toastEl.textContent = message;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastEl.hidden = true;
  }, 2800);
}

/* Mobile nav */
const navToggle = document.getElementById("navToggle");
const mobileNav = document.getElementById("mobileNav");

navToggle.addEventListener("click", () => {
  const open = mobileNav.hasAttribute("hidden");
  if (open) {
    mobileNav.removeAttribute("hidden");
    navToggle.setAttribute("aria-expanded", "true");
  } else {
    mobileNav.setAttribute("hidden", "");
    navToggle.setAttribute("aria-expanded", "false");
  }
});

mobileNav.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    mobileNav.setAttribute("hidden", "");
    navToggle.setAttribute("aria-expanded", "false");
  });
});

/* Modals */
const bookingModal = document.getElementById("bookingModal");
const partyModal = document.getElementById("partyModal");
const waiverModal = document.getElementById("waiverModal");

document.querySelectorAll("[data-open]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const type = btn.getAttribute("data-open");
    if (type === "booking") bookingModal.showModal();
    if (type === "party") {
      updatePartyModalLabel();
      partyModal.showModal();
    }
    if (type === "waiver") waiverModal.showModal();
    mobileNav.setAttribute("hidden", "");
    navToggle.setAttribute("aria-expanded", "false");
  });
});

/* Academy filter */
const classList = document.getElementById("classList");

function renderClasses(age = "all") {
  const items = classes.filter((c) => age === "all" || c.age === age);
  classList.innerHTML = items
    .map(
      (c) => `
      <article class="class-item">
        <h3>${c.title}</h3>
        <p class="class-meta">سن ${c.age.replace("-", "–")} · ${c.meta}</p>
      </article>`
    )
    .join("");
}

document.querySelectorAll(".filter-bar .chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    document.querySelectorAll(".filter-bar .chip").forEach((c) => {
      c.classList.remove("is-active");
      c.setAttribute("aria-selected", "false");
    });
    chip.classList.add("is-active");
    chip.setAttribute("aria-selected", "true");
    renderClasses(chip.dataset.age);
  });
});

renderClasses();

/* Parties */
let currentParty = "basic";
const partyPanel = document.getElementById("partyPanel");
const partyTotal = document.getElementById("partyTotal");

function calcPartyTotal() {
  let total = parties[currentParty].price;
  document.querySelectorAll("#addons input:checked").forEach((el) => {
    total += Number(el.dataset.price);
  });
  partyTotal.textContent = money(total);
  return total;
}

function renderParty(key) {
  currentParty = key;
  const p = parties[key];
  partyPanel.innerHTML = `
    <h3>${p.name}</h3>
    <p class="price">${p.label}</p>
    <ul>${p.includes.map((i) => `<li>${i}</li>`).join("")}</ul>
  `;
  calcPartyTotal();
  updatePartyModalLabel();
}

function updatePartyModalLabel() {
  const el = document.getElementById("partyModalPkg");
  if (!el) return;
  const addons = [...document.querySelectorAll("#addons input:checked")].map(
    (i) => i.parentElement.textContent.trim().split("(+")[0].trim()
  );
  el.textContent = `${parties[currentParty].name}${
    addons.length ? " + " + addons.join("، ") : ""
  } · ${money(calcPartyTotal())}`;
}

document.querySelectorAll(".party-tabs .chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    document.querySelectorAll(".party-tabs .chip").forEach((c) => {
      c.classList.remove("is-active");
      c.setAttribute("aria-selected", "false");
    });
    chip.classList.add("is-active");
    chip.setAttribute("aria-selected", "true");
    renderParty(chip.dataset.party);
  });
});

document.querySelectorAll("#addons input").forEach((input) => {
  input.addEventListener("change", calcPartyTotal);
});

renderParty("basic");

/* Membership */
document.querySelectorAll(".member-card").forEach((card) => {
  card.addEventListener("click", () => {
    document
      .querySelectorAll(".member-card")
      .forEach((c) => c.classList.remove("is-selected"));
    card.classList.add("is-selected");
    const name = card.querySelector("h3").textContent;
    document.getElementById(
      "memberNote"
    ).textContent = `پلن «${name}» برای تست انتخاب شد. در نسخه واقعی به درگاه پرداخت می‌رود.`;
    showToast(`پلن ${name} انتخاب شد`);
  });
});

/* Booking estimate */
const bookType = document.getElementById("bookType");
const bookKids = document.getElementById("bookKids");
const bookTotal = document.getElementById("bookTotal");

function updateBookingTotal() {
  const type = bookType.value;
  const kids = Math.max(1, Number(bookKids.value) || 1);
  const total =
    type === "private" ? bookingPrices.private : bookingPrices[type] * kids;
  bookTotal.textContent = money(total);
}

bookType.addEventListener("change", updateBookingTotal);
bookKids.addEventListener("input", updateBookingTotal);
updateBookingTotal();

document.getElementById("confirmBooking").addEventListener("click", () => {
  const form = document.getElementById("bookingForm");
  if (!form.reportValidity()) return;
  bookingModal.close();
  showToast("رزرو تست ثبت شد — اعلان نمونه");
});

document.getElementById("confirmParty").addEventListener("click", () => {
  const form = document.getElementById("partyForm");
  if (!form.reportValidity()) return;
  partyModal.close();
  showToast("درخواست تولد تست ثبت شد");
});

document.getElementById("confirmWaiver").addEventListener("click", () => {
  const form = document.getElementById("waiverForm");
  if (!form.reportValidity()) return;
  waiverModal.close();
  showToast("رضایت‌نامه تست ثبت شد");
});

document.getElementById("groupForm").addEventListener("submit", (e) => {
  e.preventDefault();
  showToast("درخواست گروهی تست ارسال شد");
  e.target.reset();
});

/* Sensible default dates (tomorrow) */
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
const iso = tomorrow.toISOString().slice(0, 10);
["bookDate", "partyDate"].forEach((id) => {
  const el = document.getElementById(id);
  if (el) el.value = iso;
});
