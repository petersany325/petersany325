<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'menus',
            'name' => 'مگا منو',
            'description' => 'ساخت منو و زیرمنو، پیوند، مگا پنل و افکت باز شدن',
        ];
    }

    public function install(Database $db): void
    {
        menu_install($db);
    }

    public function boot(App $app): void
    {
        menu_extend($app);
        $app->listen('site.nav.ready', function (array $items) use ($app): array {
            $tree = menu_public_tree($app);
            return $tree !== [] ? $tree : $items;
        });
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/menus', 'label' => 'مگا منو', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('site.toolbar', function (array $items): array {
            $items[] = ['href' => '/admin/menus', 'label' => 'مگا منو'];
            return $items;
        });
        $app->listen('admin.settings', function (array $items) use ($app): array {
            $count = $app->db->one('SELECT COUNT(*) AS c FROM ak_menu_items WHERE visible = 1');
            $items[] = [
                'href' => '/admin/menus',
                'title' => 'مگا منو',
                'text' => 'عنوان، پیوند، ستون، زیرمنو، ترتیب و نمایش. آیتم نمایان: ' . fa_digits((string) ($count['c'] ?? 0)),
            ];
            return $items;
        });
        $app->router->get('/admin/menus', function () use ($app): void {
            require_role($app, ['admin']);
            $rows = menu_rows($app);
            $app->view->plugin('menus', 'index', [
                'title' => 'مگا منو',
                'rows' => $rows,
                'tree' => menu_tree_from($rows, 0, false),
                'options' => menu_options($app),
                'parent' => (int) ($_GET['parent'] ?? 0),
            ]);
        });
        $app->router->post('/admin/menus/settings', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            menu_save_options($app);
        });
        $app->router->post('/admin/menus', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            menu_save($app);
        });
        $app->router->post('/admin/menus/delete', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            menu_delete($app);
        });
        $app->router->post('/admin/menus/import', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            menu_import_pages($app);
        });
    }
};
