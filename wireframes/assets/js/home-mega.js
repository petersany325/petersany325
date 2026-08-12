(() => {
  const header = document.getElementById("consoleHeader");
  const nav = document.getElementById("consoleNav");
  const backdrop = document.getElementById("megaBackdrop");
  const mobileToggle = document.getElementById("mobileToggle");
  const items = [...document.querySelectorAll(".nav-item")];

  const closeAll = () => {
    items.forEach((item) => {
      item.classList.remove("open");
      const trigger = item.querySelector(".nav-trigger");
      if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
    header.classList.remove("mega-open");
    backdrop.classList.remove("show");
    backdrop.hidden = true;
  };

  const openItem = (item) => {
    closeAll();
    item.classList.add("open");
    const trigger = item.querySelector(".nav-trigger");
    if (trigger) trigger.setAttribute("aria-expanded", "true");
    if (item.classList.contains("mega-host")) {
      header.classList.add("mega-open");
      backdrop.hidden = false;
      requestAnimationFrame(() => backdrop.classList.add("show"));
    }
  };

  items.forEach((item) => {
    const trigger = item.querySelector(":scope > .nav-trigger");
    if (!trigger || trigger.tagName === "A") return;

    trigger.addEventListener("click", (e) => {
      e.preventDefault();
      if (item.classList.contains("open")) closeAll();
      else openItem(item);
    });

    // Desktop hover for faster mega/cascade feel
    item.addEventListener("mouseenter", () => {
      if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
        openItem(item);
      }
    });
  });

  header.addEventListener("mouseleave", () => {
    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
      closeAll();
    }
  });

  backdrop.addEventListener("click", closeAll);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAll();
  });

  mobileToggle?.addEventListener("click", () => {
    nav.classList.toggle("show");
    if (!nav.classList.contains("show")) closeAll();
  });
})();
