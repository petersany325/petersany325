<?php
declare(strict_types=1);

namespace HddLand\Bot\Middleware;

use HddLand\Bot\Context;

final class Pipeline
{
    /** @var list<MiddlewareInterface> */
    private array $stack = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * @param callable(Context): void $destination
     */
    public function process(Context $ctx, callable $destination): void
    {
        $pipeline = array_reduce(
            array_reverse($this->stack),
            static function (callable $next, MiddlewareInterface $middleware): callable {
                return static function (Context $ctx) use ($middleware, $next): void {
                    $middleware->handle($ctx, $next);
                };
            },
            $destination
        );
        $pipeline($ctx);
    }
}
