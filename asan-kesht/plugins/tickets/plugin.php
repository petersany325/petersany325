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
            return $items;
        });
        $app->listen('dashboard.cards', function (array $cards) use ($app): array {
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_tickets WHERE status = "open"');
            $cards[] = ['label' => 'تیکت باز', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/admin/tickets'];
            return $cards;
        });
        $app->listen('account.cards', function (array $cards) use ($app): array {
            $user = $app->auth->user();
            if (!$user) {
                return $cards;
            }
            $row = $app->db->one('SELECT COUNT(*) AS c FROM ak_tickets WHERE user_id = ?', [(int) $user['id']]);
            $cards[] = ['label' => 'تیکت‌های من', 'value' => fa_digits((string) ($row['c'] ?? 0)), 'href' => '/account/tickets'];
            return $cards;
        });
        $app->router->get('/account/tickets', function () use ($app): void {
            $user = require_user($app);
            $rows = $app->db->all('SELECT * FROM ak_tickets WHERE user_id = ? ORDER BY updated_at DESC', [(int) $user['id']]);
            $app->view->plugin('tickets', 'customer', ['title' => 'تیکت‌ها', 'rows' => $rows], 'layouts/site');
        });
        $app->router->post('/account/tickets', function () use ($app): void {
            csrf_check();
            $user = require_user($app);
            $subject = post_string('subject', 180);
            $body = post_string('body', 4000);
            if (mb_strlen($subject) < 3 || mb_strlen($body) < 3) {
                flash('error', 'موضوع و متن تیکت را بنویسید.');
                redirect('/account/tickets');
            }
            $app->db->transaction(function () use ($app, $user, $subject, $body): void {
                $app->db->run(
                    'INSERT INTO ak_tickets (user_id, subject, status, created_at, updated_at) VALUES (?, ?, "open", NOW(), NOW())',
                    [(int) $user['id'], $subject]
                );
                $app->db->run(
                    'INSERT INTO ak_ticket_messages (ticket_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
                    [(int) $app->db->id(), (int) $user['id'], $body]
                );
            });
            flash('success', 'تیکت ثبت شد.');
            redirect('/account/tickets');
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
            $rows = $app->db->all(
                'SELECT t.*, u.name FROM ak_tickets t JOIN ak_users u ON u.id = t.user_id ORDER BY t.updated_at DESC LIMIT 200'
            );
            $app->view->plugin('tickets', 'staff', ['title' => 'تیکت‌ها', 'rows' => $rows]);
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
            ], 'layouts/admin');
        });
        $app->router->post('/admin/tickets/{id}', function (array $params) use ($app): void {
            csrf_check();
            $user = require_role($app, ['admin', 'employee']);
            $ticket = $this->ticket($app, (int) $params['id']);
            if (isset($_POST['close'])) {
                $app->db->run('UPDATE ak_tickets SET status = "closed", updated_at = NOW() WHERE id = ?', [(int) $ticket['id']]);
                flash('success', 'تیکت بسته شد.');
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
        $status = $staff ? 'answered' : 'open';
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
};
