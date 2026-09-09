<?php

namespace App\Support;

/**
 * Minimal plugin base for the sparse HDD Land tree.
 * Production may already ship a richer BasePlugin; this covers
 * id/name/boot/routes/views registration hooks used by storefront plugins.
 */
abstract class BasePlugin
{
    abstract public function id(): string;

    abstract public function name(): string;

    public function description(): string
    {
        return '';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isCore(): bool
    {
        return false;
    }

    public function boot(): void
    {
        //
    }

    /** @return list<string> */
    public function adminMenu(): array
    {
        return [];
    }
}
