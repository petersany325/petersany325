<?php

namespace Demo;

use PDO;
use PDOException;

final class Store
{
    private PDO $pdo;
    private string $driver;

    public function __construct(array $cfg)
    {
        $this->driver = $cfg['driver'] ?? 'sqlite';
        if ($this->driver === 'mysql') {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $cfg['host'],
                    (int) ($cfg['port'] ?? 3306),
                    $cfg['database'],
                    $cfg['charset'] ?? 'utf8mb4'
                );
                $this->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                $this->driver = 'sqlite';
            }
        }
        if ($this->driver !== 'mysql') {
            $path = $cfg['sqlite_path'];
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->driver = 'sqlite';
        }
        $this->migrate();
        if ((int) $this->scalar('SELECT COUNT(*) FROM users') === 0) {
            $this->seed();
        }
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    private function migrate(): void
    {
        $id = $this->driver === 'mysql'
            ? 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
            : 'id INTEGER PRIMARY KEY AUTOINCREMENT';

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS meta (k VARCHAR(64) PRIMARY KEY, v TEXT)");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            {$id},
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(120) NULL,
            role VARCHAR(40) NOT NULL,
            role_label VARCHAR(80) NOT NULL,
            password VARCHAR(120) NOT NULL,
            is_active INT NOT NULL DEFAULT 1
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS customers (
            {$id},
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(20) NOT NULL UNIQUE,
            address TEXT NULL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
            {$id},
            code VARCHAR(40) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            device TEXT NOT NULL,
            status VARCHAR(40) NOT NULL,
            technician VARCHAR(120) NULL,
            amount INT NOT NULL DEFAULT 0,
            note TEXT NULL,
            created_at VARCHAR(40) NOT NULL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS parts (
            {$id},
            code VARCHAR(40) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            price INT NOT NULL DEFAULT 0
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS events (
            {$id},
            ticket_code VARCHAR(40) NULL,
            body TEXT NOT NULL,
            created_at VARCHAR(40) NOT NULL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS daily_logs (
            {$id},
            user_name VARCHAR(120) NOT NULL,
            service VARCHAR(120) NOT NULL,
            body TEXT NULL,
            created_at VARCHAR(40) NOT NULL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            {$id},
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            is_read INT NOT NULL DEFAULT 0,
            created_at VARCHAR(40) NOT NULL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS interns (
            {$id},
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            department VARCHAR(80) NULL,
            start_date VARCHAR(20) NOT NULL,
            end_date VARCHAR(20) NOT NULL,
            status VARCHAR(40) NOT NULL
        )");
    }

    private function seed(): void
    {
        $users = [
            ['مدیر سیستم', '09120000000', 'admin@demo.local', 'admin', 'مدیر', '1234'],
            ['رضا پذیرش', '09120000002', 'reception@demo.local', 'receptionist', 'پذیرش / حسابدار', '1234'],
            ['علی تعمیرکار', '09120000004', 'tech@demo.local', 'technician', 'تعمیرکار', '1234'],
            ['مینا حسابدار', '09120000007', 'acc@demo.local', 'accountant', 'حسابدار', '1234'],
            ['سارا کارآموز', '09120000008', 'intern@demo.local', 'intern', 'کارآموز', '1234'],
        ];
        $ins = $this->pdo->prepare('INSERT INTO users(name,phone,email,role,role_label,password,is_active) VALUES(?,?,?,?,?,?,1)');
        foreach ($users as $u) {
            $ins->execute($u);
        }

        $customers = [
            ['مریم رضایی', '09120000001', 'تهران'],
            ['کامران نوری', '09121112233', 'کرج'],
            ['شرکت آموت', '09123334455', 'تهران، سعادت‌آباد'],
            ['سارا محمدی', '09125556677', 'اصفهان'],
        ];
        $cins = $this->pdo->prepare('INSERT INTO customers(name,phone,address) VALUES(?,?,?)');
        foreach ($customers as $c) {
            $cins->execute($c);
        }

        $now = date('Y-m-d H:i:s');
        $tickets = [
            ['HL-1405-101', 1, 'هارد WD Blue 2TB — کلیک و صدای غیرعادی', 'ارجاع‌شده', 'علی تعمیرکار', 0, ''],
            ['HL-1405-102', 2, 'SSD NVMe 1TB — سیستم بالا نمی‌آید', 'دست تعمیر', 'علی تعمیرکار', 0, ''],
            ['HL-1405-103', 3, 'سرور RAID5 — یک دیسک آفلاین', 'منتظر تأیید هزینه', 'علی تعمیرکار', 18000000, 'بازیابی اطلاعات'],
            ['HL-1405-104', 4, 'هارد لپ‌تاپ 1TB — آب‌خوردگی', 'هزینه تأیید شد', 'علی تعمیرکار', 9500000, 'جراحی هارد'],
        ];
        $tins = $this->pdo->prepare('INSERT INTO tickets(code,customer_id,device,status,technician,amount,note,created_at) VALUES(?,?,?,?,?,?,?,?)');
        foreach ($tickets as $t) {
            $tins->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $now]);
        }

        $parts = [
            ['PCB-01', 'برد هارد', 12, 1200000],
            ['HEAD-02', 'هد هارد', 8, 2200000],
            ['SSD-128', 'SSD 128GB', 15, 1350000],
            ['CABLE-SATA', 'کابل ساتا', 40, 120000],
        ];
        $pins = $this->pdo->prepare('INSERT INTO parts(code,name,stock,price) VALUES(?,?,?,?)');
        foreach ($parts as $p) {
            $pins->execute($p);
        }

        $nins = $this->pdo->prepare('INSERT INTO notifications(title,body,is_read,created_at) VALUES(?,?,0,?)');
        $nins->execute(['ارجاع جدید', 'قبض HL-1405-101 منتظر تأیید دریافت تعمیرکار است.', $now]);
        $nins->execute(['تأیید هزینه', 'مشتری قبض HL-1405-104 را تأیید کرد.', $now]);
        $nins->execute(['پیام مشتری', 'مشتری درباره قبض HL-1405-103 سؤال فرستاده است.', $now]);

        $iins = $this->pdo->prepare('INSERT INTO interns(name,phone,department,start_date,end_date,status) VALUES(?,?,?,?,?,?)');
        $iins->execute(['سارا کارآموز', '09120000008', 'کارگاه', '1405/06/01', '1405/09/01', 'در حال کارآموزی']);
        $iins->execute(['نیما کارآموز', '09120000009', 'پذیرش', '1405/07/01', '1405/10/01', 'آینده']);

        $dins = $this->pdo->prepare('INSERT INTO daily_logs(user_name,service,body,created_at) VALUES(?,?,?,?)');
        $dins->execute(['سارا کارآموز', 'تست هارد', '۳ دستگاه تست سلامت شد', $now]);
        $dins->execute(['علی تعمیرکار', 'گزارش کار', 'PCB تعویض شد — HL-1405-102', $now]);

        $eins = $this->pdo->prepare('INSERT INTO events(ticket_code,body,created_at) VALUES(?,?,?)');
        $eins->execute(['HL-1405-104', 'لینک تأیید هزینه برای مشتری ارسال شد (دمو)', $now]);
        $eins->execute(['HL-1405-103', 'منتظر تأیید هزینه مشتری', $now]);
    }

    public function scalar(string $sql, array $params = [])
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchColumn();
    }

    public function all(string $sql, array $params = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function exec(string $sql, array $params = []): void
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function resetDemo(): void
    {
        foreach (['events', 'daily_logs', 'notifications', 'interns', 'parts', 'tickets', 'customers', 'users', 'meta'] as $t) {
            $this->pdo->exec('DELETE FROM ' . $t);
        }
        $this->seed();
    }

    public function nextTicketCode(): string
    {
        $n = (int) $this->scalar('SELECT COUNT(*) FROM tickets') + 101;

        return 'HL-1405-' . $n;
    }
}
