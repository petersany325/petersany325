<?php
/**
 * Final Admin Settings — ALL bot options live here.
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$cfg = merge_bot_defaults_into_config(bot_config());
$tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'general';
$allowedTabs = array('general','features','commerce','license','growth','support','integrations','messages','branding','notify','api','security','webhook');
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
            foreach (all_feature_keys() as $f) {
                $cfg['feature_' . $f] = settings_bool_post('feature_' . $f);
            }
            save_bot_config($cfg);
            flash('ok', 'Feature switches saved.');
            $tab = 'features';
        } elseif ($action === 'save_commerce') {
            $cfg['payment_provider_token'] = trim((string)($_POST['payment_provider_token'] ?? ''));
            $cfg['payment_currency'] = trim((string)($_POST['payment_currency'] ?? 'USD'));
            $cfg['checkout_url'] = trim((string)($_POST['checkout_url'] ?? ''));
            $cfg['miniapp_url'] = trim((string)($_POST['miniapp_url'] ?? ''));
            save_bot_config($cfg);
            flash('ok', 'Commerce / payments settings saved.');
            $tab = 'commerce';
        } elseif ($action === 'save_license') {
            foreach (array('license_help_text_en','license_help_text_fa','license_check_url','renewal_message_en','renewal_message_fa') as $k) {
                $cfg[$k] = trim((string)($_POST[$k] ?? ''));
            }
            $cfg['renewal_days_before'] = max(1, (int)($_POST['renewal_days_before'] ?? 14));
            save_bot_config($cfg);
            flash('ok', 'License / renewal settings saved.');
            $tab = 'license';
        } elseif ($action === 'save_growth') {
            foreach (array(
                'bot_username','referral_bonus_text_en','referral_bonus_text_fa','contact_phone','contact_hours',
                'news_channel_url','demo_request_info_en','demo_request_info_fa','feedback_thankyou_en','feedback_thankyou_fa',
                'brand_search_prompt_en','brand_search_prompt_fa',
                'vip_download_url','vip_download_text_en','vip_download_text_fa','vip_download_denied_en','vip_download_denied_fa',
            ) as $k) {
                $cfg[$k] = trim((string)($_POST[$k] ?? ''));
            }
            save_bot_config($cfg);
            flash('ok', 'Growth / contact settings saved.');
            $tab = 'growth';
        } elseif ($action === 'save_support') {
            foreach (array('support_intro_en', 'support_intro_fa', 'support_links', 'support_questions') as $k) {
                $cfg[$k] = trim((string)($_POST[$k] ?? ''));
            }
            $cfg['ticket_ask_name'] = settings_bool_post('ticket_ask_name');
            $cfg['ticket_ask_phone'] = settings_bool_post('ticket_ask_phone');
            $cfg['ticket_ask_id'] = settings_bool_post('ticket_ask_id');
            $cfg['ticket_always_ask_name'] = settings_bool_post('ticket_always_ask_name');
            $cfg['ticket_always_ask_phone'] = settings_bool_post('ticket_always_ask_phone');
            $cfg['ticket_always_ask_id'] = settings_bool_post('ticket_always_ask_id');
            $cfg['ticket_phone_for_view'] = settings_bool_post('ticket_phone_for_view');
            save_bot_config($cfg);
            flash('ok', 'Technical Support & Tickets settings saved.');
            $tab = 'support';
        } elseif ($action === 'save_integrations') {
            $cfg['crm_webhook_url'] = trim((string)($_POST['crm_webhook_url'] ?? ''));
            $cfg['analytics_webhook_url'] = trim((string)($_POST['analytics_webhook_url'] ?? ''));
            save_bot_config($cfg);
            flash('ok', 'Integrations saved.');
            $tab = 'integrations';
        } elseif ($action === 'save_messages') {
            $keys = array(
                'website_text_en','website_text_fa','forum_text_en','forum_text_fa',
                'training_text_en','training_text_fa','shop_text_en','shop_text_fa',
                'help_text_en','help_text_fa','cart_text_en','cart_text_fa',
                'orders_text_en','orders_text_fa','license_text_en','license_text_fa',
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
            $cfg['notify_orders'] = settings_bool_post('notify_orders');
            $cfg['notify_feedback'] = settings_bool_post('notify_feedback');
            save_bot_config($cfg);
            flash('ok', 'Notification settings saved.');
            $tab = 'notify';
        } elseif ($action === 'sync_menus') {
            if (function_exists('ensure_professional_menus')) {
                $n = ensure_professional_menus(db());
                flash('ok', 'Professional menus synced. Added/updated: ' . (int)$n);
            } else {
                throw new RuntimeException('ensure_professional_menus() missing — update menu_faq.php');
            }
            $tab = 'features';
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
                $detail = is_array($res) ? ($res['description'] ?? json_encode($res)) : 'empty response';
                throw new RuntimeException('Telegram setWebhook failed: ' . $detail . ' — check admin/check.php (host must reach api.telegram.org)');
            }
            flash('ok', 'Webhook set to ' . $url . ' | ' . json_encode($res, JSON_UNESCAPED_UNICODE));
            $tab = 'webhook';
        } elseif ($action === 'webhook_info') {
            $res = tg_api('getWebhookInfo', array());
            if (empty($res) || (isset($res['ok']) && !$res['ok'] && empty($res['result']))) {
                $detail = is_array($res) ? ($res['description'] ?? json_encode($res)) : '[]';
                throw new RuntimeException('getWebhookInfo failed: ' . $detail . ' — open admin/check.php');
            }
            flash('ok', 'Webhook info: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
            $tab = 'webhook';
        } elseif ($action === 'delete_webhook') {
            $res = tg_api('deleteWebhook', array());
            flash('ok', 'Webhook deleted: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
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
    'commerce' => 'Commerce',
    'license' => 'License',
    'growth' => 'Growth',
    'support' => 'Support / Tickets',
    'integrations' => 'Integrations',
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
      <label><input type="checkbox" name="start_with_menu" value="1" <?= !empty($cfg['start_with_menu'])?'checked':'' ?>> Skip language picker on /start (NOT recommended — users should pick language first)</label>
      <p class="muted">Normal flow: <b>/start → select language → bot switches to that language → menus</b>. Keep this unchecked.</p>
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
<div class="row2">
  <div class="card panel">
    <h2>Core modules</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="save_features">
      <?php
      $core = array(
        'feature_shop' => '🛒 Shop / Products',
        'feature_forum' => '📋 Forum',
        'feature_faq' => '❓ FAQ',
        'feature_tickets' => '🎫 Tickets (/ticket)',
        'feature_prodesk' => '💼 Pro Desk (Support & Sales)',
        'feature_ai' => '🤖 AI assistant (/ask)',
        'feature_language_gate' => '🌍 Language picker on /start',
        'feature_auto_faq_search' => '🔎 Auto-search FAQ on free text',
      );
      foreach ($core as $k => $label): ?>
        <label><input type="checkbox" name="<?= e($k) ?>" value="1" <?= !empty($cfg[$k])?'checked':'' ?>> <?= e($label) ?></label>
      <?php endforeach; ?>
      <h2 style="margin-top:18px">Professional modules</h2>
      <?php
      $pro = array(
        'feature_cart' => '🛒 Cart',
        'feature_orders' => '📦 My Orders',
        'feature_payments' => '💳 Checkout / Payments',
        'feature_license' => '🔑 License Status',
        'feature_renewal' => '♻️ Renew License',
        'feature_demo' => '▶️ Request Demo',
        'feature_profile' => '👤 My Profile',
        'feature_feedback' => '⭐ Feedback',
        'feature_referral' => '🎁 Referral',
        'feature_contact' => '☎️ Contact Human',
        'feature_brand_search' => '🔧 Brand Search',
        'feature_news' => '📰 News / Updates',
        'feature_miniapp' => '📱 Mini App',
        'feature_vip_download' => '💎 VIP Download',
      );
      foreach ($pro as $k => $label): ?>
        <label><input type="checkbox" name="<?= e($k) ?>" value="1" <?= !empty($cfg[$k])?'checked':'' ?>> <?= e($label) ?></label>
      <?php endforeach; ?>
      <button class="btn" type="submit" style="margin-top:12px">Save All Features</button>
    </form>
  </div>
  <div class="card panel">
    <h2>Menus sync</h2>
    <p class="muted">Creates missing professional menu rows (Cart, Orders, License, …) without deleting your custom menus.</p>
    <form method="post" onsubmit="return confirm('Sync professional menus into database?')">
      <input type="hidden" name="action" value="sync_menus">
      <button class="btn secondary" type="submit">Sync Professional Menus</button>
    </form>
    <p class="muted" style="margin-top:14px">Also open <a href="health.php">Health & Repair</a> → Menu Health for online checks.</p>
  </div>
</div>

<?php elseif ($tab === 'commerce'): ?>
<div class="card panel">
  <h2>Commerce & payments</h2>
  <p class="muted">Telegram Payments provider token from BotFather / payment provider. Checkout URL is optional external page.</p>
  <form method="post" class="stack" autocomplete="off">
    <input type="hidden" name="action" value="save_commerce">
    <label>Payment provider token</label>
    <input name="payment_provider_token" value="<?= e((string)($cfg['payment_provider_token'] ?? '')) ?>" placeholder="123456:TEST:XXXX">
    <label>Currency</label>
    <input name="payment_currency" value="<?= e((string)($cfg['payment_currency'] ?? 'USD')) ?>">
    <label>Checkout URL (optional)</label>
    <input name="checkout_url" value="<?= e((string)($cfg['checkout_url'] ?? '')) ?>" placeholder="https://...">
    <label>Mini App URL (optional)</label>
    <input name="miniapp_url" value="<?= e((string)($cfg['miniapp_url'] ?? '')) ?>" placeholder="https://...">
    <button class="btn" type="submit" style="margin-top:12px">Save Commerce</button>
  </form>
</div>

<?php elseif ($tab === 'license'): ?>
<div class="card panel">
  <h2>License & renewal</h2>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_license">
    <label>License help text (EN)</label>
    <textarea name="license_help_text_en" rows="4"><?= e((string)($cfg['license_help_text_en'] ?? '')) ?></textarea>
    <label>License help text (FA)</label>
    <textarea name="license_help_text_fa" rows="4"><?= e((string)($cfg['license_help_text_fa'] ?? '')) ?></textarea>
    <label>License check URL (optional API/page)</label>
    <input name="license_check_url" value="<?= e((string)($cfg['license_check_url'] ?? '')) ?>">
    <label>Remind before expiry (days)</label>
    <input name="renewal_days_before" type="number" min="1" value="<?= e((string)($cfg['renewal_days_before'] ?? 14)) ?>">
    <label>Renewal message (EN)</label>
    <textarea name="renewal_message_en" rows="3"><?= e((string)($cfg['renewal_message_en'] ?? '')) ?></textarea>
    <label>Renewal message (FA)</label>
    <textarea name="renewal_message_fa" rows="3"><?= e((string)($cfg['renewal_message_fa'] ?? '')) ?></textarea>
    <button class="btn" type="submit" style="margin-top:12px">Save License Settings</button>
  </form>
</div>

<?php elseif ($tab === 'growth'): ?>
<div class="card panel">
  <h2>Growth, contact & engagement</h2>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_growth">
    <label>Bot username (without @) for referral links</label>
    <input name="bot_username" value="<?= e((string)($cfg['bot_username'] ?? '')) ?>" placeholder="YourBot">
    <label>Referral bonus text (EN)</label>
    <textarea name="referral_bonus_text_en" rows="3"><?= e((string)($cfg['referral_bonus_text_en'] ?? '')) ?></textarea>
    <label>Referral bonus text (FA)</label>
    <textarea name="referral_bonus_text_fa" rows="3"><?= e((string)($cfg['referral_bonus_text_fa'] ?? '')) ?></textarea>
    <label>Contact phone</label>
    <input name="contact_phone" value="<?= e((string)($cfg['contact_phone'] ?? '')) ?>">
    <label>Contact hours</label>
    <input name="contact_hours" value="<?= e((string)($cfg['contact_hours'] ?? '')) ?>">
    <label>News channel URL</label>
    <input name="news_channel_url" value="<?= e((string)($cfg['news_channel_url'] ?? '')) ?>" placeholder="https://t.me/...">
    <label>Demo request intro (EN)</label>
    <textarea name="demo_request_info_en" rows="3"><?= e((string)($cfg['demo_request_info_en'] ?? '')) ?></textarea>
    <label>Demo request intro (FA)</label>
    <textarea name="demo_request_info_fa" rows="3"><?= e((string)($cfg['demo_request_info_fa'] ?? '')) ?></textarea>
    <label>Feedback thank-you (EN)</label>
    <textarea name="feedback_thankyou_en" rows="2"><?= e((string)($cfg['feedback_thankyou_en'] ?? '')) ?></textarea>
    <label>Feedback thank-you (FA)</label>
    <textarea name="feedback_thankyou_fa" rows="2"><?= e((string)($cfg['feedback_thankyou_fa'] ?? '')) ?></textarea>
    <label>Brand search prompt (EN)</label>
    <textarea name="brand_search_prompt_en" rows="2"><?= e((string)($cfg['brand_search_prompt_en'] ?? '')) ?></textarea>
    <label>Brand search prompt (FA)</label>
    <textarea name="brand_search_prompt_fa" rows="2"><?= e((string)($cfg['brand_search_prompt_fa'] ?? '')) ?></textarea>
    <h2 style="margin-top:18px">💎 VIP Download</h2>
    <label>VIP Download URL</label>
    <input name="vip_download_url" value="<?= e((string)($cfg['vip_download_url'] ?? 'https://forum.hdd-land.com/vbdlmanager')) ?>" placeholder="https://forum.hdd-land.com/vbdlmanager">
    <label>VIP intro text (EN)</label>
    <textarea name="vip_download_text_en" rows="3"><?= e((string)($cfg['vip_download_text_en'] ?? '')) ?></textarea>
    <label>VIP intro text (FA)</label>
    <textarea name="vip_download_text_fa" rows="3"><?= e((string)($cfg['vip_download_text_fa'] ?? '')) ?></textarea>
    <label>Denied message for non-VIP (EN)</label>
    <textarea name="vip_download_denied_en" rows="2"><?= e((string)($cfg['vip_download_denied_en'] ?? '')) ?></textarea>
    <label>Denied message for non-VIP (FA)</label>
    <textarea name="vip_download_denied_fa" rows="2"><?= e((string)($cfg['vip_download_denied_fa'] ?? '')) ?></textarea>
    <p class="muted" style="font-size:.85rem">Mark users as VIP in <a href="users.php">Users</a>. Admins always have access.</p>
    <button class="btn" type="submit" style="margin-top:12px">Save Growth Settings</button>
  </form>
</div>

<?php elseif ($tab === 'support'): ?>
<form method="post">
  <input type="hidden" name="action" value="save_support">
  <div class="row2">
    <div class="card panel">
      <h2>🛠️ Technical Support form</h2>
      <p class="muted">Used when users open Technical Support or <code>/ticket</code>. Add questions and helpful links from here.</p>
      <div class="stack">
        <label>Intro text (EN)</label>
        <textarea name="support_intro_en" rows="4"><?= e((string)($cfg['support_intro_en'] ?? '')) ?></textarea>
        <label>Intro text (FA)</label>
        <textarea name="support_intro_fa" rows="4"><?= e((string)($cfg['support_intro_fa'] ?? '')) ?></textarea>
        <label>Helpful links (one per line: <code>Label|https://url</code>)</label>
        <textarea name="support_links" rows="4" placeholder="Forum|https://hdd-land.com/forum"><?= e((string)($cfg['support_links'] ?? '')) ?></textarea>
        <label>Questions (one per line: <code>key|English|فارسی|1or0</code> — last = required)</label>
        <textarea name="support_questions" rows="6" placeholder="drive_model|Hard drive model|مدل هارد|1"><?= e((string)($cfg['support_questions'] ?? '')) ?></textarea>
      </div>
    </div>
    <div class="card panel">
      <h2>🎫 Smart ticket identity</h2>
      <p class="muted">برای مدیریت کامل فیلدها (نام، موبایل، کد مشتری، سؤالات) از صفحه اختصاصی استفاده کنید.</p>
      <p><a class="btn sm" href="ticket_fields.php">🧠 Open Ticket Fields Manager</a></p>
      <div class="stack" style="margin-top:14px">
        <label><input type="checkbox" name="ticket_ask_name" value="1" <?= !empty($cfg['ticket_ask_name'])?'checked':'' ?>> Ask name</label>
        <label><input type="checkbox" name="ticket_ask_phone" value="1" <?= !empty($cfg['ticket_ask_phone'])?'checked':'' ?>> Ask mobile</label>
        <label><input type="checkbox" name="ticket_ask_id" value="1" <?= !empty($cfg['ticket_ask_id'] ?? 1)?'checked':'' ?>> Ask customer / license ID</label>
        <label><input type="checkbox" name="ticket_always_ask_name" value="1" <?= !empty($cfg['ticket_always_ask_name'])?'checked':'' ?>> Always re-ask name</label>
        <label><input type="checkbox" name="ticket_always_ask_phone" value="1" <?= !empty($cfg['ticket_always_ask_phone'])?'checked':'' ?>> Always re-ask mobile</label>
        <label><input type="checkbox" name="ticket_always_ask_id" value="1" <?= !empty($cfg['ticket_always_ask_id'] ?? 1)?'checked':'' ?>> Always re-ask ID</label>
        <hr style="border:none;border-top:1px solid var(--line);margin:8px 0">
        <label><input type="checkbox" name="ticket_phone_for_view" value="1" <?= !empty($cfg['ticket_phone_for_view'])?'checked':'' ?>> Require phone again to view My Tickets</label>
      </div>
    </div>
  </div>
  <button class="btn" type="submit" style="margin-top:14px">Save Support &amp; Ticket Settings</button>
</form>

<?php elseif ($tab === 'integrations'): ?>
<div class="card panel">
  <h2>Integrations</h2>
  <p class="muted">Optional webhooks for CRM / analytics (JSON POST when events happen — ready for next stage).</p>
  <form method="post" class="stack">
    <input type="hidden" name="action" value="save_integrations">
    <label>CRM webhook URL</label>
    <input name="crm_webhook_url" value="<?= e((string)($cfg['crm_webhook_url'] ?? '')) ?>" placeholder="https://...">
    <label>Analytics webhook URL</label>
    <input name="analytics_webhook_url" value="<?= e((string)($cfg['analytics_webhook_url'] ?? '')) ?>" placeholder="https://...">
    <button class="btn" type="submit" style="margin-top:12px">Save Integrations</button>
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
      'cart_text_en' => 'Cart text (EN)',
      'cart_text_fa' => 'Cart text (FA)',
      'orders_text_en' => 'Orders text (EN)',
      'orders_text_fa' => 'Orders text (FA)',
      'license_text_en' => 'License page text (EN)',
      'license_text_fa' => 'License page text (FA)',
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
    <label><input type="checkbox" name="notify_orders" value="1" <?= !empty($cfg['notify_orders'])?'checked':'' ?>> Notify on orders / checkout events</label>
    <label><input type="checkbox" name="notify_feedback" value="1" <?= !empty($cfg['notify_feedback'])?'checked':'' ?>> Notify on feedback</label>
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
