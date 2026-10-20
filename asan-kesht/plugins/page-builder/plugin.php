<?php

declare(strict_types=1);

require_once __DIR__ . '/blocks.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'page-builder',
            'name' => 'صفحه‌ساز',
            'description' => 'چیدن بلوک‌های صفحه، ترتیب، انتشار و پیوند منو',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_pages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                status VARCHAR(20) NOT NULL,
                in_menu TINYINT(1) NOT NULL DEFAULT 0,
                menu_order INT NOT NULL DEFAULT 0,
                blocks_json MEDIUMTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $home = [
            pb_block('catalog', pb_catalog_data()),
            pb_block('machines', ['title' => 'مدل‌های آماده', 'limit' => '4']),
            pb_block('steps', [
                'title' => 'از سفارش تا تحویل',
                'items' => [
                    ['title' => 'نیاز زمین', 'text' => 'توان تراکتور، عرض کار و نوع خاک'],
                    ['title' => 'ساخت شاسی', 'text' => 'برش و جوش سازه فولادی'],
                    ['title' => 'مونتاژ', 'text' => 'ادوات سوار، کششی یا نیمه‌سوار'],
                    ['title' => 'کنترل', 'text' => 'بازدید جوش، چرخش و رنگ'],
                    ['title' => 'تحویل', 'text' => 'تنظیم اولیه و معرفی قطعات سایشی'],
                ],
            ]),
            pb_block('cta', [
                'title' => 'برای همین مدل پیش‌فاکتور بگیرید',
                'text' => 'استان، توان تراکتور و مدل را بنویسید تا درخواست در پنل بماند.',
                'button_label' => 'درخواست پیش‌فاکتور',
                'button_url' => '/contact',
            ]),
        ];
        $about = [
            pb_block('text', [
                'title' => 'کارخانه ادوات، نه ویترین واسطه',
                'body' => 'آسان‌کشت ماشین‌آلات صنعتی کشاورزی می‌سازد. تمرکز روی ادواتی است که به تراکتور بسته می‌شوند: خاک‌ورز، کارنده، سمپاش و تجهیزات حمل. هر مدل با بازه توان و عرض کار مشخص می‌شود و پیش از خروج بازدید می‌شود.',
            ]),
            pb_block('columns', [
                'title' => 'توان ساخت',
                'items' => [
                    ['title' => 'طراحی شاسی', 'text' => 'متناسب با توان تراکتور و شرایط زمین'],
                    ['title' => 'جوش و مونتاژ', 'text' => 'سازه فولادی، یاتاقان و قطعات سایشی'],
                    ['title' => 'خدمات', 'text' => 'قطعه یدکی مدل‌های جاری و پاسخ به تیکت'],
                ],
            ]),
        ];
        $contact = [
            pb_block('quote', [
                'title' => 'درخواست پیش‌فاکتور',
                'text' => 'مشخصات زمین و تراکتور را بنویسید. درخواست برای کارمندان در پنل می‌ماند.',
            ]),
        ];
        $this->insertPage($db, 'خانه', 'home', 0, 0, $home);
        $this->insertPage($db, 'کارخانه', 'about', 1, 20, $about);
        $this->insertPage($db, 'تماس', 'contact', 1, 30, $contact);
    }

    private function insertPage(Database $db, string $title, string $slug, int $menu, int $order, array $blocks): void
    {
        $db->run(
            'INSERT IGNORE INTO ak_pages (title, slug, status, in_menu, menu_order, blocks_json, updated_at) VALUES (?, ?, "published", ?, ?, ?, NOW())',
            [$title, $slug, $menu, $order, json_encode($blocks, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function boot(App $app): void
    {
        $this->applyNavyHome($app);
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/pages', 'label' => 'صفحه‌ساز', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('site.toolbar', function (array $items) use ($app): array {
            $path = trim(request_path(), '/');
            $slug = $path === '' ? 'home' : $path;
            if (str_contains($slug, '/')) {
                return $items;
            }
            $page = $app->db->one('SELECT id FROM ak_pages WHERE slug = ?', [$slug]);
            if (!$page) {
                return $items;
            }
            array_unshift($items, [
                'href' => '/admin/pages/' . (int) $page['id'],
                'label' => 'ویرایش این صفحه',
                'primary' => true,
            ]);
            return $items;
        });
        $app->listen('site.nav', function (array $items) use ($app): array {
            $pages = $app->db->all('SELECT title, slug FROM ak_pages WHERE status = "published" AND in_menu = 1 ORDER BY menu_order, id');
            foreach ($pages as $page) {
                $items[] = ['href' => '/' . $page['slug'], 'label' => (string) $page['title']];
            }
            return $items;
        });
        $app->listen('route.fallback', function (array $payload) use ($app): array {
            if (!empty($payload['handled'])) {
                return $payload;
            }
            $path = trim((string) $payload['path'], '/');
            $slug = $path === '' ? 'home' : $path;
            if ($slug !== 'home' && reserved_slug($slug)) {
                return $payload;
            }
            $page = $app->db->one('SELECT * FROM ak_pages WHERE slug = ? AND status = "published"', [$slug]);
            if (!$page) {
                return $payload;
            }
            $blocks = json_decode((string) $page['blocks_json'], true);
            $app->view->plugin('page-builder', 'page', [
                'title' => (string) $page['title'],
                'blocks' => is_array($blocks) ? $blocks : [],
            ], 'layouts/site');
            $payload['handled'] = true;
            return $payload;
        });
        $app->router->get('/admin/pages', function () use ($app): void {
            require_role($app, ['admin']);
            $pages = $app->db->all('SELECT id, title, slug, status, in_menu FROM ak_pages ORDER BY menu_order, id');
            $app->view->plugin('page-builder', 'index', ['title' => 'صفحه‌ها', 'pages' => $pages]);
        });
        $app->router->get('/admin/pages/new', function () use ($app): void {
            require_role($app, ['admin']);
            $app->view->plugin('page-builder', 'edit', [
                'title' => 'صفحه تازه',
                'page' => ['id' => 0, 'title' => '', 'slug' => '', 'status' => 'draft', 'in_menu' => 0, 'menu_order' => 0],
                'blocks' => [pb_blank('text')],
                'definitions' => pb_definitions(),
            ]);
        });
        $app->router->get('/admin/pages/{id}', function (array $params) use ($app): void {
            require_role($app, ['admin']);
            $page = $this->page($app, (string) $params['id']);
            $blocks = json_decode((string) $page['blocks_json'], true);
            $app->view->plugin('page-builder', 'edit', [
                'title' => 'ویرایش صفحه',
                'page' => $page,
                'blocks' => is_array($blocks) ? $blocks : [],
                'definitions' => pb_definitions(),
            ]);
        });
        $app->router->post('/admin/pages/{id}', function (array $params) use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            $id = (int) $params['id'];
            $posted = $_POST['blocks'] ?? [];
            $blocks = pb_from_post(is_array($posted) ? $posted : []);
            $action = (string) ($_POST['action'] ?? 'save');
            if (str_starts_with($action, 'up:') || str_starts_with($action, 'down:') || str_starts_with($action, 'delete:') || str_starts_with($action, 'dup:')) {
                [$op, $index] = explode(':', $action, 2);
                $index = (int) $index;
                if ($op === 'delete') {
                    array_splice($blocks, $index, 1);
                } elseif ($op === 'dup' && isset($blocks[$index])) {
                    array_splice($blocks, $index + 1, 0, [$blocks[$index]]);
                } elseif ($op === 'up' && $index > 0 && isset($blocks[$index])) {
                    [$blocks[$index - 1], $blocks[$index]] = [$blocks[$index], $blocks[$index - 1]];
                } elseif ($op === 'down' && isset($blocks[$index + 1])) {
                    [$blocks[$index + 1], $blocks[$index]] = [$blocks[$index], $blocks[$index + 1]];
                }
            }
            if ($action === 'add') {
                $type = (string) ($_POST['add_type'] ?? 'text');
                if (isset(pb_definitions()[$type])) {
                    $blocks[] = pb_blank($type);
                }
            }
            $title = post_string('title', 180);
            $slug = slugify(post_string('slug', 80));
            $status = post_string('status', 20) === 'published' ? 'published' : 'draft';
            $inMenu = isset($_POST['in_menu']) ? 1 : 0;
            $order = (int) post_string('menu_order', 6);
            if ($title === '' || $slug === '' || reserved_slug($slug)) {
                flash('error', 'عنوان و نامک انگلیسی لازم است و نامک نباید رزرو شده باشد.');
                redirect($id > 0 ? '/admin/pages/' . $id : '/admin/pages/new');
            }
            $previous = $id > 0 ? $this->page($app, (string) $id) : null;
            $json = json_encode($blocks, JSON_UNESCAPED_UNICODE);
            if ($id === 0) {
                $app->db->run(
                    'INSERT INTO ak_pages (title, slug, status, in_menu, menu_order, blocks_json, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                    [$title, $slug, $status, $inMenu, $order, $json]
                );
                $id = (int) $app->db->id();
            } else {
                $app->db->run(
                    'UPDATE ak_pages SET title = ?, slug = ?, status = ?, in_menu = ?, menu_order = ?, blocks_json = ?, updated_at = NOW() WHERE id = ?',
                    [$title, $slug, $status, $inMenu, $order, $json, $id]
                );
            }
            if ($app->pluginEnabled('menus') && function_exists('menu_sync_page')) {
                menu_sync_page(
                    $app,
                    $previous ? (string) $previous['slug'] : '',
                    $slug,
                    $previous ? (string) $previous['title'] : '',
                    $title,
                    $status === 'published' && $inMenu === 1,
                    $order
                );
            }
            flash('success', $status === 'published'
                ? 'صفحه ذخیره شد و روی سایت زنده دیده می‌شود.'
                : 'صفحه به‌صورت پیش‌نویس ذخیره شد و تا انتشار روی سایت زنده دیده نمی‌شود.');
            redirect('/admin/pages/' . $id);
        });
    }

    private function applyNavyHome(App $app): void
    {
        if (setting($app, 'page-builder', 'home_layout', '') === 'navy-catalog') {
            return;
        }
        $home = $app->db->one('SELECT id, blocks_json FROM ak_pages WHERE slug = ?', ['home']);
        $blocks = [];
        if ($home) {
            $decoded = json_decode((string) $home['blocks_json'], true);
            $blocks = is_array($decoded) ? $decoded : [];
        }
        if ($blocks !== []) {
            save_setting($app, 'page-builder', 'home_layout', 'navy-catalog');
            return;
        }
        if ($home) {
            $app->db->run(
                'UPDATE ak_pages SET blocks_json = ?, updated_at = NOW() WHERE id = ?',
                [json_encode([pb_block('catalog', pb_catalog_data())], JSON_UNESCAPED_UNICODE), (int) $home['id']]
            );
        }
        save_setting($app, 'page-builder', 'home_layout', 'navy-catalog');
    }

    private function page(App $app, string $id): array
    {
        if (!ctype_digit($id)) {
            redirect('/admin/pages');
        }
        $page = $app->db->one('SELECT * FROM ak_pages WHERE id = ?', [(int) $id]);
        if (!$page) {
            redirect('/admin/pages');
        }
        return $page;
    }
};

function pb_block(string $type, array $data): array
{
    $blank = pb_blank($type);
    $blank['data'] = array_merge($blank['data'], $data);
    return $blank;
}
