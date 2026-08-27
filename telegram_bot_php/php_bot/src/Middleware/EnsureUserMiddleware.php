<?php
declare(strict_types=1);

namespace HddLand\Bot\Middleware;

use HddLand\Bot\Context;
use HddLand\Bot\Repositories\UserRepository;

final class EnsureUserMiddleware implements MiddlewareInterface
{
    public function handle(Context $ctx, callable $next): void
    {
        if ($ctx->userId > 0 && $ctx->from) {
            UserRepository::ensure($ctx->from);
            // Schema heal is handled lightly by HealthRepair on a TTL — avoid double full ensure every update
            $ctx->lang = UserRepository::lang($ctx->userId);
        }
        $next($ctx);
    }
}
