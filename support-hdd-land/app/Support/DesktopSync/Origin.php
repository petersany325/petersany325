<?php

namespace App\Support\DesktopSync;

class Origin
{
    public static function isDesktop(): bool
    {
        return app()->bound('sync.origin') && app('sync.origin') === 'desktop';
    }
}
