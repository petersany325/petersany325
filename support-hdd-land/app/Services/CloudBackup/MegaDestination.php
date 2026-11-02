<?php

namespace App\Services\CloudBackup;

use App\Support\BackupSettings;

class MegaDestination
{
    /** @return array{ok:bool,message:string} */
    public function connect(): array
    {
        $cfg = BackupSettings::cloud('mega');
        $client = new MegaClient;
        $login = $client->login($cfg['email'], $cfg['password']);
        BackupSettings::saveCloudTokens('mega', [
            'connected' => (bool) ($login['ok'] ?? false),
            'account_email' => $cfg['email'],
        ]);

        return $login;
    }

    /** @return array{ok:bool,message:string} */
    public function test(): array
    {
        return $this->connect();
    }

    /** @return array{ok:bool,message:string} */
    public function upload(string $localPath): array
    {
        $cfg = BackupSettings::cloud('mega');
        if ($cfg['email'] === '' || $cfg['password'] === '') {
            return ['ok' => false, 'message' => 'ایمیل/رمز MEGA تنظیم نشده است.'];
        }
        $client = new MegaClient;
        $login = $client->login($cfg['email'], $cfg['password']);
        if (! ($login['ok'] ?? false)) {
            BackupSettings::saveCloudTokens('mega', ['connected' => false]);

            return $login;
        }
        BackupSettings::saveCloudTokens('mega', ['connected' => true, 'account_email' => $cfg['email']]);

        return $client->upload($localPath, $cfg['folder'] ?: 'HDDLAND-Backups');
    }
}
