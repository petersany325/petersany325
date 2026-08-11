<?php
/**
 * Final Admin Settings — ALL bot options live here.
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$cfg = merge_bot_defaults_into_config(bot_config());
$tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'general';
$allowedTabs = array('general','features','messages','branding','notify','api','security','webhook');
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'general';
}

function settings_bool_post($key) {
    return isset($_POST[$key]) ? 1 : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $cfg = merge_bot_defaults_into_config(bot_config());

        if ($action === 'save_general') {
            $cfg['site_url'] = trim((string)($_POST['site_url'] ?? ''));
            $cfg['forum_url'] = trim((string)($_POST['forum_url'] ?? ''));
            $cfg['training_url'] = trim((string)($_POST['training_url'] ?? ''));
            $cfg['support_email'] = trim((string)($_POST['support_email'] ?? ''));
            $cfg['sales_email'] = trim((string)($_POST['sales_email'] ?? ''));
            $admins = trim((string)($_POST['admin_ids'] ?? ''));
            $cfg['admin_ids'] = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $admins))));
            $cfg['maintenance_mode'] = settings_bool_post('maintenance_mode');
            $cfg['maintenance_text'] = trim((string)($_POST['maintenance_text'] ?? $cfg['maintenance_text']));
            $cfg['start_with_menu'] = settings_bool_post('start_with_menu');
            save_bot_config($cfg);
            flash('ok', 'General settings saved.');
            $tab = 'general';
        } elseif ($action === 'save_features') {
            foreach (array('shop','forum','faq','tickets','prodesk','ai','language_gate','auto_faq_search') as $f) {
                $cfg['feature_' . $f] = settings_bool_post('feature_' . $f);
            }
            save_bot_config($cfg);
            flash('ok', 'Feature switches saved.');
            $tab = 'features';
        } elseif ($action === 'save_messages') {
            $keys = array(
                'website_text_en','website_text_fa','forum_text_en','forum_text_fa',
                'training_text_en','training_text_fa','shop_text_en','shop_text_fa',
                'help_text_en','help_text_fa',
            );
            foreach ($keys as $k) {
                $cfg[$k] = trim((string)($_POST[$k] ?? ''));
            }
            save_bot_config($cfg);
            flash('ok', 'Page messages saved.');
            $tab = 'messages';
        } elseif ($action === 'save_branding') {
            $cfg['bot_title'] = trim((string)($_POST['bot_title'] ?? ''));
            $cfg['bot_subtitle'] = trim((string)($_POST['bot_subtitle'] ?? ''));
            $cfg['gate_text'] = trim((string)($_POST['gate_text'] ?? ''));
            $cfg['welcome_text_en'] = trim((string)($_POST['welcome_text_en'] ?? ''));
            $cfg['welcome_text_fa'] = trim((string)($_POST['welcome_text_fa'] ?? ''));
            save_bot_config($cfg);
            flash('ok', 'Branding saved.');
            $tab = 'branding';
        } elseif ($action === 'save_notify') {
            $cfg['notify_tickets'] = settings_bool_post('notify_tickets');
            $cfg['notify_requests'] = settings_bool_post('notify_requests');
            $cfg['notify_media'] = settings_bool_post('notify_media');
            save_bot_config($cfg);
            flash('ok', 'Notification settings saved.');
            $tab = 'notify';
        } elseif ($action === 'save_api') {
            $cfg['openai_api_key'] = trim((string)($_POST['openai_api_key'] ?? ''));
            $cfg['ai_model'] = trim((string)($_POST['ai_model'] ?? 'gpt-4o-mini'));
            $cfg['ai_system_prompt'] = trim((string)($_POST['ai_system_prompt'] ?? ''));
            $cfg['weather_api_key'] = trim((string)($_POST['weather_api_key'] ?? ''));
            save_bot_config($cfg);
            flash('ok', 'API settings saved.');
            $tab = 'api';
        } elseif ($action === 'save_token') {
            $token = trim((string)($_POST['bot_token'] ?? ''));
            if ($token !== '' && strpos($token, '…') === false && strlen($token) > 20) {
                $cfg['bot_token'] = $token;
            }
            $secret = trim((string)($_POST['webhook_secret'] ?? ''));
            $cfg['webhook_secret'] = $secret;
            save_bot_config($cfg);
            flash('ok', 'Bot token / webhook secret saved.');
            $tab = 'security';
        } elseif ($action === 'password') {
            $user = trim((string)($_POST['admin_username'] ?? 'admin'));
            $current = (string)($_POST['current_password'] ?? '');
            $pass = (string)($_POST['admin_password'] ?? '');
            $pass2 = (string)($_POST['admin_password2'] ?? '');
            $sessionUser = (string)($_SESSION['hddland_admin_user'] ?? $user);

            // Verify current password against panel_users or legacy config
            $okCurrent = false;
            try {
                $st = db()->prepare('SELECT password_hash FROM panel_users WHERE username=? AND is_active=1 LIMIT 1');
                $st->execute(array($sessionUser));
                $row = $st->fetch();
                if ($row && password_verify($current, (string)$row['password_hash'])) {
                    $okCurrent = true;
                }
            } catch (Throwable $e) {}
            if (!$okCurrent) {
                $legacy = admin_cfg();
                if ($legacy['username'] === $sessionUser && $legacy['password_hash'] !== '' && password_verify($current, $legacy['password_hash'])) {
                    $okCurrent = true;
                }
            }
            if (!$okCurrent) {
                throw new RuntimeException('Current password is incorrect.');
            }
            if (strlen($pass) < 8) {
                throw new RuntimeException('Password must be at least 8 characters.');
            }
            if ($pass !== $pass2) {
                throw new RuntimeException('Passwords do not match.');
            }
            $cfg['admin_username'] = $user !== '' ? $user : 'admin';
            $cfg['admin_password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
            save_bot_config($cfg);
            // sync panel_users (update current session user, rename if needed)
            try {
                $hash = $cfg['admin_password_hash'];
                $st = db()->prepare('SELECT id FROM panel_users WHERE username=? LIMIT 1');
                $st->execute(array($sessionUser));
                $row = $st->fetch();
                if ($row) {
                    db()->prepare('UPDATE panel_users SET username=?, password_hash=?, is_active=1 WHERE id=?')
                        ->execute(array($cfg['admin_username'], $hash, (int)$row['id']));
                } else {
                    $st2 = db()->prepare('SELECT id FROM panel_users WHERE username=? LIMIT 1');
                    $st2->execute(array($cfg['admin_username']));
                    if ($st2->fetch()) {
                        db()->prepare('UPDATE panel_users SET password_hash=?, is_active=1 WHERE username=?')
                            ->execute(array($hash, $cfg['admin_username']));
                    } else {
                        db()->prepare('INSERT INTO panel_users (username, password_hash, display_name, is_super, can_tickets, can_requests, can_products, can_menus, can_faqs, can_users, can_languages, can_branding, can_settings, can_admins, can_health, is_active) VALUES (?,?,?,1,1,1,1,1,1,1,1,1,1,1,1,1)')
                            ->execute(array($cfg['admin_username'], $hash, 'Super Admin'));
                    }
                }
                $_SESSION['hddland_admin_user'] = $cfg['admin_username'];
            } catch (Throwable $e) {}
            flash('ok', 'Admin panel login updated.');
            $tab = 'security';
        } elseif ($action === 'init_defaults') {
            $cfg = merge_bot_defaults_into_config(bot_config());
            save_bot_config($cfg);
            flash('ok', 'Missing default keys written to config.');
            $tab = 'general';
        } elseif ($action === 'set_webhook') {
            $base = rtrim((string)($_POST['public_base'] ?? ''), '/');
            if ($base === '') {
                throw new RuntimeException('Public base URL required, e.g. https://hdd-land.com/telegram_bot/php_bot');
            }
            $url = $base . '/webhook.php';
            $params = array('url' => $url);
            if (!empty($cfg['webhook_secret'])) {
                $params['secret_token'] = $cfg['webhook_secret'];
            }
            $res = tg_api('setWebhook', $params);
            if (empty($res['ok'])) {
                throw new RuntimeException('Telegram error: ' . json_encode($res));
            }
            flash('ok', 'Webhook set to ' . $url);
            $tab = 'webhook';
        } elseif ($action === 'webhook_info') {
            $res = tg_api('getWebhookInfo', array());
            flash('ok', 'Webhook info: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
            $tab = 'webhook';
        } elseif ($action === 'delete_webhook') {
            $res = tg_api('deleteWebhook', array());
            flash('ok', 'Webhook deleted: ' . json_encode($res));
            $tab = 'webhook';
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: settings.php?tab=' . urlencode($tab));
    exit;
}

$cfg = merge_bot_defaults_into_config(bot_config());
$tokenMask = '';
if (!empty($cfg['bot_token'])) {
    $t = (string)$cfg['bot_token'];
    $tokenMask = substr($t, 0, 8) . '…' . substr($t, -4);
}

$pageTitle = 'Settings';
$active = 'settings';
require __DIR__ . '/layout_header.php';
?>
<div class="actions" style="margin-bottom:14px;flex-wrap:wrap">
  <?php
  $tabs = array(
    'general' => 'General',
    'features' => 'Features',
    'messages' => 'Page Texts',
    'branding' => 'Branding',
    'notify' => 'Notifications',
    'api' => 'AI / API',
    'security' => 'Security',
    'webhook' => 'Webhook',
  );
  foreach ($tabs as $k => $label): ?>
    <a class="btn sm <?= $tab === $k ? '' : 'secondary' ?>" href="?tab=<?= e($k) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'general'): ?>
<div class="row2">
  <div class="card panel">
    <h2>URLs & Contacts</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="save_general">
      <label>Website URL</label>
      <input name="site_url" value="<?= e((string)$cfg['site_url']) ?>">
      <label>Forum URL</label>
      <input name="forum_url" value="<?= e((string)$cfg['forum_url']) ?>">
      <label>Training URL</label>
      <input name="training_url" value="<?= e((string)$cfg['training_url']) ?>">
      <label>Support email (optional)</label>
      <input name="support_email" value="<?= e((string)$cfg['support_email']) ?>">
      <label>Sales email (optional)</label>
      <input name="sales_email" value="<?= e((string)$cfg['sales_email']) ?>">
      <label>Telegram Admin IDs (comma-separated)</label>
      <input name="admin_ids" value="<?= e(implode(',', $cfg['admin_ids'] ?? array())) ?>">
      <label><input type="checkbox" name="start_with_menu" value="1" <?= !empty($cfg['start_with_menu'])?'checked':'' ?>> Skip language gate on /start (go straight to menu)</label>
      <label><input type="checkbox" name="maintenance_mode" value="1" <?= !empty($cfg['maintenance_mode'])?'checked':'' ?>> Maintenance mode</label>
      <label>Maintenance message</label>
      <textarea name="maintenance_text"><?= e((string)$cfg['maintenance_text']) ?></textarea>
      <button class="btn" type="submit" style="margin-top:12px">Save General</button>
    </form>
  </div>
  <div class="card panel">
    <h2>Quick actions</h2>
    <p class="muted">Menus, FAQ, Products, Languages, Admins are managed from their own pages — those are the content. This page is the master switchboard.</p>
    <div class="actions" style="margin-top:12px">
      <a class="btn sm" href="menus.php">Edit Menus</a>
      <a class="btn sm secondary" href="faqs.php">Edit FAQ</a>
      <a class="btn sm secondary" href="products.php">Edit Products</a>
      <a class="btn sm secondary" href="broadcast.php">Broadcast</a>
    </div>
    <form method="post" style="margin-top:20px" onsubmit="return confirm('Write missing default keys into config.local.php?')">
      <input type="hidden" name="action" value="init_defaults">
      <button class="btn secondary" type="submit">Initialize missing defaults</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'features'): ?>
<div class="card panel">
  <h2>Feature switches</h2>
  <p class="muted">Turn modules on/off without code changes. Disabled features reply with a short unavailable message.</p>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_features">
    <?php
    $feats = array(
      'feature_shop' => 'Shop / Products',
      'feature_forum' => 'Forum',
      'feature_faq' => 'FAQ',
      'feature_tickets' => 'Tickets (/ticket)',
      'feature_prodesk' => 'Pro Desk (Support & Sales requests)',
      'feature_ai' => 'AI assistant (/ask)',
      'feature_language_gate' => 'Language picker on /start',
      'feature_auto_faq_search' => 'Auto-search FAQ on free text',
    );
    foreach ($feats as $k => $label): ?>
      <label><input type="checkbox" name="<?= e($k) ?>" value="1" <?= !empty($cfg[$k])?'checked':'' ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
    <button class="btn" type="submit" style="margin-top:12px">Save Features</button>
  </form>
</div>

<?php elseif ($tab === 'messages'): ?>
<div class="card panel">
  <h2>Page texts (EN / FA)</h2>
  <p class="muted">Leave empty to use built-in defaults. HTML tags like &lt;b&gt; are supported.</p>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_messages">
    <?php
    $fields = array(
      'website_text_en' => 'Website text (EN)',
      'website_text_fa' => 'Website text (FA)',
      'forum_text_en' => 'Forum text (EN)',
      'forum_text_fa' => 'Forum text (FA)',
      'training_text_en' => 'Training text (EN)',
      'training_text_fa' => 'Training text (FA)',
      'shop_text_en' => 'Shop intro (EN)',
      'shop_text_fa' => 'Shop intro (FA)',
      'help_text_en' => 'Help / commands (EN)',
      'help_text_fa' => 'Help / commands (FA)',
    );
    foreach ($fields as $k => $label): ?>
      <label><?= e($label) ?></label>
      <textarea name="<?= e($k) ?>" rows="4"><?= e((string)($cfg[$k] ?? '')) ?></textarea>
    <?php endforeach; ?>
    <button class="btn" type="submit" style="margin-top:12px">Save Page Texts</button>
  </form>
</div>

<?php elseif ($tab === 'branding'): ?>
<div class="card panel">
  <h2>Bot title & welcome</h2>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_branding">
    <label>Bot Title</label>
    <input name="bot_title" value="<?= e((string)$cfg['bot_title']) ?>">
    <label>Subtitle</label>
    <input name="bot_subtitle" value="<?= e((string)$cfg['bot_subtitle']) ?>">
    <label>Language gate text</label>
    <textarea name="gate_text" rows="4"><?= e((string)$cfg['gate_text']) ?></textarea>
    <label>Welcome EN</label>
    <textarea name="welcome_text_en" rows="5"><?= e((string)$cfg['welcome_text_en']) ?></textarea>
    <label>Welcome FA</label>
    <textarea name="welcome_text_fa" rows="5"><?= e((string)$cfg['welcome_text_fa']) ?></textarea>
    <button class="btn" type="submit" style="margin-top:12px">Save Branding</button>
  </form>
</div>

<?php elseif ($tab === 'notify'): ?>
<div class="card panel">
  <h2>Telegram staff notifications</h2>
  <p class="muted">Uses Admin IDs + active staff from Admins & Access.</p>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_notify">
    <label><input type="checkbox" name="notify_tickets" value="1" <?= !empty($cfg['notify_tickets'])?'checked':'' ?>> Notify on new tickets</label>
    <label><input type="checkbox" name="notify_requests" value="1" <?= !empty($cfg['notify_requests'])?'checked':'' ?>> Notify on Support/Sales requests</label>
    <label><input type="checkbox" name="notify_media" value="1" <?= !empty($cfg['notify_media'])?'checked':'' ?>> Forward user media to staff</label>
    <button class="btn" type="submit" style="margin-top:12px">Save Notifications</button>
  </form>
</div>

<?php elseif ($tab === 'api'): ?>
<div class="card panel">
  <h2>AI / external APIs</h2>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_api">
    <label>OpenAI API Key</label>
    <input name="openai_api_key" value="<?= e((string)$cfg['openai_api_key']) ?>" autocomplete="off">
    <label>AI model</label>
    <input name="ai_model" value="<?= e((string)$cfg['ai_model']) ?>">
    <label>AI system prompt</label>
    <textarea name="ai_system_prompt" rows="5"><?= e((string)$cfg['ai_system_prompt']) ?></textarea>
    <label>Weather API Key (optional / unused unless enabled later)</label>
    <input name="weather_api_key" value="<?= e((string)$cfg['weather_api_key']) ?>">
    <button class="btn" type="submit" style="margin-top:12px">Save API</button>
  </form>
</div>

<?php elseif ($tab === 'security'): ?>
<div class="row2">
  <div class="card panel">
    <h2>Bot token</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="save_token">
      <label>Current token (masked)</label>
      <input value="<?= e($tokenMask) ?>" disabled>
      <label>New Bot Token (leave blank to keep)</label>
      <input name="bot_token" placeholder="123456:ABC..." autocomplete="off">
      <label>Webhook secret token (optional)</label>
      <input name="webhook_secret" value="<?= e((string)($cfg['webhook_secret'] ?? '')) ?>" autocomplete="off">
      <button class="btn" type="submit" style="margin-top:12px">Save Token / Secret</button>
    </form>
  </div>
  <div class="card panel">
    <h2>Panel login / Change Password</h2>
    <p class="muted">Or use the dedicated page: <a href="password.php">Change Password</a></p>
    <form method="post" class="stack" autocomplete="off">
      <input type="hidden" name="action" value="password">
      <label>Username</label>
      <input name="admin_username" value="<?= e((string)($cfg['admin_username'] ?? 'admin')) ?>">
      <label>Current Password</label>
      <input type="password" name="current_password" required autocomplete="current-password">
      <label>New Password</label>
      <input type="password" name="admin_password" required minlength="8" autocomplete="new-password">
      <label>Confirm Password</label>
      <input type="password" name="admin_password2" required minlength="8" autocomplete="new-password">
      <button class="btn" type="submit" style="margin-top:12px">Update Password</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'webhook'): ?>
<div class="card panel">
  <h2>Telegram webhook</h2>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="set_webhook">
    <label>Public base URL of php_bot (no trailing slash)</label>
    <input name="public_base" value="https://hdd-land.com/telegram_bot/php_bot" required>
    <button class="btn" type="submit" style="margin-top:12px">Set Webhook</button>
  </form>
  <div class="actions" style="margin-top:16px">
    <form method="post"><input type="hidden" name="action" value="webhook_info"><button class="btn secondary" type="submit">Get Webhook Info</button></form>
    <form method="post" onsubmit="return confirm('Delete webhook?')"><input type="hidden" name="action" value="delete_webhook"><button class="btn danger" type="submit">Delete Webhook</button></form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
