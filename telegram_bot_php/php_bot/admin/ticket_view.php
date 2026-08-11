<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Repositories\TicketRepository;
use HddLand\Bot\Services\SupportFormService;
use HddLand\Bot\Services\TicketFieldsService;

$id = (int)($_GET['id'] ?? 0);
\HddLand\Bot\Services\SupportFormService::ensureSchema();
$ticket = TicketRepository::find($id);
if (!$ticket) {
    flash('err', 'Ticket not found.');
    header('Location: tickets.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'reply') {
            $text = trim((string)($_POST['text'] ?? ''));
            if ($text === '') {
                throw new RuntimeException('Reply cannot be empty.');
            }
            $adminTg = (int)(bot_config()['admin_ids'][0] ?? 0);
            TicketRepository::addAdminReply($id, $adminTg, $text);
            $langHint = 'en';
            $meta = json_decode((string)($ticket['meta_json'] ?? ''), true);
            if (is_array($meta) && ($meta['lang'] ?? '') === 'fa') {
                $langHint = 'fa';
            }
            $notify = $langHint === 'fa'
                ? "💬 <b>پاسخ پشتیبانی به تیکت #{$id}:</b>\n\n" . htmlspecialchars($text) . "\n\nبرای پاسخ دوباره دکمه زیر را بزنید."
                : "💬 <b>Support reply to Ticket #{$id}:</b>\n\n" . htmlspecialchars($text) . "\n\nTap below to reply.";
            send_message((int)$ticket['user_id'], $notify, array('inline_keyboard' => array(
                array(array('text' => $langHint === 'fa' ? '💬 پاسخ' : '💬 Reply', 'callback_data' => 'ticket_reply:' . $id)),
                array(array('text' => $langHint === 'fa' ? '🎫 مشاهده تیکت' : '🎫 View ticket', 'callback_data' => 'ticket:' . $id)),
            )));
            flash('ok', 'Reply sent. Status → answered.');
        } elseif ($action === 'status') {
            $st = (string)($_POST['status'] ?? '');
            TicketRepository::setStatus($id, $st);
            if ($st === 'closed') {
                send_message((int)$ticket['user_id'], "🔒 Your ticket #{$id} has been closed.");
            } elseif ($st === 'open' || $st === 'waiting') {
                // soft notify on reopen/waiting optional
            }
            flash('ok', 'Status updated to ' . $st);
        } elseif ($action === 'close') {
            TicketRepository::close($id);
            send_message((int)$ticket['user_id'], "🔒 Your ticket #{$id} has been closed.");
            flash('ok', 'Ticket closed.');
        } elseif ($action === 'reopen') {
            TicketRepository::setStatus($id, 'open');
            send_message((int)$ticket['user_id'], "🔓 Your ticket #{$id} was reopened. You can reply again.");
            flash('ok', 'Ticket reopened.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: ticket_view.php?id=' . $id);
    exit;
}

$ticket = TicketRepository::find($id);
$messages = TicketRepository::messages($id);
$meta = json_decode((string)($ticket['meta_json'] ?? ''), true);
if (!is_array($meta)) {
    $meta = array();
}
$fieldMap = array();
foreach (TicketFieldsService::all() as $f) {
    $fieldMap[$f['key']] = $f;
}

$pageTitle = 'Ticket #' . $id;
$active = 'tickets';
require __DIR__ . '/layout_header.php';
?>
<div class="row2">
  <div class="card panel">
    <div class="actions" style="margin-bottom:12px;flex-wrap:wrap">
      <a class="btn sm secondary" href="tickets.php">← Back</a>
      <a class="btn sm secondary" href="ticket_fields.php">🧠 Fields</a>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="status">
        <select name="status" onchange="this.form.submit()">
          <?php foreach (array('open','answered','waiting','closed') as $st): ?>
            <option value="<?= e($st) ?>" <?= $ticket['status']===$st?'selected':'' ?>><?= e($st) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="close">
          <button class="btn sm danger" type="submit">Close</button>
        </form>
      <?php else: ?>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="reopen">
          <button class="btn sm secondary" type="submit">Reopen</button>
        </form>
      <?php endif; ?>
    </div>

    <p>
      <span class="badge <?= e((string)$ticket['status']) ?>"><?= e(SupportFormService::statusLabel((string)$ticket['status'])) ?></span>
      &nbsp; TG: <code><?= e((string)$ticket['user_id']) ?></code>
      &nbsp; Created: <span class="muted"><?= e((string)$ticket['created_at']) ?></span>
    </p>
    <h2><?= e((string)$ticket['subject']) ?></h2>

    <div style="margin:14px 0;padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:rgba(255,255,255,.03)">
      <h3 style="margin:0 0 8px">🪪 Customer identity</h3>
      <p style="margin:4px 0">👤 <?= e((string)($ticket['contact_name'] ?: ($meta['contact_name'] ?? '—'))) ?></p>
      <p style="margin:4px 0">📞 <?= e((string)($ticket['phone'] ?: ($meta['phone'] ?? '—'))) ?></p>
      <p style="margin:4px 0">🆔 <?= e((string)($ticket['customer_id'] ?: ($meta['customer_id'] ?? '—'))) ?></p>
      <?php
        $answers = isset($meta['answers']) && is_array($meta['answers']) ? $meta['answers'] : array();
        $values = isset($meta['values']) && is_array($meta['values']) ? $meta['values'] : array();
        if ($answers || $values):
      ?>
        <h3 style="margin:12px 0 8px">📋 Form answers</h3>
        <ul style="margin:0;padding-left:18px">
          <?php
          $shown = array('contact_name','phone','customer_id','problem');
          foreach ($values as $k => $v) {
              if ($v === '' || in_array($k, $shown, true)) continue;
              $label = isset($fieldMap[$k]) ? $fieldMap[$k]['en'] : $k;
              echo '<li><b>' . e($label) . ':</b> ' . e((string)$v) . '</li>';
          }
          foreach ($answers as $k => $v) {
              if ($v === '' || isset($values[$k])) continue;
              $label = isset($fieldMap[$k]) ? $fieldMap[$k]['en'] : $k;
              echo '<li><b>' . e($label) . ':</b> ' . e((string)$v) . '</li>';
          }
          ?>
        </ul>
      <?php endif; ?>
    </div>

    <div style="margin:18px 0;display:flex;flex-direction:column;gap:10px">
      <?php foreach ($messages as $m): ?>
        <div style="padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:<?= !empty($m['is_admin']) ? 'rgba(47,128,237,.12)' : 'rgba(255,255,255,.03)' ?>">
          <div class="muted" style="font-size:.8rem;margin-bottom:6px">
            <?= !empty($m['is_admin']) ? '🛡️ Support' : '👤 Customer' ?> · <?= e((string)$m['created_at']) ?>
          </div>
          <div><?= nl2br(e((string)$m['text'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="reply">
      <label>Reply to customer on Telegram</label>
      <textarea name="text" required placeholder="Write your reply..." rows="5"></textarea>
      <button class="btn" type="submit">Send Reply</button>
    </form>
    <?php else: ?>
      <p class="muted">Ticket is closed. Reopen to reply.</p>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
