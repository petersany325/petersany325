<?php /** @var array $page */ /** @var array $blocks */ /** @var array $definitions */ ?>
<form method="post" action="<?= e(url('/admin/pages/' . (int) $page['id'])) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="grid">
    <label>عنوان <input name="title" required value="<?= e((string) $page['title']) ?>"></label>
    <label>نامک انگلیسی <input name="slug" required value="<?= e((string) $page['slug']) ?>" placeholder="about"></label>
    <label>وضعیت
      <select name="status">
        <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
        <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>منتشر</option>
      </select>
    </label>
    <label>ترتیب منو <input name="menu_order" value="<?= e((string) ($page['menu_order'] ?? 0)) ?>"></label>
    <label class="check"><input type="checkbox" name="in_menu" <?= (int) ($page['in_menu'] ?? 0) === 1 ? 'checked' : '' ?>> در منو</label>
  </div>
  <p class="muted">متن بلوک‌ها با ذخیره، روی همان نشانی صفحه دیده می‌شود. «در منو» صفحه منتشرشده را به منوی زنده اضافه می‌کند و اگر آیتم منو همان عنوان قبلی را داشته باشد، عنوان تازه را آنجا هم می‌نویسد. پیش‌نویس روی سایت دیده نمی‌شود.</p>
  <?php foreach ($blocks as $i => $block): ?>
    <?php $def = $definitions[$block['type']] ?? null; if (!$def) { continue; } ?>
    <fieldset>
      <legend><?= e($def['label']) ?></legend>
      <input type="hidden" name="blocks[<?= $i ?>][type]" value="<?= e((string) $block['type']) ?>">
      <label>لنگر <input name="blocks[<?= $i ?>][anchor]" value="<?= e((string) ($block['anchor'] ?? '')) ?>"></label>
      <label class="check"><input type="checkbox" name="blocks[<?= $i ?>][hidden]" <?= !empty($block['hidden']) ? 'checked' : '' ?>> پنهان</label>
      <?php foreach ($def['fields'] as $name => $field): ?>
        <?php if (($field['type'] ?? '') === 'pairs'): ?>
          <?php for ($n = 0; $n < (int) $field['count']; $n++): ?>
            <?php $item = $block['data'][$name][$n] ?? ['title' => '', 'text' => '']; ?>
            <div class="grid">
              <label><?= e($field['label']) ?> <?= e(fa_digits((string) ($n + 1))) ?>
                <input name="blocks[<?= $i ?>][data][<?= e($name) ?>][<?= $n ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>">
              </label>
              <label>شرح
                <input name="blocks[<?= $i ?>][data][<?= e($name) ?>][<?= $n ?>][text]" value="<?= e((string) ($item['text'] ?? '')) ?>">
              </label>
            </div>
          <?php endfor; ?>
        <?php elseif (($field['type'] ?? '') === 'textarea'): ?>
          <label><?= e($field['label']) ?>
            <textarea name="blocks[<?= $i ?>][data][<?= e($name) ?>]"><?= e((string) ($block['data'][$name] ?? '')) ?></textarea>
          </label>
        <?php else: ?>
          <label><?= e($field['label']) ?>
            <input name="blocks[<?= $i ?>][data][<?= e($name) ?>]" value="<?= e((string) ($block['data'][$name] ?? '')) ?>">
          </label>
        <?php endif; ?>
      <?php endforeach; ?>
      <button type="submit" name="action" value="up:<?= $i ?>">بالا</button>
      <button type="submit" name="action" value="down:<?= $i ?>">پایین</button>
      <button type="submit" name="action" value="dup:<?= $i ?>">تکثیر</button>
      <button type="submit" name="action" value="delete:<?= $i ?>">حذف</button>
    </fieldset>
  <?php endforeach; ?>
  <div class="grid">
    <label>بلوک تازه
      <select name="add_type">
        <?php foreach ($definitions as $key => $def): ?>
          <option value="<?= e($key) ?>"><?= e($def['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" name="action" value="add">افزودن بلوک</button>
  </div>
  <button type="submit" name="action" value="save">ذخیره صفحه</button>
</form>
