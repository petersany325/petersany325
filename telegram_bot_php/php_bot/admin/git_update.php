<?php
/**
 * One-click pull of selected bot PHP files from GitHub (admin only).
 * SAFE: never touches config.local.php, menus DB, FAQs, or user data.
 * New menus are additive via ensure_professional_menus() — customs are preserved.
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$branch = 'cursor/telegram-bot-architecture-5168';
$base = 'https://raw.githubusercontent.com/petersany325/petersany325/' . rawurlencode($branch) . '/telegram_bot_php/php_bot/';

/** Code-only paths. Never include config.local.php or storage secrets. */
$files = array(
    'src/Handlers/MessageRouter.php',
    'src/Handlers/CallbackRouter.php',
    'src/Middleware/EnsureUserMiddleware.php',
    'src/Services/SupportFormService.php',
    'src/Services/ExtraMenusService.php',
    'src/Services/UserOptionsService.php',
    'src/Services/LicenseFlowService.php',
    'src/BotKernel.php',
    'src/Support/Presenter.php',
    'reply_buttons.php',
    'i18n_world.php',
    'menu_faq.php',
    'bootstrap.php',
    'settings_lib.php',
    'plugins/SmartI18n/plugin.php',
    'plugins/HealthRepair/plugin.php',
    'admin/settings.php',
    'admin/layout_header.php',
    'admin/user_options.php',
    'admin/receipts.php',
    'admin/git_update.php',
    'storage/licenses/.htaccess',
);

$blocked = array('config.local.php', 'config.php', '.env', 'error.log');

$report = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pull') {
    $botRoot = dirname(__DIR__);
    foreach ($files as $rel) {
        $baseName = basename($rel);
        if (in_array($baseName, $blocked, true) || strpos($rel, 'config.local') !== false) {
            $report[] = array('err', $rel . ' — blocked (secrets/config never overwritten)');
            continue;
        }
        $url = $base . implode('/', array_map('rawurlencode', explode('/', $rel)));
        $body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'HDDLand-Admin-Updater',
            ));
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code >= 400) {
                $body = false;
            }
        } else {
            $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => 60,
                    'header' => "User-Agent: HDDLand-Admin-Updater\r\n",
                ),
            ));
            $body = @file_get_contents($url, false, $ctx);
        }
        if ($body === false || strlen((string)$body) < 20) {
            $report[] = array('err', $rel . ' — download failed');
            continue;
        }
        if (substr($rel, -4) === '.php' && strpos((string)$body, '<?php') === false) {
            $report[] = array('err', $rel . ' — not a PHP file');
            continue;
        }
        $dest = $botRoot . '/' . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $report[] = array('err', $rel . ' — cannot create directory');
            continue;
        }
        // Backup existing PHP before overwrite (keep last copy only)
        if (is_file($dest) && substr($rel, -4) === '.php') {
            @copy($dest, $dest . '.bak');
        }
        if (@file_put_contents($dest, $body) === false) {
            $report[] = array('err', $rel . ' — write failed');
            continue;
        }
        $report[] = array('ok', $rel . ' — updated (' . strlen((string)$body) . ' bytes)');
    }
    // Additive menu heal — inserts missing items only; never deletes customs
    try {
        if (function_exists('ensure_professional_menus')) {
            $n = ensure_professional_menus();
            $report[] = array('ok', 'menus — additive sync (+' . (int)$n . ' new, customs kept)');
        }
    } catch (Throwable $e) {
        $report[] = array('err', 'menus — ' . $e->getMessage());
    }
    flash('ok', 'Git update finished. config.local.php and menu DB customs were NOT touched.');
}

$pageTitle = 'Git Update';
$active = 'health';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <h2>Update bot files from GitHub</h2>
  <p class="muted">Branch: <code><?= e($branch) ?></code></p>
  <p class="muted">Safe pull: PHP code only. <b>Does not</b> overwrite <code>config.local.php</code>, bot token, menus DB labels, FAQs, or users. New menu items are added without removing yours.</p>
  <form method="post">
    <input type="hidden" name="action" value="pull">
    <button type="submit" class="btn">Pull latest PHP fixes</button>
  </form>
  <?php if ($report): ?>
    <ul style="margin-top:16px;line-height:1.8">
      <?php foreach ($report as $row): ?>
        <li style="color:<?= $row[0] === 'ok' ? '#86efac' : '#fca5a5' ?>"><?= e($row[1]) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
