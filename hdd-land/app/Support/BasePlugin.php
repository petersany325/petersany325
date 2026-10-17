<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use ReflectionClass;

/**
 * Production plugin base: contract + route/view/migration wiring.
 * Includes basePath() alias required by plugins (e.g. SupportTickets).
 */
abstract class BasePlugin implements PluginContract
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

    /** @return list<string> */
    public function dependencies(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return false;
    }

    public function install(): void
    {
        //
    }

    public function uninstall(): void
    {
        //
    }

    /** @return list<string> */
    public function migrationPaths(): array
    {
        $path = $this->pluginPath().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        return is_dir($path) ? [$path] : [];
    }

    /** @return list<string> */
    public function adminMenu(): array
    {
        return [];
    }

    public function boot(): void
    {
        $this->bootPluginViews();
        $this->bootPluginRoutes();
    }

    /** Absolute filesystem path to this plugin directory. */
    protected function pluginPath(): string
    {
        return dirname((new ReflectionClass($this))->getFileName());
    }

    /**
     * Alias used by several plugins (SupportTickets, etc.).
     * Must remain public — plugins call $this->basePath() from boot().
     */
    public function basePath(string $path = ''): string
    {
        $base = $this->pluginPath();
        $path = ltrim(str_replace(['\\', '..'], ['/', ''], $path), '/');

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /** @deprecated alias */
    public function path(string $path = ''): string
    {
        return $this->basePath($path);
    }

    protected function bootPluginViews(): void
    {
        $views = $this->pluginPath().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
        if (! is_dir($views)) {
            return;
        }

        $id = $this->id();
        View::addNamespace($id, $views);
        $folder = basename($this->pluginPath());
        View::addNamespace(strtolower($folder), $views);
        View::addNamespace(str_replace('-', '', $id), $views);
    }

    protected function bootPluginRoutes(): void
    {
        $routesDir = $this->pluginPath().DIRECTORY_SEPARATOR.'routes';
        if (! is_dir($routesDir)) {
            return;
        }

        $web = $routesDir.DIRECTORY_SEPARATOR.'web.php';
        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $admin = $routesDir.DIRECTORY_SEPARATOR.'admin.php';
        if (is_file($admin)) {
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group($admin);
        }

        $staff = $routesDir.DIRECTORY_SEPARATOR.'staff.php';
        if (is_file($staff)) {
            Route::middleware(['web', 'auth', 'staff'])
                ->prefix('staff')
                ->name('staff.')
                ->group($staff);
        }
    }
}
