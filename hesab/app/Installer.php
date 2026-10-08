<?php
declare(strict_types=1);

final class Installer
{
    public static function isInstalled(): bool
    {
        return is_file(__DIR__ . '/../config.php') && is_file(__DIR__ . '/../storage/.installed');
    }

    public static function run(array $db, string $adminName, string $adminEmail, string $adminPass): void
    {
        $name = str_replace('`', '``', $db['name']);
        // Prefer direct DB connect (cPanel users usually lack CREATE DATABASE privilege).
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']);
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            $dsn = sprintf('mysql:host=%s;charset=utf8mb4', $db['host']);
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . $name . '`');
        }

        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }

        $config = [
            'app_name' => 'حساب HDD',
            'base_url' => rtrim($db['base_url'] ?? 'https://hesab.hdd-land.ir', '/'),
            'timezone' => 'Asia/Tehran',
            'db' => [
                'host' => $db['host'],
                'name' => $db['name'],
                'user' => $db['user'],
                'pass' => $db['pass'],
                'charset' => 'utf8mb4',
            ],
        ];
        $export = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents(__DIR__ . '/../config.php', $export);

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
        $st->execute([$adminName, $adminEmail, $hash, 'admin']);

        $pdo->prepare('INSERT INTO fiscal_years (title, start_date, end_date, is_active) VALUES (?,?,?,1)')
            ->execute(['سال مالی ۱۴۰۵', '2026-03-21', '2027-03-20']);

        self::seedCoding($pdo);
        file_put_contents(__DIR__ . '/../storage/.installed', date('c'));
    }

    public static function seedCoding(PDO $pdo): void
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../data/coding.json'), true);
        $groupIds = [];
        $kolIds = [];

        $insG = $pdo->prepare('INSERT INTO account_groups (code, title, nature) VALUES (?,?,?)');
        foreach ($json['groups'] as $g) {
            $insG->execute([$g['code'], $g['title'], $g['nature']]);
            $groupIds[$g['code']] = (int) $pdo->lastInsertId();
        }

        $insK = $pdo->prepare('INSERT INTO accounts_kol (code, title, group_id) VALUES (?,?,?)');
        foreach ($json['kols'] as $k) {
            if (empty($k['title']) || !isset($groupIds[$k['group_code']])) {
                continue;
            }
            $insK->execute([$k['code'], $k['title'], $groupIds[$k['group_code']]]);
            $kolIds[$k['code']] = (int) $pdo->lastInsertId();
        }

        $insM = $pdo->prepare('INSERT INTO accounts_moein (code, title, kol_id) VALUES (?,?,?)');
        foreach ($json['moeins'] as $m) {
            if (empty($m['title']) || !isset($kolIds[$m['kol_code']])) {
                continue;
            }
            $insM->execute([$m['code'], $m['title'], $kolIds[$m['kol_code']]]);
        }

        $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?)')
            ->execute(['coding_seeded', '1']);
    }
}
