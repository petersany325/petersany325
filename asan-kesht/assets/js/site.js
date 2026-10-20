(function () {
  var toggle = document.querySelector(".menu-btn");
  var nav = document.querySelector(".nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && nav.classList.contains("is-open")) {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
      }
    });
  }
  document.querySelectorAll(".nav-more").forEach(function (button) {
    button.addEventListener("click", function () {
      var item = button.closest(".nav-item");
      if (!item) {
        return;
      }
      var open = item.classList.toggle("is-open");
      button.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });

  var desktop = window.matchMedia("(min-width: 961px)");
  var closeTimer = 0;
  function hoverMode(item) {
    var menu = item.closest(".nav");
    return desktop.matches && menu && menu.getAttribute("data-open") !== "click";
  }
  function hold(item) {
    if (!hoverMode(item)) {
      return;
    }
    window.clearTimeout(closeTimer);
    document.querySelectorAll(".nav-item.is-hover").forEach(function (other) {
      if (other !== item) {
        other.classList.remove("is-hover");
      }
    });
    item.classList.add("is-hover");
  }
  function release(item) {
    if (!hoverMode(item)) {
      return;
    }
    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () {
      item.classList.remove("is-hover");
    }, 420);
  }
  document.querySelectorAll(".nav-item").forEach(function (item) {
    item.addEventListener("mouseenter", function () {
      hold(item);
    });
    item.addEventListener("mouseleave", function () {
      release(item);
    });
  });
  desktop.addEventListener("change", function () {
    window.clearTimeout(closeTimer);
    document.querySelectorAll(".nav-item.is-hover").forEach(function (item) {
      item.classList.remove("is-hover");
    });
  });
})();
