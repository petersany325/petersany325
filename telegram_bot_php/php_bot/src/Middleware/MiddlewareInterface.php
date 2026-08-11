<?php
declare(strict_types=1);

namespace HddLand\Bot\Middleware;

use HddLand\Bot\Context;

interface MiddlewareInterface
{
    /**
     * @param callable(Context): void $next
     */
    public function handle(Context $ctx, callable $next): void;
}
