<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = ?');
$stmt->execute([$id]);
$ticket = $stmt->fetch();
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
            $adminTg = bot_config()['admin_ids'][0] ?? 0;
            $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,1,?)')
                ->execute([$id, $adminTg, $text]);
            send_message((int)$ticket['user_id'], "💬 <b>Reply to Ticket #{$id}:</b>\n\n" . htmlspecialchars($text));
            flash('ok', 'Reply sent to Telegram user.');
        } elseif ($action === 'close') {
            $pdo->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute([$id]);
            send_message((int)$ticket['user_id'], "🔒 Your ticket #{$id} has been closed.");
            flash('ok', 'Ticket closed.');
        } elseif ($action === 'reopen') {
            $pdo->prepare("UPDATE tickets SET status='open' WHERE id=?")->execute([$id]);
            flash('ok', 'Ticket reopened.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: ticket_view.php?id=' . $id);
    exit;
}

$msgs = $pdo->prepare('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC');
$msgs->execute([$id]);
$messages = $msgs->fetchAll();

$pageTitle = 'Ticket #' . $id;
$active = 'tickets';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <div class="actions" style="margin-bottom:12px">
    <a class="btn sm secondary" href="tickets.php">← Back</a>
    <?php if ($ticket['status'] === 'open'): ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="close">
        <button class="btn sm danger" type="submit">Close Ticket</button>
      </form>
    <?php else: ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="reopen">
        <button class="btn sm secondary" type="submit">Reopen</button>
      </form>
    <?php endif; ?>
  </div>

  <p>
    <span class="badge <?= e($ticket['status']) ?>"><?= e($ticket['status']) ?></span>
    &nbsp; User: <code><?= e((string)$ticket['user_id']) ?></code>
    &nbsp; Created: <span class="muted"><?= e((string)$ticket['created_at']) ?></span>
  </p>
  <h2><?= e((string)$ticket['subject']) ?></h2>

  <div style="margin:18px 0;display:flex;flex-direction:column;gap:10px">
    <?php foreach ($messages as $m): ?>
      <div style="padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:<?= $m['is_admin'] ? 'rgba(47,128,237,.12)' : 'rgba(255,255,255,.03)' ?>">
        <div class="muted" style="font-size:.8rem;margin-bottom:6px">
          <?= $m['is_admin'] ? 'Admin' : 'User' ?> · <?= e((string)$m['created_at']) ?>
        </div>
        <div><?= nl2br(e((string)$m['text'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($ticket['status'] === 'open'): ?>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="reply">
    <label>Reply to user on Telegram</label>
    <textarea name="text" required placeholder="Write your reply..."></textarea>
    <button class="btn" type="submit">Send Reply</button>
  </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
