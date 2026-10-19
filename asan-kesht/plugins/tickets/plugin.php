<?php

declare(strict_types=1);

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'tickets',
            'name' => 'تیکت مشتری و کارمند',
            'description' => 'گفتگوی پشتیبانی بین مشتری و کارکنان',
        ];
    }

    public function install(Database $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_tickets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                subject VARCHAR(180) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS ak_ticket_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX (ticket_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function boot(App $app): void
    {
        $app->listen('admin.menu', function (array $items): array {
            $items[] = ['href' => '/admin/tickets', 'label' => 'تیکت‌ها', 'roles' => ['admin', 'employee']];
            $items[] = ['href' => '/admin/tickets/new', 'label' => 'ایجاد تیکت', 'roles' => ['admin', 'employee']];
            $items[] = ['href' => '/admin/tickets?status=customer', 'label' => 'پاسخ مشتری', 'roles' => ['admin', 'employee']];
            $items[] = ['href' => '/admin/tickets?status=open', 'label' => 'تیکت باز', 'roles' => ['admin', 'employee']];
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_tickets WHERE status IN ("open","customer")');
            $cards[] = ['label' => 'تیکت نیازمند پاسخ', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/admin/tickets?status=customer'];
            $cards[] = ['label' => 'ایجاد تیکت', 'value' => 'جدید', 'href' => '/admin/tickets/new'];
            return $cards;
        });
        $app->listen('account.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user) {
                return $cards;
            }
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_tickets WHERE user_id = ?', [(int) $user['id']]);
            $cards[] = ['label' => 'تیکت‌های من', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/account/tickets'];
            $cards[] = ['label' => 'ایجاد تیکت', 'value' => 'جدید', 'href' => '/account/tickets/new'];
            return $cards;
        });
        $app->listen('site.aside', function (array $items) use ($app): array {
            $user = $app->auth->user();
            if (!$user) {
                return $items;
            }
            $role = (string) $user['role'];
            if (in_array($role, ['customer', 'representative'], true)) {
                $items[] = ['href' => '/account/tickets', 'label' => 'تیکت‌ها'];
                $items[] = ['href' => '/account/tickets/new', 'label' => 'ایجاد تیکت'];
            }
            if (in_array($role, ['admin', 'employee'], true)) {
                $items[] = ['href' => '/admin/tickets', 'label' => 'تیکت‌ها'];
                $items[] = ['href' => '/admin/tickets/new', 'label' => 'ایجاد تیکت'];
            }
            return $items;
        });
        $app->router->get('/account/tickets', function () use ($app): void {
            $user = require_user($app);
            $filter = $this->filterKey((string) ($_GET['status'] ?? 'all'), false);
            $params = [(int) $user['id']];
            $sql = 'SELECT * FROM ak_tickets WHERE user_id = ?';
            $sql .= $this->filterSql($filter, false, $params);
            $rows = $app->db->all($sql . ' ORDER BY updated_at DESC', $params);
            $app->view->plugin('tickets', 'customer', [
                'title' => 'تیکت‌ها',
                'rows' => $rows,
                'tabs' => $this->tabs(false),
                'counts' => $this->counts($app, (int) $user['id']),
                'current' => $filter,
                'base' => '/account/tickets',
            ], 'layouts/site');
        });
        $app->router->get('/account/tickets/new', function () use ($app): void {
            require_user($app);
            $app->view->plugin('tickets', 'create', [
                'title' => 'ایجاد تیکت',
            ], 'layouts/site');
        });
        $app->router->post('/account/tickets/new', function () use ($app): void {
            $this->createForCustomer($app);
        });
        $app->router->post('/account/tickets', function () use ($app): void {
            $this->createForCustomer($app);
        });
        $app->router->get('/account/tickets/{id}', function (array $params) use ($app): void {
            $user = require_user($app);
            $ticket = $this->ticketFor($app, (int) $params['id'], $user);
            $app->view->plugin('tickets', 'show', [
                'title' => (string) $ticket['subject'],
                'ticket' => $ticket,
                'messages' => $this->messages($app, (int) $ticket['id']),
                'action' => '/account/tickets/' . $ticket['id'],
            ], 'layouts/site');
        });
        $app->router->post('/account/tickets/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = require_user($app);
            $ticket = $this->ticketFor($app, (int) $params['id'], $user);
            $this->reply($app, $ticket, $user, false);
        });
        $app->router->get('/admin/tickets', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $filter = $this->filterKey((string) ($_GET['status'] ?? 'active'), true);
            $params = [];
            $sql = 'SELECT t.*, u.name FROM ak_tickets t JOIN ak_users u ON u.id = t.user_id';
            $where = $this->filterSql($filter, true, $params);
            if ($where !== '') {
                $sql .= ' WHERE ' . ltrim($where, ' AND');
            }
            $rows = $app->db->all($sql . ' ORDER BY t.updated_at DESC LIMIT 200', $params);
            $app->view->plugin('tickets', 'staff', [
                'title' => 'تیکت‌ها',
                'rows' => $rows,
                'tabs' => $this->tabs(true),
                'counts' => $this->counts($app, null),
                'current' => $filter,
                'base' => '/admin/tickets',
            ]);
        });
        $app->router->get('/admin/tickets/new', function () use ($app): void {
            require_role($app, ['admin', 'employee']);
            $app->view->plugin('tickets', 'create', [
                'title' => 'ایجاد تیکت',
                'staff' => true,
                'clients' => $this->clients($app),
                'statuses' => ticket_statuses(),
            ]);
        });
        $app->router->post('/admin/tickets/new', function () use ($app): void {
            csrf_check();
            $user = require_role($app, ['admin', 'employee']);
            $subject = post_string('subject', 180);
            $body = post_string('body', 4000);
            $ownerId = (int) ($_POST['user_id'] ?? 0);
            $client = $app->db->one(
                'SELECT id, phone FROM ak_users WHERE id = ? AND active = 1 AND role IN ("customer","representative")',
                [$ownerId]
            );
            $status = (string) ($_POST['status'] ?? 'open');
            if (!isset(ticket_statuses()[$status])) {
                $status = 'open';
            }
            if (!$client || mb_strlen($subject) < 3 || mb_strlen($body) < 3) {
                flash('error', 'مشتری، موضوع و متن تیکت را کامل کنید.');
                redirect('/admin/tickets/new');
            }
            $id = $this->openTicket($app, $ownerId, (int) $user['id'], $subject, $body, $status);
            if (!empty($client['phone'])) {
                $sms = $app->get('sms');
                if ($sms instanceof NiazpardazClient && $sms->configured()) {
                    $sms->send((string) $client['phone'], 'یک تیکت پشتیبانی در آسان‌کشت برای شما ثبت شد. شماره ' . $id, 'ticket');
                }
            }
            flash('success', 'تیکت ثبت شد.');
            redirect('/admin/tickets/' . $id);
        });
        $app->router->get('/admin/tickets/{id}', function (array $params) use ($app): void {
            require_role($app, ['admin', 'employee']);
            $ticket = $this->ticket($app, (int) $params['id']);
            $app->view->plugin('tickets', 'show', [
                'title' => (string) $ticket['subject'],
                'ticket' => $ticket,
                'messages' => $this->messages($app, (int) $ticket['id']),
                'action' => '/admin/tickets/' . $ticket['id'],
                'staff' => true,
                'statuses' => ticket_statuses(),
            ], 'layouts/admin');
        });
        $app->router->post('/admin/tickets/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = require_role($app, ['admin', 'employee']);
            $ticket = $this->ticket($app, (int) $params['id']);
            $next = (string) ($_POST['status'] ?? '');
            if ($next !== '' && isset(ticket_statuses()[$next]) && post_string('body', 4000) === '') {
                $app->db->run('UPDATE ak_tickets SET status = ?, updated_at = NOW() WHERE id = ?', [$next, (int) $ticket['id']]);
                flash('success', 'وضعیت تیکت شد: ' . ticket_status_label($next));
                redirect('/admin/tickets/' . $ticket['id']);
            }
            $this->reply($app, $ticket, $user, true);
        });
    }

    private function ticket(App $app, int $id): array
    {
        $ticket = $app->db->one('SELECT t.*, u.name, u.phone FROM ak_tickets t JOIN ak_users u ON u.id = t.user_id WHERE t.id = ?', [$id]);
        if (!$ticket) {
            redirect('/admin/tickets');
        }
        return $ticket;
    }

    private function ticketFor(App $app, int $id, array $user): array
    {
        $ticket = $app->db->one('SELECT * FROM ak_tickets WHERE id = ? AND user_id = ?', [$id, (int) $user['id']]);
        if (!$ticket && in_array($user['role'], ['admin', 'employee'], true)) {
            return $this->ticket($app, $id);
        }
        if (!$ticket) {
            redirect('/account/tickets');
        }
        return $ticket;
    }

    private function messages(App $app, int $id): array
    {
        return $app->db->all(
            'SELECT m.*, u.name, u.role FROM ak_ticket_messages m JOIN ak_users u ON u.id = m.user_id WHERE m.ticket_id = ? ORDER BY m.id',
            [$id]
        );
    }

    private function createForCustomer(App $app): void
    {
        csrf_check();
        $user = require_user($app);
        $subject = post_string('subject', 180);
        $body = post_string('body', 4000);
        if (mb_strlen($subject) < 3 || mb_strlen($body) < 3) {
            flash('error', 'موضوع و متن تیکت را بنویسید.');
            redirect('/account/tickets/new');
        }
        $id = $this->openTicket($app, (int) $user['id'], (int) $user['id'], $subject, $body, 'open');
        flash('success', 'تیکت ثبت شد.');
        redirect('/account/tickets/' . $id);
    }

    private function openTicket(App $app, int $ownerId, int $authorId, string $subject, string $body, string $status): int
    {
        return (int) $app->db->transaction(function () use ($app, $ownerId, $authorId, $subject, $body, $status): int {
            $app->db->run(
                'INSERT INTO ak_tickets (user_id, subject, status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
                [$ownerId, $subject, $status]
            );
            $id = (int) $app->db->id();
            $app->db->run(
                'INSERT INTO ak_ticket_messages (ticket_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
                [$id, $authorId, $body]
            );
            return $id;
        });
    }

    /** @return list<array<string, mixed>> */
    private function clients(App $app): array
    {
        return $app->db->all(
            'SELECT id, name, role FROM ak_users WHERE active = 1 AND role IN ("customer","representative") ORDER BY name LIMIT 400'
        );
    }

    private function reply(App $app, array $ticket, array $user, bool $staff): void
    {
        $body = post_string('body', 4000);
        if (mb_strlen($body) < 2) {
            flash('error', 'متن پاسخ را بنویسید.');
            redirect(($staff ? '/admin/tickets/' : '/account/tickets/') . $ticket['id']);
        }
        if ($ticket['status'] === 'closed') {
            flash('error', 'این تیکت بسته است.');
            redirect(($staff ? '/admin/tickets/' : '/account/tickets/') . $ticket['id']);
        }
        $app->db->run(
            'INSERT INTO ak_ticket_messages (ticket_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
            [(int) $ticket['id'], (int) $user['id'], $body]
        );
        $chosen = (string) ($_POST['status'] ?? '');
        if ($staff && isset(ticket_statuses()[$chosen])) {
            $status = $chosen;
        } else {
            $status = $staff ? 'answered' : 'customer';
        }
        $app->db->run('UPDATE ak_tickets SET status = ?, updated_at = NOW() WHERE id = ?', [$status, (int) $ticket['id']]);
        if ($staff && !empty($ticket['phone'])) {
            $sms = $app->get('sms');
            if ($sms instanceof NiazpardazClient && $sms->configured()) {
                $sms->send((string) $ticket['phone'], 'پاسخ تیکت شما در آسان‌کشت ثبت شد. شماره ' . $ticket['id'], 'ticket');
            }
        }
        flash('success', 'پاسخ ثبت شد.');
        redirect(($staff ? '/admin/tickets/' : '/account/tickets/') . $ticket['id']);
    }

    /** @return array<string, string> */
    private function tabs(bool $staff): array
    {
        if ($staff) {
            return [
                'active' => 'فعال',
                'open' => 'باز',
                'customer' => 'پاسخ مشتری',
                'answered' => 'پاسخ داده‌شده',
                'progress' => 'در حال انجام',
                'hold' => 'در انتظار',
                'closed' => 'بسته',
                'all' => 'همه',
            ];
        }
        return [
            'all' => 'همه',
            'flow' => 'در جریان',
            'answered' => 'پاسخ داده‌شده',
            'closed' => 'بسته',
        ];
    }

    private function filterKey(string $value, bool $staff): string
    {
        $tabs = $this->tabs($staff);
        if ($value === '' || !isset($tabs[$value])) {
            return $staff ? 'active' : 'all';
        }
        return $value;
    }

    /** @param list<int|string> $params */
    private function filterSql(string $filter, bool $staff, array &$params): string
    {
        if ($filter === 'all') {
            return '';
        }
        if ($filter === 'active') {
            return ' AND t.status != "closed"';
        }
        if (!$staff && $filter === 'flow') {
            return ' AND status NOT IN ("answered","closed")';
        }
        $column = $staff ? 't.status' : 'status';
        $params[] = $filter;
        return ' AND ' . $column . ' = ?';
    }

    /** @return array<string, int> */
    private function counts(App $app, ?int $userId): array
    {
        $sql = 'SELECT status, COUNT(*) AS c FROM ak_tickets';
        $params = [];
        if ($userId !== null) {
            $sql .= ' WHERE user_id = ?';
            $params[] = $userId;
        }
        $counts = array_fill_keys(array_keys(ticket_statuses()), 0);
        $total = 0;
        foreach ($app->db->all($sql . ' GROUP BY status', $params) as $row) {
            $count = (int) $row['c'];
            $total += $count;
            $status = (string) $row['status'];
            if (isset($counts[$status])) {
                $counts[$status] = $count;
            }
        }
        $counts['all'] = $total;
        $counts['active'] = $total - $counts['closed'];
        $counts['flow'] = $total - $counts['answered'] - $counts['closed'];
        return $counts;
    }
};

/** @return array<string, string> */
function ticket_statuses(): array
{
    return [
        'open' => 'باز',
        'customer' => 'پاسخ مشتری',
        'answered' => 'پاسخ داده‌شده',
        'progress' => 'در حال انجام',
        'hold' => 'در انتظار',
        'closed' => 'بسته',
    ];
}

function ticket_status_label(string $status): string
{
    return ticket_statuses()[$status] ?? $status;
}
