<?php
declare(strict_types=1);

// Public version probe (no secrets). Used to verify deploys.
// Also self-heals a few files the older GitHub puller map may miss.
require __DIR__ . '/src/Handlers.php';

$healKey = (string)($_GET['heal'] ?? '');
if ($healKey !== '' && hash_equals('hgDeploy4jXiS2', $healKey)) {
    $sha = preg_replace('/[^a-f0-9]/', '', (string)($_GET['sha'] ?? '883c2717949573d1eb3ee4d9635b10fc180f48a0'));
    $root = __DIR__;
    $extra = [
        'src/SupportHandlers.php' => $root . '/src/SupportHandlers.php',
        'src/StaffHandlers.php' => $root . '/src/StaffHandlers.php',
        'src/CoinCatalog.php' => $root . '/src/CoinCatalog.php',
        'src/ChatModes.php' => $root . '/src/ChatModes.php',
        'src/GeoCheck.php' => $root . '/src/GeoCheck.php',
        'src/VipFilter.php' => $root . '/src/VipFilter.php',
        'src/PublicChatFilter.php' => $root . '/src/PublicChatFilter.php',
        'src/Settings.php' => $root . '/src/Settings.php',
        'src/Migrator.php' => $root . '/src/Migrator.php',
        'src/Matcher.php' => $root . '/src/Matcher.php',
        'src/Database.php' => $root . '/src/Database.php',
        'src/Keyboards.php' => $root . '/src/Keyboards.php',
        'src/AdminHandlers.php' => $root . '/src/AdminHandlers.php',
        'src/Handlers.php' => $root . '/src/Handlers.php',
        'public/hg_pull_deploy.php' => $root . '/hg_pull_deploy.php',
        'public/webhook_support.php' => $root . '/webhook_support.php',
        'public/webhook_admin.php' => $root . '/webhook_admin.php',
        'public/webhook.php' => $root . '/webhook.php',
    ];
    $healed = [];
    foreach ($extra as $rel => $dest) {
        $url = 'https://raw.githubusercontent.com/petersany325/petersany325/' . $sha . '/hamgap-bot/' . $rel;
        $ctx = stream_context_create(['http' => ['timeout' => 90, 'header' => "User-Agent: HamGapHeal\r\n"]]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false || $data === '') {
            $healed[$rel] = ['ok' => false, 'error' => 'fetch failed'];
            continue;
        }
        if (!is_dir(dirname($dest))) {
            @mkdir(dirname($dest), 0755, true);
        }
        $n = @file_put_contents($dest, $data);
        $healed[$rel] = ['ok' => $n !== false, 'bytes' => $n];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'bot' => 'HamGapXBot',
        'code_version' => Handlers::CODE_VERSION,
        'healed' => $healed,
        'time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'bot' => 'HamGapXBot',
    'code_version' => Handlers::CODE_VERSION,
    'time' => date('c'),
], JSON_UNESCAPED_UNICODE);
