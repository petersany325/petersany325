<?php
/** @var array $values */
/** @var array $home */
/** @var list<array{label:string,href:string}> $links1 */
/** @var list<array{label:string,href:string}> $links2 */
$blocks = $home['blocks'];
$catalog = null;
foreach ($blocks as $block) {
    if ((string) ($block['type'] ?? '') === 'catalog') {
        $catalog = is_array($block['data'] ?? null) ? $block['data'] : [];
        break;
    }
}
$labels = function_exists('pb_definitions') ? pb_definitions() : [];
$items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
?>
<p class="muted">ذخیره همین صفحه، صفحه اول و فوتر سایت را همان لحظه عوض می‌کند. ترتیب بخش‌ها با عدد کوچک‌تر بالاتر می‌آید. برداشتن «نمایان» بخش را از صفحه اول پنهان می‌کند و متنش را پاک نمی‌کند.</p>
<form method="post" action="<?= e(url('/admin/layout')) ?>" class="stack">
  <?= csrf_field() ?>
  <fieldset>
    <legend>ساختار صفحه اول</legend>
    <label>چیدمان
      <select name="structure">
        <?php foreach (['full' => 'تمام‌عرض، ویترین سه کارت', 'focus' => 'متمرکز، بخش‌های بعدی باریک‌تر', 'stack' => 'کارت‌ها زیر هم'] as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= ($values['structure'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="check"><input type="checkbox" name="notice_on" <?= ($values['notice_on'] ?? '0') === '1' ? 'checked' : '' ?>> نوار اطلاع بالای صفحه اول</label>
    <label>متن نوار <input name="notice_text" maxlength="180" value="<?= e((string) ($values['notice_text'] ?? '')) ?>"></label>
    <div class="grid">
      <label>متن دکمه نوار <input name="notice_label" maxlength="40" value="<?= e((string) ($values['notice_label'] ?? '')) ?>"></label>
      <label>پیوند دکمه <input name="notice_url" maxlength="240" value="<?= e((string) ($values['notice_url'] ?? '')) ?>" placeholder="/contact"></label>
    </div>
  </fieldset>
  <?php if (is_array($catalog)): ?>
    <fieldset>
      <legend>متن ویترین</legend>
      <label>شعار کوتاه <input name="catalog_kicker" maxlength="120" value="<?= e((string) ($catalog['kicker'] ?? '')) ?>"></label>
      <label>عنوان <input name="catalog_title" maxlength="180" value="<?= e((string) ($catalog['title'] ?? '')) ?>"></label>
      <label>متن <textarea name="catalog_text"><?= e((string) ($catalog['text'] ?? '')) ?></textarea></label>
      <div class="grid">
        <label>متن دکمه <input name="catalog_button_label" maxlength="80" value="<?= e((string) ($catalog['button_label'] ?? '')) ?>"></label>
        <label>پیوند دکمه <input name="catalog_button_url" maxlength="240" value="<?= e((string) ($catalog['button_url'] ?? '')) ?>"></label>
      </div>
      <?php foreach ([0, 1, 2] as $slot): ?>
        <?php $item = is_array($items[$slot] ?? null) ? $items[$slot] : ['title' => '', 'text' => '']; ?>
        <div class="grid">
          <label>کارت <?= e(fa_digits((string) ($slot + 1))) ?> عنوان <input name="catalog_items[<?= e((string) $slot) ?>][title]" maxlength="80" value="<?= e((string) ($item['title'] ?? '')) ?>"></label>
          <label>توضیح <input name="catalog_items[<?= e((string) $slot) ?>][text]" maxlength="180" value="<?= e((string) ($item['text'] ?? '')) ?>"></label>
        </div>
      <?php endforeach; ?>
    </fieldset>
  <?php else: ?>
    <p class="muted">بلوک ویترین روی صفحه اول نیست. از صفحه‌ساز می‌توان آن را اضافه کرد.</p>
  <?php endif; ?>
  <fieldset>
    <legend>بخش‌های صفحه اول</legend>
    <?php if ($blocks === []): ?>
      <p>صفحه اول هنوز ساخته نشده است.</p>
    <?php endif; ?>
    <?php foreach ($blocks as $index => $block): ?>
      <?php
        $type = (string) ($block['type'] ?? '');
        $title = (string) ($block['data']['title'] ?? '');
        if ($title === '' && $type === 'catalog') {
            $title = (string) ($block['data']['kicker'] ?? 'ویترین');
        }
        $typeLabel = (string) ($labels[$type]['label'] ?? $type);
      ?>
      <div class="grid">
        <strong><?= e($typeLabel) ?><?= $title !== '' ? ' — ' . e($title) : '' ?></strong>
        <label>ترتیب <input name="blocks[<?= e((string) $index) ?>][order]" value="<?= e((string) $index) ?>"></label>
        <label class="check"><input type="checkbox" name="blocks[<?= e((string) $index) ?>][show]" <?= empty($block['hidden']) ? 'checked' : '' ?>> نمایان</label>
      </div>
    <?php endforeach; ?>
  </fieldset>
  <fieldset>
    <legend>فوتر</legend>
    <label>معرفی، خالی یعنی شعار قالب <textarea name="footer_about"><?= e((string) ($values['footer_about'] ?? '')) ?></textarea></label>
    <label>یادداشت پایین فوتر <input name="footer_note" maxlength="240" value="<?= e((string) ($values['footer_note'] ?? '')) ?>"></label>
    <label class="check"><input type="checkbox" name="footer_show_menu" <?= ($values['footer_show_menu'] ?? '1') === '1' ? 'checked' : '' ?>> پیوندهای منو در فوتر بیاید</label>
    <div class="grid">
      <div>
        <label>عنوان ستون اول <input name="footer_col1" maxlength="40" value="<?= e((string) ($values['footer_col1'] ?? '')) ?>"></label>
        <?php foreach ($links1 as $index => $link): ?>
          <div class="grid">
            <label>عنوان <input name="links1[<?= e((string) $index) ?>][label]" maxlength="40" value="<?= e($link['label']) ?>"></label>
            <label>پیوند <input name="links1[<?= e((string) $index) ?>][href]" maxlength="240" value="<?= e($link['href']) ?>" placeholder="/machines"></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div>
        <label>عنوان ستون دوم <input name="footer_col2" maxlength="40" value="<?= e((string) ($values['footer_col2'] ?? '')) ?>"></label>
        <?php foreach ($links2 as $index => $link): ?>
          <div class="grid">
            <label>عنوان <input name="links2[<?= e((string) $index) ?>][label]" maxlength="40" value="<?= e($link['label']) ?>"></label>
            <label>پیوند <input name="links2[<?= e((string) $index) ?>][href]" maxlength="240" value="<?= e($link['href']) ?>" placeholder="/contact"></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </fieldset>
  <button type="submit">ذخیره و نمایش روی سایت</button>
</form>
