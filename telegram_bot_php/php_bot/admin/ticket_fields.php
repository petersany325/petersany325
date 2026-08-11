<?php
/**
 * Smart Ticket Fields — define identity + questions asked in the bot.
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Services\TicketFieldsService;

$fields = TicketFieldsService::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_all') {
            $keys = $_POST['key'] ?? array();
            $types = $_POST['type'] ?? array();
            $ens = $_POST['en'] ?? array();
            $fas = $_POST['fa'] ?? array();
            $reqs = $_POST['required'] ?? array();
            $always = $_POST['ask_always'] ?? array();
            $new = array();
            $n = is_array($keys) ? count($keys) : 0;
            for ($i = 0; $i < $n; $i++) {
                $key = preg_replace('/[^a-z0-9_]/i', '_', trim((string)($keys[$i] ?? '')));
                $type = strtolower(trim((string)($types[$i] ?? 'text')));
                $en = trim((string)($ens[$i] ?? ''));
                $fa = trim((string)($fas[$i] ?? ''));
                if ($key === '' || $en === '') {
                    continue;
                }
                if (!in_array($type, TicketFieldsService::TYPES, true)) {
                    $type = 'text';
                }
                $new[] = array(
                    'key' => $key,
                    'type' => $type,
                    'en' => $en,
                    'fa' => $fa !== '' ? $fa : $en,
                    'required' => !empty($reqs[$i]),
                    'ask_always' => !empty($always[$i]),
                );
            }
            if (!$new) {
                throw new RuntimeException('Keep at least one field.');
            }
            TicketFieldsService::save($new);
            flash('ok', 'Ticket fields saved. Bot will use them on next ticket.');
        } elseif ($action === 'reset_defaults') {
            TicketFieldsService::save(TicketFieldsService::defaults());
            flash('ok', 'Default smart fields restored.');
        } elseif ($action === 'add_field') {
            $fields = TicketFieldsService::all();
            $fields[] = array(
                'key' => 'custom_' . (count($fields) + 1),
                'type' => 'text',
                'en' => 'New question',
                'fa' => 'سؤال جدید',
                'required' => true,
                'ask_always' => false,
            );
            TicketFieldsService::save($fields);
            flash('ok', 'Field added — edit labels below and Save.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: ticket_fields.php');
    exit;
}

$fields = TicketFieldsService::all();
$pageTitle = 'Ticket Fields';
$active = 'ticket_fields';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <h2>🧠 Smart Ticket Fields</h2>
  <p class="muted">
    این فیلدها هنگام زدن تیکت / پشتیبانی فنی از مشتری پرسیده می‌شوند.
    ترتیب لیست = ترتیب سؤال در بات.
    انواع: <code>name</code> نام · <code>phone</code> موبایل · <code>id</code> کد مشتری/لایسنس · <code>text</code> سؤال · <code>message</code> شرح مشکل
  </p>
  <div class="actions" style="margin:12px 0;flex-wrap:wrap">
    <form method="post" style="display:inline"><input type="hidden" name="action" value="add_field"><button class="btn sm" type="submit">＋ Add field</button></form>
    <form method="post" style="display:inline" onsubmit="return confirm('Restore defaults?')"><input type="hidden" name="action" value="reset_defaults"><button class="btn sm secondary" type="submit">Restore defaults</button></form>
    <a class="btn sm secondary" href="tickets.php">← Tickets</a>
    <a class="btn sm secondary" href="settings.php?tab=support">Support intro / links</a>
  </div>

  <form method="post">
    <input type="hidden" name="action" value="save_all">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Key</th>
            <th>Type</th>
            <th>Label EN</th>
            <th>Label FA</th>
            <th>Required</th>
            <th>Ask every time</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($fields as $i => $f): ?>
          <tr>
            <td><input name="key[]" value="<?= e((string)$f['key']) ?>" style="min-width:110px"></td>
            <td>
              <select name="type[]">
                <?php foreach (TicketFieldsService::TYPES as $t): ?>
                  <option value="<?= e($t) ?>" <?= $f['type']===$t?'selected':'' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input name="en[]" value="<?= e((string)$f['en']) ?>" style="min-width:160px"></td>
            <td><input name="fa[]" value="<?= e((string)$f['fa']) ?>" style="min-width:160px"></td>
            <td style="text-align:center"><input type="checkbox" name="required[<?= (int)$i ?>]" value="1" <?= !empty($f['required'])?'checked':'' ?>></td>
            <td style="text-align:center"><input type="checkbox" name="ask_always[<?= (int)$i ?>]" value="1" <?= !empty($f['ask_always'])?'checked':'' ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="muted" style="margin-top:10px;font-size:.85rem">
      برای حذف یک فیلد، Key آن را خالی کنید و Save بزنید.
      توصیه: name + phone + id را <b>Ask every time</b> بگذارید تا مشخصات هر تیکت تازه باشد.
    </p>
    <button class="btn" type="submit" style="margin-top:12px">Save Ticket Fields</button>
  </form>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
