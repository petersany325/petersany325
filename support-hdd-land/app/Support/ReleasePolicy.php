<?php

namespace App\Support;

/**
 * The main shop (empty license key) is where a change is tested.
 * The installable ZIP is only for customer shops, and only after that test.
 */
class ReleasePolicy
{
    public static function isMainSite(): bool
    {
        return trim((string) config('license.key')) === '';
    }

    public static function mayInstallCustomerPackage(): bool
    {
        return ! self::isMainSite();
    }

    /**
     * Opting out keeps the previous stable version. A first publish with the
     * box unchecked must not become the version customers are offered.
     */
    public static function stableLatest(bool $setLatest, string $version, string $previous): string
    {
        if ($setLatest) {
            return $version;
        }

        return trim($previous);
    }
}
