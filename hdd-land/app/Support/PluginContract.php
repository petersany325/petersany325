<?php

namespace App\Support;

interface PluginContract
{
    public function id(): string;

    public function name(): string;

    public function description(): string;

    public function version(): string;

    /** @return list<string> */
    public function dependencies(): array;

    public function isCore(): bool;

    public function install(): void;

    public function uninstall(): void;

    /** @return list<string> */
    public function migrationPaths(): array;

    /** @return list<array{label:string,route:string,icon?:string,group?:string}> */
    public function adminMenu(): array;

    public function boot(): void;
}
