<?php
declare(strict_types=1);

// Public version probe (no secrets). Used to verify deploys.
require __DIR__ . '/src/Handlers.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'bot' => 'HamGapXBot',
    'code_version' => Handlers::CODE_VERSION,
    'time' => date('c'),
], JSON_UNESCAPED_UNICODE);
