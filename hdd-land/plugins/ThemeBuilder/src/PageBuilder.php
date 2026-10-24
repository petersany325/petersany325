<?php

namespace Plugins\ThemeBuilder\src;

use App\Support\SettingsStore;
use Illuminate\Support\Facades\Schema;
use Plugins\ThemeBuilder\src\Models\BuilderPage;

class PageBuilder
{
    public const SETTINGS_KEY = 'studio_builder_pages';

    /** @var list<array<string, mixed>>|null */
    protected static ?array $memory = null;

    public static function ensureSchema(): void
    {
        try {
            if (! class_exists(Schema::class) || Schema::hasTable('builder_pages')) {
                return;
            }
            Schema::create('builder_pages', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug', 190)->index();
                $table->string('path', 190)->index();
                $table->string('status', 20)->default('publish');
                $table->string('seo_title', 255)->nullable();
                $table->text('seo_description')->nullable();
                $table->string('menu_label', 190)->nullable();
                $table->string('menu_group', 80)->nullable();
                $table->longText('blocks')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable) {
            //
        }
    }

    public static function ensureHomepage(): ?object
    {
        self::ensureReady();
        $row = self::findByPath('/');

        return $row ? self::asObject($row) : null;
    }

    public static function ensureReady(): void
    {
        self::ensureSchema();
        self::seedStudioPages();
    }

    public static function seedStudioPages(): void
    {
        if (! class_exists(StudioPageCatalog::class)) {
            return;
        }
        $existing = [];
        foreach (self::all() as $page) {
            $existing[self::normPath((string) ($page['path'] ?? ''))] = true;
        }
        foreach (StudioPageCatalog::all() as $spec) {
            $path = self::normPath((string) ($spec['path'] ?? ''));
            if ($path === '' || isset($existing[$path])) {
                continue;
            }
            self::save($spec);
            $existing[$path] = true;
        }
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        if (self::$memory !== null) {
            return self::$memory;
        }
        if (self::dbReady()) {
            try {
                $rows = [];
                foreach (BuilderPage::query()->orderBy('id')->get() as $model) {
                    $rows[] = self::fromModel($model);
                }
                self::$memory = $rows;

                return $rows;
            } catch (\Throwable) {
                //
            }
        }

        $raw = [];
        try {
            $raw = SettingsStore::get(self::SETTINGS_KEY, []);
        } catch (\Throwable) {
            $raw = [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        self::$memory = is_array($raw) ? array_values($raw) : [];

        return self::$memory;
    }

    /** @return array<string, mixed>|null */
    public static function findById(int|string $id): ?array
    {
        $id = (string) $id;
        foreach (self::all() as $page) {
            if ((string) ($page['id'] ?? '') === $id) {
                return $page;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function findByPath(string $path): ?array
    {
        $path = self::normPath($path);
        foreach (self::all() as $page) {
            if (self::normPath((string) ($page['path'] ?? '')) === $path) {
                return $page;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        $slug = trim($slug, '/');
        foreach (self::all() as $page) {
            if ((string) ($page['slug'] ?? '') === $slug) {
                return $page;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function findPublishedByPath(string $path): ?array
    {
        $page = self::findByPath($path);
        if ($page && ($page['status'] ?? '') === 'publish') {
            return $page;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function save(array $input): array
    {
        $page = self::normalize($input);
        if (self::dbReady()) {
            try {
                $model = null;
                if (! empty($page['id'])) {
                    $model = BuilderPage::query()->find($page['id']);
                }
                if (! $model && ! empty($page['path'])) {
                    $model = BuilderPage::query()->where('path', $page['path'])->first();
                }
                $payload = $page;
                unset($payload['id']);
                if ($model) {
                    $model->fill($payload);
                    $model->save();
                } else {
                    $model = BuilderPage::query()->create($payload);
                }
                self::$memory = null;

                return self::fromModel($model);
            } catch (\Throwable) {
                //
            }
        }

        $all = self::all();
        $found = false;
        foreach ($all as $i => $row) {
            $sameId = ! empty($page['id']) && (string) ($row['id'] ?? '') === (string) $page['id'];
            $samePath = self::normPath((string) ($row['path'] ?? '')) === $page['path'];
            if ($sameId || ($page['id'] === null && $samePath)) {
                $page['id'] = $row['id'] ?? $page['id'];
                $all[$i] = array_merge($row, $page);
                $found = true;
                $page = $all[$i];
                break;
            }
        }
        if (! $found) {
            if (empty($page['id'])) {
                $max = 0;
                foreach ($all as $row) {
                    $max = max($max, (int) ($row['id'] ?? 0));
                }
                $page['id'] = $max + 1;
            }
            $all[] = $page;
        }
        self::persistSettings($all);

        return $page;
    }

    public static function delete(int|string $id): void
    {
        $id = (string) $id;
        if (self::dbReady()) {
            try {
                BuilderPage::query()->where('id', $id)->delete();
            } catch (\Throwable) {
                //
            }
        }
        $all = array_values(array_filter(self::all(), static fn (array $row): bool => (string) ($row['id'] ?? '') !== $id));
        self::persistSettings($all);
    }

    public static function normPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/' || $path === 'index.php') {
            return '/';
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return rtrim($path, '/') ?: '/';
    }

    public static function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;

        return $html;
    }

    public static function mediaUrl(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $src) || str_starts_with($src, '/')) {
            return $src;
        }

        return asset($src);
    }

    /** @param  list<array<string, mixed>>  $pages */
    protected static function persistSettings(array $pages): void
    {
        self::$memory = array_values($pages);
        try {
            SettingsStore::set(self::SETTINGS_KEY, self::$memory);
        } catch (\Throwable) {
            //
        }
    }

    protected static function dbReady(): bool
    {
        try {
            return class_exists(BuilderPage::class)
                && class_exists(Schema::class)
                && Schema::hasTable('builder_pages');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected static function normalize(array $input): array
    {
        $blocks = $input['blocks'] ?? [];
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true) ?: [];
        }
        if (! is_array($blocks)) {
            $blocks = [];
        }
        $clean = [];
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') === 'richtext') {
                $block['html'] = self::sanitizeHtml((string) ($block['html'] ?? ''));
            }
            $clean[] = $block;
        }

        $path = self::normPath((string) ($input['path'] ?? '/'));
        $slug = trim((string) ($input['slug'] ?? ''), '/');
        if ($slug === '') {
            $slug = trim(str_replace('/', '-', $path), '-') ?: 'home';
        }

        return [
            'id' => $input['id'] ?? null,
            'title' => trim((string) ($input['title'] ?? 'صفحه')),
            'slug' => $slug,
            'path' => $path,
            'status' => ($input['status'] ?? 'publish') === 'draft' ? 'draft' : 'publish',
            'seo_title' => trim((string) ($input['seo_title'] ?? '')),
            'seo_description' => trim((string) ($input['seo_description'] ?? '')),
            'menu_label' => trim((string) ($input['menu_label'] ?? $input['title'] ?? '')),
            'menu_group' => trim((string) ($input['menu_group'] ?? 'سایت')),
            'blocks' => $clean,
        ];
    }

    /** @return array<string, mixed> */
    protected static function fromModel(BuilderPage $model): array
    {
        return [
            'id' => $model->id,
            'title' => (string) $model->title,
            'slug' => (string) $model->slug,
            'path' => self::normPath((string) $model->path),
            'status' => (string) ($model->status ?: 'publish'),
            'seo_title' => (string) ($model->seo_title ?? ''),
            'seo_description' => (string) ($model->seo_description ?? ''),
            'menu_label' => (string) ($model->menu_label ?? $model->title),
            'menu_group' => (string) ($model->menu_group ?? 'سایت'),
            'blocks' => is_array($model->blocks) ? $model->blocks : [],
        ];
    }

    /** @param  array<string, mixed>  $page */
    protected static function asObject(array $page): object
    {
        return new class($page)
        {
            /** @param  array<string, mixed>  $page */
            public function __construct(private array $page) {}

            public function __get(string $name): mixed
            {
                return $this->page[$name] ?? null;
            }

            public function isPublished(): bool
            {
                return ($this->page['status'] ?? '') === 'publish';
            }

            /** @return list<array<string, mixed>> */
            public function widgets(): array
            {
                return is_array($this->page['blocks'] ?? null) ? array_values($this->page['blocks']) : [];
            }
        };
    }
}
