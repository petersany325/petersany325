<?php

declare(strict_types=1);

final class App
{
    /** @var array<string, Plugin> */
    private array $plugins = [];

    /** @var array<string, list<callable>> */
    private array $listeners = [];

    /** @var array<string, mixed> */
    private array $services = [];

    private function __construct(
        public Database $db,
        public Router $router,
        public View $view,
        public Auth $auth,
        public array $config,
        public string $root,
    ) {
    }

    public static function boot(string $root): self
    {
        $configFile = $root . '/config.php';
        if (!is_file($configFile)) {
            redirect_install();
        }
        /** @var array $config */
        $config = require $configFile;
        try {
            $db = Database::connect($config['db']);
        } catch (Throwable $e) {
            log_line($root, 'db ' . $e->getMessage());
            http_response_code(500);
            echo 'اتصال به پایگاه داده برقرار نشد.';
            exit;
        }
        $view = new View($root);
        $app = new self($db, new Router(), $view, new Auth($db), $config, $root);
        $view->setApp($app);
        return $app;
    }

    public function set(string $key, mixed $value): void
    {
        $this->services[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->services[$key] ?? $default;
    }

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function filter(string $event, mixed $value): mixed
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $value = $listener($value);
        }
        return $value;
    }

    public function pluginEnabled(string $id): bool
    {
        $row = $this->db->one('SELECT enabled FROM ak_plugins WHERE id = ?', [$id]);
        return $row ? (int) $row['enabled'] === 1 : false;
    }

    public function loadPlugins(): void
    {
        foreach (glob($this->root . '/plugins/*/plugin.php') ?: [] as $file) {
            $plugin = require $file;
            if (!$plugin instanceof Plugin) {
                continue;
            }
            $meta = $plugin->meta();
            $this->plugins[$meta['id']] = $plugin;
            $this->db->run(
                'INSERT INTO ak_plugins (id, name, description, enabled) VALUES (?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)',
                [$meta['id'], $meta['name'], $meta['description']]
            );
        }
        foreach ($this->plugins as $id => $plugin) {
            if (!$this->pluginEnabled($id)) {
                continue;
            }
            $done = $this->db->one('SELECT id FROM ak_schema_migrations WHERE id = ?', [$id . ':install']);
            if (!$done) {
                // CREATE TABLE ends any open MySQL transaction, so install stays outside one.
                $plugin->install($this->db);
                $this->db->run(
                    'INSERT INTO ak_schema_migrations (id, applied_at) VALUES (?, NOW())',
                    [$id . ':install']
                );
            }
            $plugin->boot($this);
        }
    }

    /** @return array<string, Plugin> */
    public function plugins(): array
    {
        return $this->plugins;
    }

    public function run(): void
    {
        $this->registerCoreRoutes();
        $this->loadPlugins();
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = request_path();
        try {
            $handled = $this->router->dispatch($method, $path);
        } catch (Throwable $e) {
            log_line($this->root, $e::class . ' ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!empty($this->config['debug'])) {
                http_response_code(500);
                echo e($e->getMessage());
                return;
            }
            http_response_code(500);
            echo 'خطای داخلی. دوباره تلاش کنید.';
            return;
        }
        if ($handled) {
            return;
        }
        $fallback = $this->filter('route.fallback', ['path' => $path, 'handled' => false]);
        if (!empty($fallback['handled'])) {
            return;
        }
        http_response_code(404);
        $this->view->render('errors/404', ['title' => 'صفحه پیدا نشد'], 'layouts/site');
    }

    private function registerCoreRoutes(): void
    {
        $app = $this;
        $this->router->get('/login', function () use ($app): void {
            $user = $app->auth->user();
            if ($user) {
                redirect(landing_for_role((string) $user['role']));
            }
            $app->view->render('auth/login', ['title' => 'ورود']);
        });
        $this->router->post('/login/password', function () use ($app): void {
            csrf_check();
            $username = post_string('username', 60);
            $password = (string) ($_POST['password'] ?? '');
            $user = $app->auth->attemptPassword($username, $password);
            if (!$user) {
                flash('error', 'نام کاربری یا رمز نادرست است.');
                redirect('/login#pass');
            }
            $app->auth->login($user);
            redirect(landing_for_role((string) $user['role']));
        });
        $this->router->post('/logout', function () use ($app): void {
            csrf_check();
            $app->auth->logout();
            redirect('/');
        });
        $this->router->get('/admin', function () use ($app): void {
            $user = require_role($app, ['admin', 'employee', 'accountant', 'region_manager', 'representative']);
            if ($user['role'] === 'representative') {
                redirect('/rep');
            }
            $cards = $app->filter('dashboard.cards', []);
            if (!is_array($cards)) {
                $cards = [];
            }
            $cards = array_values(array_filter($cards, static function ($card) use ($app, $user): bool {
                if (!is_array($card)) {
                    return false;
                }
                $roles = $card['roles'] ?? ['admin', 'employee'];
                if (!is_array($roles)) {
                    return false;
                }
                $href = (string) ($card['href'] ?? '');
                $section = access_section_for($href);
                if ($section !== null) {
                    return access_can($app, $user, $section, $roles);
                }
                return in_array((string) $user['role'], $roles, true);
            }));
            $app->view->render('admin/dashboard', [
                'title' => 'پیشخوان',
                'cards' => is_array($cards) ? $cards : [],
            ], 'layouts/admin');
        });
        $this->router->get('/admin/access', function () use ($app): void {
            require_role($app, ['admin']);
            $app->view->render('admin/access', [
                'title' => 'دسترسی‌ها',
                'sections' => access_sections(),
                'matrix' => access_matrix($app),
                'roles' => ak_role_labels(),
                'users' => $app->db->all('SELECT id, name, role, active FROM ak_users WHERE role <> "admin" ORDER BY id DESC LIMIT 80'),
                'flags' => [],
            ], 'layouts/admin');
        });
        $this->router->post('/admin/access', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            access_save_matrix($app);
        });
        $this->router->post('/admin/access/user', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            access_save_user($app);
        });
        $this->router->get('/admin/users', function () use ($app): void {
            require_role($app, ['admin']);
            $users = $app->db->all('SELECT id, name, username, phone, role, active, created_at FROM ak_users ORDER BY id DESC LIMIT 200');
            $actor = $app->auth->user();
            $app->view->render('admin/users', [
                'title' => 'کاربران',
                'users' => $users,
                'actor_id' => (int) ($actor['id'] ?? 0),
            ], 'layouts/admin');
        });
        $this->router->post('/admin/users', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            admin_user_save($app, 0);
        });
        $this->router->get('/admin/users/{id}', function (array $params) use ($app): void {
            require_role($app, ['admin']);
            $id = (int) ($params['id'] ?? 0);
            $user = $app->db->one('SELECT id, name, username, phone, role, active FROM ak_users WHERE id = ?', [$id]);
            if (!$user) {
                flash('error', 'این کاربر پیدا نشد.');
                redirect('/admin/users');
            }
            $app->view->render('admin/user-form', [
                'title' => 'ویرایش کاربر',
                'person' => $user,
            ], 'layouts/admin');
        });
        $this->router->post('/admin/users/{id}/delete', function (array $params) use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            admin_user_delete($app, (int) ($params['id'] ?? 0));
        });
        $this->router->post('/admin/users/{id}', function (array $params) use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            admin_user_save($app, (int) ($params['id'] ?? 0));
        });
        $this->router->get('/admin/settings', function () use ($app): void {
            require_role($app, ['admin']);
            $items = $app->filter('admin.settings', [
                ['href' => '/admin/users', 'title' => 'کاربران', 'text' => 'افزودن، ویرایش و حذف کاربر، نقش و رمز'],
                ['href' => '/admin/plugins', 'title' => 'افزونه‌ها', 'text' => 'روشن یا خاموش کردن هر بخش، بدون پاک شدن داده‌ها'],
            ]);
            $app->view->render('admin/settings', [
                'title' => 'تنظیمات',
                'items' => is_array($items) ? $items : [],
            ], 'layouts/admin');
        });
        $this->router->get('/admin/plugins', function () use ($app): void {
            require_role($app, ['admin']);
            $rows = $app->db->all('SELECT * FROM ak_plugins ORDER BY name');
            $app->view->render('admin/plugins', ['title' => 'افزونه‌ها', 'rows' => $rows], 'layouts/admin');
        });
        $this->router->post('/admin/plugins/toggle', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            $id = post_string('id', 50);
            $row = $app->db->one('SELECT enabled FROM ak_plugins WHERE id = ?', [$id]);
            if ($row) {
                $app->db->run('UPDATE ak_plugins SET enabled = ? WHERE id = ?', [(int) $row['enabled'] === 1 ? 0 : 1, $id]);
                flash('success', 'وضعیت افزونه عوض شد.');
            }
            redirect('/admin/plugins');
        });
        $this->router->get('/admin/profile', function () use ($app): void {
            $user = require_role($app, ['admin', 'employee', 'accountant', 'region_manager', 'representative']);
            $app->view->render('admin/profile', ['title' => 'نمایه', 'user' => $user], 'layouts/admin');
        });
        $this->router->post('/admin/profile', function () use ($app): void {
            csrf_check();
            $user = require_role($app, ['admin', 'employee', 'accountant', 'region_manager', 'representative']);
            $name = post_string('name', 120);
            $phone = normalize_phone(post_string('phone', 20));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '') {
                flash('error', 'نام را بنویسید.');
                redirect('/admin/profile');
            }
            if ($phone !== '' && !is_mobile($phone)) {
                flash('error', 'شماره موبایل معتبر نیست.');
                redirect('/admin/profile');
            }
            $app->db->run(
                'UPDATE ak_users SET name = ?, phone = ? WHERE id = ?',
                [$name, $phone === '' ? null : $phone, (int) $user['id']]
            );
            if ($password !== '') {
                if (mb_strlen($password) < 8) {
                    flash('error', 'رمز حداقل ۸ نویسه باشد.');
                    redirect('/admin/profile');
                }
                $app->db->run('UPDATE ak_users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
            }
            flash('success', 'نمایه ذخیره شد.');
            redirect('/admin/profile');
        });
        $this->router->get('/account', function () use ($app): void {
            $user = require_user($app);
            $cards = $app->filter('account.cards', []);
            $app->view->render('account/home', [
                'title' => 'حساب من',
                'user' => $user,
                'cards' => is_array($cards) ? $cards : [],
            ]);
        });
    }
}

function redirect_install(): void
{
    header('Location: ' . url('/install.php'));
    exit;
}
