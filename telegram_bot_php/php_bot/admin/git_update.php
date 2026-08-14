<?php
/**
 * One-click pull of selected bot PHP files from GitHub (admin only).
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$branch = 'cursor/telegram-bot-architecture-5168';
$base = 'https://raw.githubusercontent.com/petersany325/petersany325/' . rawurlencode($branch) . '/telegram_bot_php/php_bot/';
$files = array(
    'src/Handlers/MessageRouter.php',
    'src/Handlers/CallbackRouter.php',
    'src/Services/SupportFormService.php',
    'src/Services/ExtraMenusService.php',
    'src/Services/UserOptionsService.php',
    'src/Services/LicenseFlowService.php',
    'src/BotKernel.php',
    'src/Support/Presenter.php',
    'reply_buttons.php',
    'i18n_world.php',
    'bootstrap.php',
    'settings_lib.php',
    'plugins/SmartI18n/plugin.php',
    'admin/settings.php',
    'admin/layout_header.php',
    'admin/user_options.php',
    'admin/receipts.php',
    'admin/git_update.php',
);

$report = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pull') {
    $botRoot = dirname(__DIR__);
    foreach ($files as $rel) {
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
        if (strpos((string)$body, '<?php') === false && substr($rel, -4) === '.php') {
            $report[] = array('err', $rel . ' — not a PHP file');
            continue;
        }
        $dest = $botRoot . '/' . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $report[] = array('err', $rel . ' — cannot create directory');
            continue;
        }
        if (@file_put_contents($dest, $body) === false) {
            $report[] = array('err', $rel . ' — write failed');
            continue;
        }
        $report[] = array('ok', $rel . ' — updated (' . strlen((string)$body) . ' bytes)');
    }
    flash('ok', 'Git update finished. See report below.');
}

$pageTitle = 'Git Update';
$active = 'health';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <h2>Update bot files from GitHub</h2>
  <p class="muted">Branch: <code><?= e($branch) ?></code></p>
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
