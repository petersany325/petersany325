(() => {
  const tabs = Array.from(document.querySelectorAll(".app-tab"));
  const setActive = () => {
    const hash = (location.hash || "#top").replace("#", "");
    tabs.forEach((tab) => {
      const id = (tab.getAttribute("href") || "").replace("#", "") || "top";
      tab.classList.toggle("is-active", id === hash || (hash === "top" && id === "top"));
    });
  };
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("is-active"));
      tab.classList.add("is-active");
    });
  });
  window.addEventListener("hashchange", setActive);
  setActive();

  // Install prompt
  let deferred;
  const btn = document.getElementById("appInstallBtn");
  const toast = document.getElementById("appToast");
  window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferred = e;
    if (btn) btn.hidden = false;
    if (toast && !sessionStorage.getItem("pwaToastShown")) {
      toast.hidden = false;
      sessionStorage.setItem("pwaToastShown", "1");
      setTimeout(() => {
        toast.hidden = true;
      }, 5000);
    }
  });
  btn?.addEventListener("click", async () => {
    if (!deferred) return;
    deferred.prompt();
    await deferred.userChoice;
    deferred = null;
    btn.hidden = true;
  });

  // iOS tip
  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  const isStandalone = window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone;
  if (isIos && !isStandalone && toast && !sessionStorage.getItem("pwaToastShown")) {
    toast.textContent = "در Safari از Share → Add to Home Screen وب‌اپ را نصب کنید.";
    toast.hidden = false;
    sessionStorage.setItem("pwaToastShown", "1");
    setTimeout(() => {
      toast.hidden = true;
    }, 6000);
  }
})();
