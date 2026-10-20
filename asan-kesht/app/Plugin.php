<?php

declare(strict_types=1);

abstract class Plugin
{
    /** @return array{id:string,name:string,description:string} */
    abstract public function meta(): array;

    public function install(Database $db): void
    {
    }

    public function boot(App $app): void
    {
    }
}
