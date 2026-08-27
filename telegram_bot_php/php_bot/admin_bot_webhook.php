<?php
declare(strict_types=1);

/**
 * Webhook entry for SeDiv Admin Console bot (@SedivSupport_bot).
 * English-only. Requires panel username/password.
 */
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/src/Autoload.php';

\HddLand\Bot\AdminBot\AdminBotKernel::handleHttp();
