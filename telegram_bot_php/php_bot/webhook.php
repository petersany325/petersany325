<?php
declare(strict_types=1);

/**
 * Thin HTTP entrypoint.
 * Business logic lives in src/ (Handlers → Services → Repositories).
 */
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/src/Autoload.php';

\HddLand\Bot\BotKernel::handleHttp();
