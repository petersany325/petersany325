<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo "HESAB_OK\n";
echo 'time=' . date('c') . "\n";
echo 'php=' . PHP_VERSION . "\n";
echo 'host=' . ($_SERVER['HTTP_HOST'] ?? '') . "\n";
echo 'uri=' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo 'docroot=' . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo 'config=' . (is_file(__DIR__ . '/config.php') ? 'yes' : 'no') . "\n";
