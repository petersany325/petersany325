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
}
