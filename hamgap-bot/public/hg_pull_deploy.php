<?php
declare(strict_types=1);

/**
 * Tiny one-shot puller: fetches HamGap PHP sources from GitHub raw by SHA.
 * Usage (once): /hg_pull_deploy.php?k=SECRET&sha=COMMIT
 * Delete or rotate secret after deploy.
 */
header('Content-Type: application/json; charset=utf-8');

$key = (string)($_GET['k'] ?? '');
if (!hash_equals('hgDeploy4jXiS2', $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sha = preg_replace('/[^a-f0-9]/', '', (string)($_GET['sha'] ?? ''));
if (strlen($sha) < 7) {
    echo json_encode(['ok' => false, 'error' => 'bad sha'], JSON_UNESCAPED_UNICODE);
    exit;
}

$root = __DIR__;
$map = [
    'src/Handlers.php' => $root . '/src/Handlers.php',
    'src/Keyboards.php' => $root . '/src/Keyboards.php',
    'src/Database.php' => $root . '/src/Database.php',
    'src/AdminHandlers.php' => $root . '/src/AdminHandlers.php',
    'src/SupportHandlers.php' => $root . '/src/SupportHandlers.php',
    'src/StaffHandlers.php' => $root . '/src/StaffHandlers.php',
    'src/Occupation.php' => $root . '/src/Occupation.php',
    'src/Settings.php' => $root . '/src/Settings.php',
    'src/Matcher.php' => $root . '/src/Matcher.php',
    'src/Migrator.php' => $root . '/src/Migrator.php',
    'src/Telegram.php' => $root . '/src/Telegram.php',
    'src/Gender.php' => $root . '/src/Gender.php',
    'src/IranLocations.php' => $root . '/src/IranLocations.php',
    'src/CoinCatalog.php' => $root . '/src/CoinCatalog.php',
    'src/ChatModes.php' => $root . '/src/ChatModes.php',
    'src/GeoCheck.php' => $root . '/src/GeoCheck.php',
    'src/VipFilter.php' => $root . '/src/VipFilter.php',
    'src/PublicChatFilter.php' => $root . '/src/PublicChatFilter.php',
    'public/version.php' => $root . '/version.php',
    'public/webhook.php' => $root . '/webhook.php',
    'public/webhook_admin.php' => $root . '/webhook_admin.php',
    'public/webhook_support.php' => $root . '/webhook_support.php',
];

$only = (string)($_GET['only'] ?? '');
$out = ['ok' => true, 'sha' => $sha, 'files' => []];

foreach ($map as $rel => $dest) {
    if ($only !== '' && $rel !== $only && basename($rel) !== $only) {
        continue;
    }
    $url = 'https://raw.githubusercontent.com/petersany325/petersany325/' . $sha . '/hamgap-bot/' . $rel;
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 90,
            'header' => "User-Agent: HamGapPuller\r\n",
        ],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || $data === '') {
        $out['ok'] = false;
        $out['files'][$rel] = ['ok' => false, 'error' => 'fetch failed', 'url' => $url];
        continue;
    }
    if (!is_dir(dirname($dest))) {
        @mkdir(dirname($dest), 0755, true);
    }
    $n = file_put_contents($dest, $data);
    $out['files'][$rel] = ['ok' => $n !== false, 'bytes' => $n];
    if ($n === false) {
        $out['ok'] = false;
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
