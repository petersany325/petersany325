<?php
declare(strict_types=1);

/**
 * Lightweight PSR-4-ish autoloader for HddLand\Bot\* under src/
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'HddLand\\Bot\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
