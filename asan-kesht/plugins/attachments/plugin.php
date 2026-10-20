<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

return new class extends Plugin {
    public function meta(): array
    {
        return [
            'id' => 'attachments',
            'name' => 'پیوست نامه',
            'description' => 'پیوست اختیاری PDF و JPG و TXT برای بخشنامه، تیکت و درخواست. نامه بدون فایل همچنان ارسال می‌شود',
        ];
    }

    public function install(Database $db): void
    {
        attachment_column($db, 'ak_circulars');
        attachment_column($db, 'ak_ticket_messages');
        attachment_column($db, 'ak_dealer_requests');
    }

    public function boot(App $app): void
    {
        attachment_column($app->db, 'ak_circulars');
        attachment_column($app->db, 'ak_ticket_messages');
        attachment_column($app->db, 'ak_dealer_requests');
        $app->listen('admin.settings', function (array $items): array {
            $items[] = [
                'href' => '/admin/plugins',
                'title' => 'پیوست نامه',
                'text' => 'در بخشنامه، تیکت و درخواست نمایندگی می‌توان PDF، JPG یا TXT تا ۴ مگابایت پیوست کرد. بدون فایل، متن تنها ارسال می‌شود. دانلود فقط از مسیر وروددار است.',
            ];
            return $items;
        });
        $app->router->get('/files/{name}', function (array $params) use ($app): void {
            attachment_send($app, (string) ($params['name'] ?? ''));
        });
    }
};
