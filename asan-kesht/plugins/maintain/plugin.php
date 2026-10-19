<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'maintain',
            'name' => 'نگهداری سایت',
            'description' => 'پاک کردن کش، تعمیر سایت و پایگاه، و پشتیبان',
        ];
    }

    public function boot(App $app): void
    {
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/maintain', 'label' => 'نگهداری', 'roles' => ['admin']];
            return $items;
        });
        $app->listen('admin.settings', function (array $items) use ($app): array {
            $count = count(maintain_backups($app));
            $items[] = [
                'href' => '/admin/maintain',
                'title' => 'نگهداری',
                'text' => 'کش، تعمیر سایت، تعمیر پایگاه و پشتیبان. پشتیبان ذخیره‌شده: ' . fa_digits((string) $count),
            ];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $count = count(maintain_backups($app));
            $cards[] = [
                'label' => 'پشتیبان سایت',
                'value' => fa_digits((string) $count),
                'href' => '/admin/maintain',
                'roles' => ['admin'],
            ];
            return $cards;
        });
        $app->router->get('/admin/maintain', function () use ($app): void {
            require_role($app, ['admin']);
            $report = $_SESSION['maintain_report'] ?? null;
            unset($_SESSION['maintain_report']);
            $app->view->plugin('maintain', 'index', [
                'title' => 'نگهداری سایت',
                'status' => maintain_status($app),
                'backups' => maintain_backups($app),
                'report' => is_array($report) ? $report : null,
            ]);
        });
        $app->router->post('/admin/maintain/cache', function () use ($app): void {
            $this->act($app, 'کش', function () use ($app): array {
                return maintain_clear_cache($app);
            }, 'کش پاک شد.');
        });
        $app->router->post('/admin/maintain/repair-site', function () use ($app): void {
            $this->act($app, 'تعمیر سایت', function () use ($app): array {
                return maintain_repair_site($app);
            }, 'بررسی سایت انجام شد.');
        });
        $app->router->post('/admin/maintain/repair-db', function () use ($app): void {
            $this->act($app, 'تعمیر پایگاه', function () use ($app): array {
                return maintain_repair_database($app);
            }, 'بررسی پایگاه انجام شد.');
        });
        $app->router->post('/admin/maintain/backup', function () use ($app): void {
            $this->act($app, 'پشتیبان', function () use ($app): array {
                $made = maintain_backup($app);
                return [[
                    'ok' => true,
                    'text' => $made['name'] . ' — ' . maintain_size((int) $made['bytes']) . ' — ' . fa_digits((string) $made['files']) . ' فایل',
                ]];
            }, 'پشتیبان سایت ساخته شد.');
        });
        $app->router->get('/admin/maintain/backup/{name}', function (array $params) use ($app): void {
            require_role($app, ['admin']);
            try {
                maintain_send($app, (string) ($params['name'] ?? ''));
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/admin/maintain');
            }
        });
        $app->router->post('/admin/maintain/backup/delete', function () use ($app): void {
            csrf_check();
            require_role($app, ['admin']);
            if (!isset($_POST['confirm'])) {
                flash('error', 'حذف پشتیبان را تأیید کنید.');
                redirect('/admin/maintain');
            }
            try {
                $path = maintain_backup_file($app, post_string('name', 80));
                if (!@unlink($path)) {
                    throw new RuntimeException('پشتیبان حذف نشد.');
                }
                ak_audit($app, 'نگهداری', 'حذف پشتیبان', basename($path));
                flash('success', 'پشتیبان حذف شد.');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
            }
            redirect('/admin/maintain');
        });
    }

    private function act(App $app, string $subject, callable $work, string $success): void
    {
        csrf_check();
        require_role($app, ['admin']);
        try {
            $lines = $work();
            $_SESSION['maintain_report'] = ['title' => $subject, 'lines' => $lines];
            $bad = 0;
            foreach ($lines as $line) {
                if (is_array($line) && empty($line['ok'])) {
                    $bad++;
                }
            }
            ak_audit($app, 'نگهداری', $subject, $bad > 0 ? fa_digits((string) $bad) . ' مورد نیاز به توجه' : 'انجام شد');
            flash($bad > 0 ? 'error' : 'success', $bad > 0 ? $success . ' چند مورد نیاز به توجه دارد.' : $success);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        } catch (Throwable $e) {
            log_line($app->root, 'maintain ' . $subject . ' ' . $e->getMessage());
            flash('error', 'این کار انجام نشد.');
        }
        redirect('/admin/maintain');
    }
};
