<?php

namespace App\Services;

use App\Support\BackupSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class DatabaseBackupService
{
    public function directory(): string
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0750, true);
        }
        $ht = $dir.'/.htaccess';
        if (! is_file($ht)) {
            File::put($ht, "Require all denied\nDeny from all\n");
        }

        return $dir;
    }

    /** @return list<array{name:string,path:string,size:int,mtime:int,scope:string}> */
    public function listLocal(): array
    {
        $dir = $this->directory();
        $files = collect(File::files($dir))
            ->filter(fn ($f) => preg_match('/\.sql(\.gz)?$/i', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        return $files->map(function ($f) {
            $name = $f->getFilename();

            return [
                'name' => $name,
                'path' => $f->getPathname(),
                'size' => $f->getSize(),
                'mtime' => $f->getMTime(),
                'scope' => str_contains($name, '-accounting-') ? 'accounting' : 'full',
            ];
        })->all();
    }

    /** @return array{ok:bool,message:string,file?:string,path?:string,details?:list<string>} */
    public function create(string $scope = 'full', bool $gzip = true): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (! $this->isMysql()) {
            return ['ok' => false, 'message' => 'بکاپ فقط برای MySQL/MariaDB پشتیبانی می‌شود.'];
        }

        $scope = $scope === 'accounting' ? 'accounting' : 'full';
        $stamp = now('Asia/Tehran')->format('Ymd_His');
        $base = 'hddland-'.$scope.'-'.$stamp.'.sql';
        $path = $this->directory().'/'.$base;
        $details = [];

        try {
            $tables = $scope === 'accounting'
                ? $this->filterExisting(BackupSettings::accountingTables())
                : $this->listTables();

            if ($tables === []) {
                return ['ok' => false, 'message' => 'جدولی برای بکاپ یافت نشد.'];
            }

            $fh = fopen($path, 'wb');
            if (! $fh) {
                return ['ok' => false, 'message' => 'امکان نوشتن فایل بکاپ نیست.'];
            }

            $db = DB::connection()->getDatabaseName();
            $this->write($fh, "-- HDD LAND database backup\n");
            $this->write($fh, '-- Generated: '.now('Asia/Tehran')->toDateTimeString()." (Asia/Tehran)\n");
            $this->write($fh, "-- Database: {$db}\n");
            $this->write($fh, "-- Scope: {$scope}\n");
            $this->write($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($tables as $table) {
                $create = DB::select('SHOW CREATE TABLE `'.$this->qi($table).'`');
                $createSql = $create[0]->{'Create Table'} ?? ($create[0]->{'Create View'} ?? null);
                if (! $createSql) {
                    $details[] = "✗ ساختار {$table} خوانده نشد";
                    continue;
                }
                $this->write($fh, "DROP TABLE IF EXISTS `{$this->qi($table)}`;\n");
                $this->write($fh, $createSql.";\n\n");

                $count = (int) DB::table($table)->count();
                $details[] = "✓ {$table} ({$count} ردیف)";
                if ($count === 0) {
                    continue;
                }

                $chunk = 200;
                $offset = 0;
                while ($offset < $count) {
                    $rows = DB::table($table)->limit($chunk)->offset($offset)->get();
                    if ($rows->isEmpty()) {
                        break;
                    }
                    $cols = array_keys((array) $rows->first());
                    $colList = implode(',', array_map(fn ($c) => '`'.$this->qi($c).'`', $cols));
                    $values = [];
                    foreach ($rows as $row) {
                        $vals = [];
                        foreach ((array) $row as $v) {
                            $vals[] = $this->sqlValue($v);
                        }
                        $values[] = '('.implode(',', $vals).')';
                    }
                    $this->write($fh, "INSERT INTO `{$this->qi($table)}` ({$colList}) VALUES\n".implode(",\n", $values).";\n");
                    $offset += $chunk;
                }
                $this->write($fh, "\n");
            }

            $this->write($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);

            $finalPath = $path;
            $finalName = $base;
            if ($gzip && function_exists('gzencode')) {
                $gzPath = $path.'.gz';
                $raw = File::get($path);
                File::put($gzPath, gzencode($raw, 6));
                @unlink($path);
                $finalPath = $gzPath;
                $finalName = $base.'.gz';
            }

            $this->pruneLocal(BackupSettings::all()['keep_local']);

            return [
                'ok' => true,
                'message' => 'بکاپ ساخته شد: '.$finalName,
                'file' => $finalName,
                'path' => $finalPath,
                'details' => $details,
            ];
        } catch (Throwable $e) {
            if (isset($fh) && is_resource($fh)) {
                fclose($fh);
            }
            @unlink($path);

            return ['ok' => false, 'message' => 'ساخت بکاپ ناموفق: '.$e->getMessage(), 'details' => $details];
        }
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function restoreFromPath(string $path, bool $confirm): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (! $confirm) {
            return ['ok' => false, 'message' => 'برای ریستور باید تأیید را فعال کنید.'];
        }
        if (! is_file($path)) {
            return ['ok' => false, 'message' => 'فایل بکاپ یافت نشد.'];
        }
        if (! $this->isMysql()) {
            return ['ok' => false, 'message' => 'ریستور فقط برای MySQL/MariaDB پشتیبانی می‌شود.'];
        }

        try {
            $sql = $this->readSqlFile($path);
            if (trim($sql) === '') {
                return ['ok' => false, 'message' => 'فایل بکاپ خالی است.'];
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $statements = $this->splitSql($sql);
            $ok = 0;
            $fail = 0;
            $details = [];
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                try {
                    DB::unprepared($stmt);
                    $ok++;
                } catch (Throwable $e) {
                    $fail++;
                    if (count($details) < 12) {
                        $details[] = '✗ '.mb_substr($e->getMessage(), 0, 160);
                    }
                }
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return [
                'ok' => $fail === 0,
                'message' => $fail === 0
                    ? "ریستور موفق ({$ok} دستور SQL)."
                    : "ریستور با {$fail} خطا از {$ok} دستور انجام شد.",
                'details' => $details,
            ];
        } catch (Throwable $e) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
            }

            return ['ok' => false, 'message' => 'ریستور ناموفق: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function restoreUpload(UploadedFile $file, bool $confirm): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['sql', 'gz'], true) && ! str_ends_with(strtolower($file->getClientOriginalName()), '.sql.gz')) {
            return ['ok' => false, 'message' => 'فقط فایل .sql یا .sql.gz پذیرفته می‌شود.'];
        }

        $tmp = $this->directory().'/upload_'.Str::random(8).'_'.$file->getClientOriginalName();
        $file->move(dirname($tmp), basename($tmp));
        $result = $this->restoreFromPath($tmp, $confirm);
        @unlink($tmp);

        return $result;
    }

    public function absolutePath(string $name): ?string
    {
        $name = basename($name);
        if (! preg_match('/^[a-zA-Z0-9._-]+\.sql(\.gz)?$/', $name)) {
            return null;
        }
        $path = $this->directory().'/'.$name;

        return is_file($path) ? $path : null;
    }

    public function deleteLocal(string $name): array
    {
        $path = $this->absolutePath($name);
        if (! $path) {
            return ['ok' => false, 'message' => 'فایل یافت نشد.'];
        }
        @unlink($path);

        return ['ok' => true, 'message' => 'فایل بکاپ حذف شد.'];
    }

    /** @return array{ok:bool,message:string} */
    public function uploadToRemote(string $localPath): array
    {
        $cfg = BackupSettings::all();
        if (! $cfg['remote_enabled']) {
            return ['ok' => false, 'message' => 'آپلود ریموت غیرفعال است.'];
        }
        if ($cfg['remote_host'] === '' || $cfg['remote_user'] === '') {
            return ['ok' => false, 'message' => 'آدرس هاست یا نام کاربری ریموت تنظیم نشده.'];
        }
        if (! is_file($localPath)) {
            return ['ok' => false, 'message' => 'فایل محلی برای آپلود نیست.'];
        }

        $port = $cfg['remote_port'] ?: 21;
        $ssl = $cfg['remote_protocol'] === 'ftps';
        if ($ssl && ! function_exists('ftp_ssl_connect')) {
            return ['ok' => false, 'message' => 'FTPS روی این سرور پشتیبانی نمی‌شود؛ پروتکل FTP را انتخاب کنید.'];
        }
        $conn = $ssl ? @ftp_ssl_connect($cfg['remote_host'], $port, 30) : @ftp_connect($cfg['remote_host'], $port, 30);
        if (! $conn) {
            return ['ok' => false, 'message' => 'اتصال به هاست بکاپ برقرار نشد ('.$cfg['remote_host'].').'];
        }

        if (! @ftp_login($conn, $cfg['remote_user'], $cfg['remote_password'])) {
            ftp_close($conn);

            return ['ok' => false, 'message' => 'ورود FTP ناموفق بود.'];
        }

        @ftp_pasv($conn, true);
        $remoteDir = trim($cfg['remote_path'] ?: '/backups');
        if ($remoteDir !== '' && $remoteDir !== '/') {
            $this->ftpEnsureDir($conn, $remoteDir);
            @ftp_chdir($conn, $remoteDir);
        }

        $remoteName = basename($localPath);
        $ok = @ftp_put($conn, $remoteName, $localPath, FTP_BINARY);
        ftp_close($conn);

        return $ok
            ? ['ok' => true, 'message' => 'بکاپ روی هاست ریموت آپلود شد: '.$remoteDir.'/'.$remoteName]
            : ['ok' => false, 'message' => 'آپلود FTP ناموفق بود.'];
    }

    /** @return array{ok:bool,message:string,file?:string,details?:list<string>} */
    public function runScheduled(): array
    {
        $cfg = BackupSettings::all();
        if (! $cfg['enabled']) {
            return ['ok' => false, 'message' => 'بکاپ خودکار خاموش است.'];
        }

        $created = $this->create($cfg['scope'], true);
        if (! ($created['ok'] ?? false)) {
            BackupSettings::markResult(false, $created['message'] ?? 'خطا');

            return $created;
        }

        $messages = [$created['message']];
        if ($cfg['remote_enabled']) {
            $up = $this->uploadToRemote($created['path']);
            $messages[] = $up['message'];
            if (! ($up['ok'] ?? false)) {
                BackupSettings::markResult(false, implode(' | ', $messages), $created['file'] ?? null);

                return ['ok' => false, 'message' => implode(' | ', $messages), 'file' => $created['file'] ?? null, 'details' => $created['details'] ?? []];
            }
        }

        $msg = implode(' | ', $messages);
        BackupSettings::markResult(true, $msg, $created['file'] ?? null);

        return ['ok' => true, 'message' => $msg, 'file' => $created['file'] ?? null, 'details' => $created['details'] ?? []];
    }

    /** @return array{ok:bool,message:string} */
    public function testRemote(): array
    {
        $cfg = BackupSettings::all();
        if ($cfg['remote_host'] === '') {
            return ['ok' => false, 'message' => 'آدرس هاست را وارد کنید.'];
        }
        $port = $cfg['remote_port'] ?: 21;
        $ssl = $cfg['remote_protocol'] === 'ftps';
        if ($ssl && ! function_exists('ftp_ssl_connect')) {
            return ['ok' => false, 'message' => 'FTPS روی این سرور پشتیبانی نمی‌شود؛ پروتکل FTP را انتخاب کنید.'];
        }
        $conn = $ssl ? @ftp_ssl_connect($cfg['remote_host'], $port, 20) : @ftp_connect($cfg['remote_host'], $port, 20);
        if (! $conn) {
            return ['ok' => false, 'message' => 'اتصال به هاست برقرار نشد.'];
        }
        $login = @ftp_login($conn, $cfg['remote_user'], $cfg['remote_password']);
        ftp_close($conn);

        return $login
            ? ['ok' => true, 'message' => 'اتصال و ورود FTP موفق بود.']
            : ['ok' => false, 'message' => 'اتصال برقرار شد ولی ورود ناموفق بود.'];
    }

    private function pruneLocal(int $keep): void
    {
        $files = collect(File::files($this->directory()))
            ->filter(fn ($f) => preg_match('/\.sql(\.gz)?$/i', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();
        foreach ($files->slice($keep) as $old) {
            @unlink($old->getPathname());
        }
    }

    private function readSqlFile(string $path): string
    {
        if (str_ends_with(strtolower($path), '.gz')) {
            $raw = File::get($path);
            $decoded = @gzdecode($raw);

            return is_string($decoded) ? $decoded : '';
        }

        return File::get($path);
    }

    /** @return list<string> */
    private function splitSql(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            if ($inString) {
                $buffer .= $ch;
                if ($ch === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"') {
                $inString = true;
                $stringChar = $ch;
                $buffer .= $ch;
                continue;
            }
            if ($ch === ';') {
                $parts[] = $buffer;
                $buffer = '';
                continue;
            }
            $buffer .= $ch;
        }
        if (trim($buffer) !== '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    private function ftpEnsureDir($conn, string $path): void
    {
        $parts = array_filter(explode('/', str_replace('\\', '/', $path)));
        $cur = '';
        foreach ($parts as $part) {
            $cur .= '/'.$part;
            if (@ftp_chdir($conn, $cur)) {
                continue;
            }
            @ftp_mkdir($conn, $cur);
        }
    }

    private function write($fh, string $chunk): void
    {
        fwrite($fh, $chunk);
    }

    private function sqlValue(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        return DB::getPdo()->quote((string) $v);
    }

    private function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** @return list<string> */
    private function listTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /** @param list<string> $tables */
    private function filterExisting(array $tables): array
    {
        $existing = array_flip($this->listTables());

        return array_values(array_filter($tables, fn ($t) => isset($existing[$t])));
    }

    private function qi(string $name): string
    {
        return str_replace('`', '``', $name);
    }
}
