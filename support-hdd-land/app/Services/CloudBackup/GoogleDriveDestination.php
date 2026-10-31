<?php

namespace App\Services\CloudBackup;

use App\Support\BackupSettings;

class GoogleDriveDestination
{
    public function __construct(private HttpClient $http = new HttpClient)
    {
    }

    public function authUrl(string $state): ?string
    {
        $cfg = BackupSettings::cloud('google');
        if ($cfg['client_id'] === '') {
            return null;
        }

        $params = [
            'client_id' => $cfg['client_id'],
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file email openid',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    public function redirectUri(): string
    {
        return url('/settings/backup/cloud/google/callback');
    }

    /** @return array{ok:bool,message:string} */
    public function handleCallback(string $code): array
    {
        $cfg = BackupSettings::cloud('google');
        if ($cfg['client_id'] === '' || $cfg['client_secret'] === '') {
            return ['ok' => false, 'message' => 'Client ID/Secret گوگل ذخیره نشده است.'];
        }

        $res = $this->http->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (($res['status'] ?? 0) >= 400 || empty($json['access_token'])) {
            return ['ok' => false, 'message' => 'دریافت توکن گوگل ناموفق: '.($json['error_description'] ?? $json['error'] ?? 'unknown')];
        }

        $email = $this->fetchEmail((string) $json['access_token']);
        BackupSettings::saveCloudTokens('google', [
            'access_token' => (string) $json['access_token'],
            'refresh_token' => (string) ($json['refresh_token'] ?? $cfg['refresh_token']),
            'token_expires' => time() + (int) ($json['expires_in'] ?? 3600) - 60,
            'account_email' => $email,
            'connected' => true,
        ]);

        return ['ok' => true, 'message' => 'اتصال گوگل‌درایو برقرار شد'.($email ? ' ('.$email.')' : '')];
    }

    /** @return array{ok:bool,message:string} */
    public function test(): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return ['ok' => false, 'message' => 'گوگل‌درایو متصل نیست. ابتدا اتصال را برقرار کنید.'];
        }
        $res = $this->http->request('GET', 'https://www.googleapis.com/drive/v3/about?fields=user', null, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (($res['status'] ?? 0) >= 400) {
            return ['ok' => false, 'message' => 'تست گوگل ناموفق: '.($json['error']['message'] ?? 'HTTP '.$res['status'])];
        }
        $email = (string) ($json['user']['emailAddress'] ?? '');
        if ($email !== '') {
            BackupSettings::saveCloudTokens('google', ['account_email' => $email, 'connected' => true]);
        }

        return ['ok' => true, 'message' => 'اتصال گوگل‌درایو سالم است'.($email ? ' — '.$email : '')];
    }

    /** @return array{ok:bool,message:string} */
    public function upload(string $localPath): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return ['ok' => false, 'message' => 'گوگل‌درایو متصل نیست.'];
        }
        if (! is_file($localPath)) {
            return ['ok' => false, 'message' => 'فایل بکاپ برای آپلود به گوگل یافت نشد.'];
        }

        $cfg = BackupSettings::cloud('google');
        $folderId = $this->ensureFolder($token, $cfg['folder'] ?: 'HDDLAND-Backups');
        if (! $folderId) {
            return ['ok' => false, 'message' => 'ایجاد/یافتن پوشه گوگل‌درایو ناموفق بود.'];
        }

        $name = basename($localPath);
        $meta = json_encode([
            'name' => $name,
            'parents' => [$folderId],
        ], JSON_UNESCAPED_UNICODE);
        $boundary = 'hddland_'.bin2hex(random_bytes(8));
        $fileBody = file_get_contents($localPath);
        if ($fileBody === false) {
            return ['ok' => false, 'message' => 'خواندن فایل بکاپ ناموفق بود.'];
        }
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .$meta."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: application/octet-stream\r\n\r\n"
            .$fileBody."\r\n"
            ."--{$boundary}--";

        $res = $this->http->request(
            'POST',
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
            $body,
            [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'multipart/related; boundary='.$boundary,
            ],
            600
        );
        $json = json_decode($res['body'], true) ?: [];
        if (($res['status'] ?? 0) >= 400 || empty($json['id'])) {
            return ['ok' => false, 'message' => 'آپلود گوگل‌درایو ناموفق: '.($json['error']['message'] ?? 'HTTP '.$res['status'])];
        }

        return ['ok' => true, 'message' => 'آپلود به گوگل‌درایو موفق: '.$name];
    }

    private function accessToken(): ?string
    {
        $cfg = BackupSettings::cloud('google');
        if ($cfg['refresh_token'] === '' && $cfg['access_token'] === '') {
            return null;
        }
        if ($cfg['access_token'] !== '' && (int) $cfg['token_expires'] > time()) {
            return $cfg['access_token'];
        }
        if ($cfg['refresh_token'] === '' || $cfg['client_id'] === '' || $cfg['client_secret'] === '') {
            return $cfg['access_token'] !== '' ? $cfg['access_token'] : null;
        }

        $res = $this->http->postForm('https://oauth2.googleapis.com/token', [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'refresh_token' => $cfg['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (empty($json['access_token'])) {
            BackupSettings::saveCloudTokens('google', ['connected' => false]);

            return null;
        }
        BackupSettings::saveCloudTokens('google', [
            'access_token' => (string) $json['access_token'],
            'token_expires' => time() + (int) ($json['expires_in'] ?? 3600) - 60,
            'connected' => true,
        ]);

        return (string) $json['access_token'];
    }

    private function fetchEmail(string $accessToken): string
    {
        $res = $this->http->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', null, [
            'Authorization' => 'Bearer '.$accessToken,
        ]);
        $json = json_decode($res['body'], true) ?: [];

        return (string) ($json['email'] ?? '');
    }

    private function ensureFolder(string $token, string $name): ?string
    {
        $q = "mimeType='application/vnd.google-apps.folder' and name='".str_replace("'", "\\'", $name)."' and trashed=false";
        $url = 'https://www.googleapis.com/drive/v3/files?'.http_build_query([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);
        $res = $this->http->request('GET', $url, null, ['Authorization' => 'Bearer '.$token]);
        $json = json_decode($res['body'], true) ?: [];
        if (! empty($json['files'][0]['id'])) {
            return (string) $json['files'][0]['id'];
        }

        $create = $this->http->postJson('https://www.googleapis.com/drive/v3/files', [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ], ['Authorization' => 'Bearer '.$token]);
        $created = json_decode($create['body'], true) ?: [];

        return ! empty($created['id']) ? (string) $created['id'] : null;
    }
}
