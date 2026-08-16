<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
if (!hash_equals((string)$config['webhook_secret'], (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

require __DIR__ . '/src/Database.php';
$db = new Database($config['db']);
$pdo = $db->pdo();

// Add flow column if missing (for "شهر دیگر" and other pending steps)
$cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'flow'")->fetchAll();
if (!$cols) {
    $pdo->exec("ALTER TABLE users ADD COLUMN flow VARCHAR(64) NULL AFTER search_pref");
    echo "added_flow\n";
} else {
    echo "flow_exists\n";
}

echo 'ok';
