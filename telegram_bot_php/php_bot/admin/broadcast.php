<?php
/**
 * Broadcast message to Telegram users (filter by language).
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}

$pdo = db();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'send') {
            $text = trim((string)($_POST['message'] ?? ''));
            $lang = trim((string)($_POST['lang'] ?? 'all'));
            $limit = (int)($_POST['limit'] ?? 500);
            if ($limit < 1) $limit = 100;
            if ($limit > 5000) $limit = 5000;
            if ($text === '') {
                throw new RuntimeException('Message is required.');
            }
            if ($lang !== 'all' && $lang !== '') {
                $st = $pdo->prepare('SELECT telegram_id FROM users WHERE lang=? ORDER BY id DESC LIMIT ' . (int)$limit);
                $st->execute(array($lang));
            } else {
                $st = $pdo->query('SELECT telegram_id FROM users ORDER BY id DESC LIMIT ' . (int)$limit);
            }
            $ids = $st->fetchAll(PDO::FETCH_COLUMN);
            $ok = 0;
            $fail = 0;
            foreach ($ids as $tid) {
                try {
                    $res = tg_api('sendMessage', array(
                        'chat_id' => (int)$tid,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ));
                    if (!empty($res['ok'])) {
                        $ok++;
                    } else {
                        $fail++;
                    }
                } catch (Throwable $e) {
                    $fail++;
                }
                usleep(35000); // ~28 msg/sec soft rate limit
            }
            $result = array('ok' => $ok, 'fail' => $fail, 'total' => count($ids));
            flash('ok', "Broadcast done: {$ok} sent, {$fail} failed (of " . count($ids) . ').');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    if ($action === 'send' && empty($result)) {
        header('Location: broadcast.php');
        exit;
    }
}

$langs = array();
try {
    $langs = get_languages(false);
} catch (Throwable $e) {}
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$pageTitle = 'Broadcast';
$active = 'broadcast';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel" style="margin-bottom:16px">
  <p class="muted" style="margin:0">
    Send an HTML message to bot users. Total users: <b><?= (int)$userCount ?></b>.
    Use carefully — Telegram rate-limits spam.
  </p>
</div>

<div class="card panel">
  <h2>Compose</h2>
  <form method="post" class="stack" onsubmit="return confirm('Send this broadcast now?')">
    <input type="hidden" name="action" value="send">
    <label>Language filter</label>
    <select name="lang">
      <option value="all">All languages</option>
      <?php foreach ($langs as $l): ?>
        <option value="<?= e($l['code']) ?>"><?= e(($l['flag'] ?? '') . ' ' . $l['native_name'] . ' (' . $l['code'] . ')') ?></option>
      <?php endforeach; ?>
    </select>
    <label>Max recipients</label>
    <input type="number" name="limit" value="500" min="1" max="5000">
    <label>Message (HTML allowed: &lt;b&gt; &lt;i&gt; &lt;code&gt;)</label>
    <textarea name="message" rows="8" required placeholder="Example:&#10;<b>HDD-Land update</b>&#10;New SeDiv training available..."></textarea>
    <button class="btn" type="submit" style="margin-top:12px">Send Broadcast</button>
  </form>
  <?php if ($result): ?>
    <p class="muted" style="margin-top:14px">Last run: <?= (int)$result['ok'] ?> ok / <?= (int)$result['fail'] ?> fail / <?= (int)$result['total'] ?> targeted</p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
