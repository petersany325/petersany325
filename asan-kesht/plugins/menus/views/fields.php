<?php
/** @var list<array<string, mixed>> $rows */
/** @var array<string, mixed>|null $item */
$item = $item ?? [];
$currentId = (int) ($item['id'] ?? 0);
$blocked = $currentId > 0 ? array_merge([$currentId], menu_descendant_ids($rows, $currentId)) : [];
?>
<div class="grid">
  <label>عنوان <input name="label" required maxlength="80" value="<?= e((string) ($item['label'] ?? '')) ?>"></label>
  <label>پیوند <input name="href" maxlength="240" value="<?= e((string) ($item['href'] ?? '')) ?>" placeholder="/machines یا https://"></label>
  <label>شرح کوتاه <input name="description" maxlength="180" value="<?= e((string) ($item['description'] ?? '')) ?>"></label>
  <label>جایگاه
    <select name="parent_id">
      <option value="0">منوی اصلی</option>
      <?php foreach ($rows as $row): ?>
        <?php
          $rowId = (int) $row['id'];
          if (in_array($rowId, $blocked, true) || menu_depth($rows, $rowId) > 1) {
              continue;
          }
        ?>
        <option value="<?= e((string) $rowId) ?>" <?= (int) ($item['parent_id'] ?? 0) === $rowId ? 'selected' : '' ?>>
          <?= e(str_repeat('– ', menu_depth($rows, $rowId)) . (string) $row['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>ترتیب <input name="sort_order" value="<?= e((string) ($item['sort_order'] ?? 0)) ?>"></label>
</div>
<label class="check"><input type="checkbox" name="visible" <?= !isset($item['id']) || (int) ($item['visible'] ?? 0) === 1 ? 'checked' : '' ?>> نمایان باشد</label>
<label class="check"><input type="checkbox" name="new_tab" <?= (int) ($item['new_tab'] ?? 0) === 1 ? 'checked' : '' ?>> در زبانه تازه باز شود</label>
<label class="check"><input type="checkbox" name="mega" <?= (int) ($item['mega'] ?? 0) === 1 ? 'checked' : '' ?>> اگر در منوی اصلی است، پنل مگا باز شود</label>
