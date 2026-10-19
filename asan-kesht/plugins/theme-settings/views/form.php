<?php
/** @var array $values */
/** @var array $presets */
/** @var string $active */
?>
<form method="post" action="<?= e(url('/admin/theme')) ?>" class="stack theme-form">
  <?= csrf_field() ?>
  <label>نام سایت <input name="site_name" required value="<?= e($values['site_name']) ?>"></label>
  <label>شعار <input name="tagline" value="<?= e($values['tagline']) ?>"></label>
  <label>تلفن <input name="phone" value="<?= e($values['phone']) ?>"></label>
  <label>نشانی <input name="address" value="<?= e($values['address']) ?>"></label>
  <label>ایمیل <input name="email" value="<?= e($values['email']) ?>"></label>
  <fieldset class="theme-pick">
    <legend>تم سایت</legend>
    <p class="muted">قالب کارخانه حالت اولیه است. با انتخاب آن، رنگ‌ها به همین قالب برمی‌گردند.</p>
    <?php foreach ($presets as $id => $preset): ?>
      <label class="theme-card">
        <input type="radio" name="theme" value="<?= e($id) ?>"<?= $active === $id ? ' checked' : '' ?> data-oxide="<?= e($preset['oxide']) ?>" data-ink="<?= e($preset['ink']) ?>" data-paper="<?= e($preset['paper']) ?>" data-dark="<?= e($preset['dark']) ?>">
        <span class="theme-swatch" style="background: linear-gradient(135deg, <?= e($preset['dark']) ?> 0 46%, <?= e($preset['oxide']) ?> 46% 72%, <?= e($preset['paper']) ?> 72%)"></span>
        <span><b><?= e($preset['name']) ?></b><small><?= e($preset['note']) ?></small></span>
      </label>
    <?php endforeach; ?>
    <label class="theme-card">
      <input type="radio" name="theme" value="custom"<?= $active === 'custom' ? ' checked' : '' ?>>
      <span class="theme-swatch theme-swatch-custom"></span>
      <span><b>رنگ دلخواه</b><small>چهار رنگ پایین</small></span>
    </label>
  </fieldset>
  <div class="grid">
    <label>رنگ تأکید <input type="color" name="color_oxide" value="<?= e(hex_color((string) $values['color_oxide'], '#d24a1a')) ?>"></label>
    <label>رنگ متن <input type="color" name="color_ink" value="<?= e(hex_color((string) $values['color_ink'], '#161814')) ?>"></label>
    <label>رنگ زمینه <input type="color" name="color_paper" value="<?= e(hex_color((string) $values['color_paper'], '#f3efe4')) ?>"></label>
    <label>رنگ تیره <input type="color" name="color_dark" value="<?= e(hex_color((string) $values['color_dark'], '#121410')) ?>"></label>
  </div>
  <button type="submit">ذخیره قالب</button>
</form>
<script>
(function () {
  var form = document.querySelector(".theme-form");
  if (!form) {
    return;
  }
  var custom = form.querySelector('input[name="theme"][value="custom"]');
  form.querySelectorAll('input[name="theme"]').forEach(function (radio) {
    radio.addEventListener("change", function () {
      ["oxide", "ink", "paper", "dark"].forEach(function (key) {
        var source = radio.getAttribute("data-" + key);
        var field = form.querySelector('[name="color_' + key + '"]');
        if (source && field) {
          field.value = source;
        }
      });
    });
  });
  form.querySelectorAll('input[type="color"]').forEach(function (input) {
    input.addEventListener("input", function () {
      if (custom) {
        custom.checked = true;
      }
    });
  });
})();
</script>
