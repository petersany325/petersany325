(function () {
  var root = document.getElementById("pb-app");
  if (!root) return;
  var data = JSON.parse(root.getAttribute("data-page") || "{}");
  var saveUrl = root.getAttribute("data-save");
  var uploadUrl = root.getAttribute("data-upload");
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  var canvas = document.getElementById("pb-canvas");
  var msg = document.getElementById("pb-msg");

  function field(label, name, value, kind) {
    var wrap = document.createElement("label");
    wrap.className = "pb-field";
    wrap.appendChild(document.createTextNode(label));
    var el = document.createElement(kind === "textarea" ? "textarea" : kind === "select" ? "select" : "input");
    if (kind === "textarea") el.rows = 5;
    el.name = name;
    el.value = value || "";
    wrap.appendChild(el);
    return wrap;
  }

  function imageField(label, value) {
    var wrap = document.createElement("div");
    wrap.className = "pb-field";
    wrap.appendChild(document.createTextNode(label));
    var row = document.createElement("div");
    row.className = "pb-img";
    var input = document.createElement("input");
    input.value = value || "";
    input.placeholder = "images/... یا uploads/pages/...";
    var img = document.createElement("img");
    img.alt = "";
    img.src = value ? (value.indexOf("http") === 0 || value.charAt(0) === "/" ? value : "/" + value) : "";
    var btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = "آپلود عکس";
    btn.addEventListener("click", function () {
      var file = document.createElement("input");
      file.type = "file";
      file.accept = "image/*";
      file.addEventListener("change", function () {
        if (!file.files || !file.files[0]) return;
        var fd = new FormData();
        fd.append("file", file.files[0]);
        fetch(uploadUrl, { method: "POST", headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" }, body: fd })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (!j.ok) throw new Error(j.message || "آپلود ناموفق");
            input.value = j.file.path;
            img.src = j.file.url;
          })
          .catch(function (e) { flash(e.message, true); });
      });
      file.click();
    });
    row.appendChild(img);
    row.appendChild(input);
    row.appendChild(btn);
    wrap.appendChild(row);
    wrap._input = input;
    return wrap;
  }

  function itemsEditor(items, fields) {
    var box = document.createElement("div");
    function add(item) {
      var row = document.createElement("div");
      row.className = "pb-item";
      fields.forEach(function (f) {
        if (f === "image") {
          var img = imageField("عکس", item[f] || "");
          img.dataset.key = f;
          row.appendChild(img);
        } else {
          var lab = field(f === "text" ? "متن" : f === "title" ? "عنوان" : f === "url" ? "لینک" : f === "value" ? "عدد" : f === "label" ? "برچسب" : f === "cat" ? "دسته" : f, f, item[f] || "", f === "text" ? "textarea" : "input");
          row.appendChild(lab);
        }
      });
      var del = document.createElement("button");
      del.type = "button";
      del.textContent = "حذف آیتم";
      del.addEventListener("click", function () { row.remove(); });
      row.appendChild(del);
      box.appendChild(row);
    }
    (items || []).forEach(add);
    var plus = document.createElement("button");
    plus.type = "button";
    plus.textContent = "آیتم جدید";
    plus.addEventListener("click", function () { add({}); });
    box.appendChild(plus);
    box.collect = function () {
      return Array.prototype.slice.call(box.querySelectorAll(".pb-item")).map(function (row) {
        var out = {};
        row.querySelectorAll(".pb-field").forEach(function (lab) {
          if (lab._input) {
            out.image = lab._input.value;
            return;
          }
          var el = lab.querySelector("input,textarea,select");
          if (el) out[el.name] = el.value;
        });
        return out;
      });
    };
    return box;
  }

  function renderBlock(block, index) {
    var card = document.createElement("div");
    card.className = "pb-block";
    card.dataset.index = String(index);
    var head = document.createElement("div");
    head.className = "pb-blockhead";
    var title = document.createElement("h3");
    title.textContent = labelOf(block.type) + " #" + (index + 1);
    var ops = document.createElement("div");
    ops.className = "ops";
    [["بالا", -1], ["پایین", 1], ["حذف", 0]].forEach(function (op) {
      var b = document.createElement("button");
      b.type = "button";
      b.textContent = op[0];
      b.addEventListener("click", function () {
        if (op[0] === "حذف") {
          data.blocks.splice(index, 1);
        } else {
          var next = index + op[1];
          if (next < 0 || next >= data.blocks.length) return;
          var tmp = data.blocks[index];
          data.blocks[index] = data.blocks[next];
          data.blocks[next] = tmp;
        }
        draw();
      });
      ops.appendChild(b);
    });
    head.appendChild(title);
    head.appendChild(ops);
    card.appendChild(head);

    var fields = [];
    if (block.type === "hero" || block.type === "cta") {
      fields.push(field("کیکر", "kicker", block.kicker));
      fields.push(field("عنوان", "title", block.title));
      fields.push(field("متن", "text", block.text, "textarea"));
      if (block.type === "hero") fields.push(imageField("عکس هیرو", block.image));
      var ctas = document.createElement("div");
      ctas.className = "pb-row2";
      ctas.appendChild(field("دکمه ۱", "cta1_label", block.cta1_label));
      ctas.appendChild(field("لینک ۱", "cta1_url", block.cta1_url));
      ctas.appendChild(field("دکمه ۲", "cta2_label", block.cta2_label));
      ctas.appendChild(field("لینک ۲", "cta2_url", block.cta2_url));
      fields.push(ctas);
    } else if (block.type === "richtext") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(field("متن غنی (HTML ساده)", "html", block.html, "textarea"));
    } else if (block.type === "image") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(imageField("عکس", block.image));
      fields.push(field("متن جایگزین", "alt", block.alt));
      fields.push(field("زیرنویس", "caption", block.caption));
    } else if (block.type === "cards") {
      fields.push(field("عنوان بخش", "title", block.title));
      fields.push(itemsEditor(block.items || [], ["title", "text", "url", "image"]));
    } else if (block.type === "list") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(field("هر خط یک مورد", "lines", (block.items || []).join("\n"), "textarea"));
    } else if (block.type === "steps") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(itemsEditor(block.items || [], ["title", "text"]));
    } else if (block.type === "stats") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(itemsEditor(block.items || [], ["value", "label"]));
    } else if (block.type === "works") {
      fields.push(field("عنوان", "title", block.title));
      fields.push(itemsEditor(block.items || [], ["title", "cat", "url", "image"]));
    }
    fields.forEach(function (el) { card.appendChild(el); });
    card.collect = function () {
      var next = { type: block.type };
      card.querySelectorAll(":scope > .pb-field").forEach(function (lab) {
        if (lab._input) {
          next.image = lab._input.value;
          return;
        }
        var el = lab.querySelector("input,textarea,select");
        if (!el) return;
        if (el.name === "html") next.html = el.value;
        else if (el.name === "lines") next.items = el.value.split("\n").map(function (s) { return s.trim(); }).filter(Boolean);
        else next[el.name] = el.value;
      });
      var items = card.querySelector("div.pb-item") ? card.querySelector(".pb-field + div, div") : null;
      card.querySelectorAll(":scope > div").forEach(function (box) {
        if (box.collect) next.items = box.collect();
        box.querySelectorAll(".pb-field input, .pb-field textarea").forEach(function (el) {
          if (["cta1_label", "cta1_url", "cta2_label", "cta2_url"].indexOf(el.name) >= 0) next[el.name] = el.value;
        });
      });
      return next;
    };
    return card;
  }

  function labelOf(type) {
    return ({
      hero: "هیرو",
      richtext: "متن غنی",
      image: "عکس",
      cards: "کارت‌ها",
      list: "فهرست",
      steps: "مراحل",
      stats: "آمار",
      works: "نمونه کار",
      cta: "دعوت به اقدام"
    })[type] || type;
  }

  function collectMeta() {
    data.title = document.getElementById("pb-title").value;
    data.path = document.getElementById("pb-path").value;
    data.menu_label = document.getElementById("pb-menu").value;
    data.menu_group = document.getElementById("pb-group").value;
    data.status = document.getElementById("pb-status").value;
    data.seo_title = document.getElementById("pb-seo-title").value;
    data.seo_description = document.getElementById("pb-seo-desc").value;
    data.blocks = Array.prototype.slice.call(canvas.querySelectorAll(".pb-block")).map(function (card) {
      return card.collect ? card.collect() : {};
    });
    return data;
  }

  function draw() {
    canvas.innerHTML = "";
    (data.blocks || []).forEach(function (block, i) {
      canvas.appendChild(renderBlock(block, i));
    });
  }

  function flash(text, err) {
    if (!msg) return;
    msg.className = "pb-flash";
    msg.style.background = err ? "#fff1f2" : "#ecfdf5";
    msg.style.color = err ? "#be123c" : "#047857";
    msg.textContent = text;
    msg.hidden = false;
  }

  document.querySelectorAll("[data-add]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      collectMeta();
      data.blocks = data.blocks || [];
      data.blocks.push({ type: btn.getAttribute("data-add"), title: "", text: "", items: [] });
      draw();
    });
  });

  document.getElementById("pb-save").addEventListener("click", function () {
    var payload = collectMeta();
    fetch(saveUrl, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) throw new Error(j.message || "ذخیره نشد");
        data = j.page;
        flash("ذخیره شد. صفحه از منو و دکمه ویرایش همین بوم را باز می‌کند.");
        var view = document.getElementById("pb-view");
        if (view && data.path) view.href = data.path;
      })
      .catch(function (e) { flash(e.message, true); });
  });

  draw();
})();
