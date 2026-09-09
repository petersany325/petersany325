<?php

namespace App\Services\CloudBackup;

use App\Support\BackupSettings;

class OneDriveDestination
{
    public function __construct(private HttpClient $http = new HttpClient)
    {
    }

    public function authUrl(string $state): ?string
    {
        $cfg = BackupSettings::cloud('onedrive');
        if ($cfg['client_id'] === '') {
            return null;
        }

        $params = [
            'client_id' => $cfg['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => 'offline_access Files.ReadWrite User.Read',
            'state' => $state,
        ];

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($params);
    }

    public function redirectUri(): string
    {
        return url('/settings/backup/cloud/onedrive/callback');
    }

    /** @return array{ok:bool,message:string} */
    public function handleCallback(string $code): array
    {
        $cfg = BackupSettings::cloud('onedrive');
        if ($cfg['client_id'] === '' || $cfg['client_secret'] === '') {
            return ['ok' => false, 'message' => 'Application (client) ID / Secret مایکروسافت ذخیره نشده.'];
        }

        $res = $this->http->postForm('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'scope' => 'offline_access Files.ReadWrite User.Read',
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (($res['status'] ?? 0) >= 400 || empty($json['access_token'])) {
            return ['ok' => false, 'message' => 'دریافت توکن مایکروسافت ناموفق: '.($json['error_description'] ?? $json['error'] ?? 'unknown')];
        }

        $email = $this->fetchEmail((string) $json['access_token']);
        BackupSettings::saveCloudTokens('onedrive', [
            'access_token' => (string) $json['access_token'],
            'refresh_token' => (string) ($json['refresh_token'] ?? $cfg['refresh_token']),
            'token_expires' => time() + (int) ($json['expires_in'] ?? 3600) - 60,
            'account_email' => $email,
            'connected' => true,
        ]);

        return ['ok' => true, 'message' => 'اتصال OneDrive برقرار شد'.($email ? ' ('.$email.')' : '')];
    }

    /** @return array{ok:bool,message:string} */
    public function test(): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return ['ok' => false, 'message' => 'OneDrive متصل نیست. ابتدا اتصال را برقرار کنید.'];
        }
        $res = $this->http->request('GET', 'https://graph.microsoft.com/v1.0/me', null, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (($res['status'] ?? 0) >= 400) {
            return ['ok' => false, 'message' => 'تست OneDrive ناموفق: '.($json['error']['message'] ?? 'HTTP '.$res['status'])];
        }
        $email = (string) ($json['userPrincipalName'] ?? $json['mail'] ?? '');
        if ($email !== '') {
            BackupSettings::saveCloudTokens('onedrive', ['account_email' => $email, 'connected' => true]);
        }

        return ['ok' => true, 'message' => 'اتصال OneDrive سالم است'.($email ? ' — '.$email : '')];
    }

    /** @return array{ok:bool,message:string} */
    public function upload(string $localPath): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return ['ok' => false, 'message' => 'OneDrive متصل نیست.'];
        }
        if (! is_file($localPath)) {
            return ['ok' => false, 'message' => 'فایل بکاپ برای آپلود به OneDrive یافت نشد.'];
        }

        $cfg = BackupSettings::cloud('onedrive');
        $folder = trim($cfg['folder'] ?: 'HDDLAND-Backups', '/');
        $name = basename($localPath);
        $size = filesize($localPath) ?: 0;

        // Small files: simple upload. Larger: upload session.
        if ($size <= 4 * 1024 * 1024) {
            $url = 'https://graph.microsoft.com/v1.0/me/drive/root:/'.rawurlencode($folder).'/'.rawurlencode($name).':/content';
            $res = $this->http->putFile($url, $localPath, [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/octet-stream',
            ]);
            $json = json_decode($res['body'], true) ?: [];
            if (($res['status'] ?? 0) >= 400 || empty($json['id'])) {
                return ['ok' => false, 'message' => 'آپلود OneDrive ناموفق: '.($json['error']['message'] ?? 'HTTP '.$res['status'])];
            }

            return ['ok' => true, 'message' => 'آپلود به OneDrive موفق: '.$folder.'/'.$name];
        }

        $session = $this->http->postJson(
            'https://graph.microsoft.com/v1.0/me/drive/root:/'.rawurlencode($folder).'/'.rawurlencode($name).':/createUploadSession',
            ['item' => ['@microsoft.graph.conflictBehavior' => 'replace', 'name' => $name]],
            ['Authorization' => 'Bearer '.$token]
        );
        $sessJson = json_decode($session['body'], true) ?: [];
        $uploadUrl = (string) ($sessJson['uploadUrl'] ?? '');
        if ($uploadUrl === '') {
            return ['ok' => false, 'message' => 'ساخت نشست آپلود OneDrive ناموفق بود.'];
        }

        $chunk = 320 * 1024 * 10; // 3.2MB (multiple of 320KiB)
        $fp = fopen($localPath, 'rb');
        if (! $fp) {
            return ['ok' => false, 'message' => 'باز کردن فایل بکاپ ناموفق بود.'];
        }
        $offset = 0;
        $final = null;
        while ($offset < $size) {
            $data = fread($fp, $chunk);
            if ($data === false || $data === '') {
                break;
            }
            $end = $offset + strlen($data) - 1;
            $res = $this->http->request('PUT', $uploadUrl, $data, [
                'Content-Length' => (string) strlen($data),
                'Content-Range' => "bytes {$offset}-{$end}/{$size}",
            ], 600);
            $final = json_decode($res['body'], true) ?: [];
            if (($res['status'] ?? 0) >= 400 && ($res['status'] ?? 0) !== 202) {
                fclose($fp);

                return ['ok' => false, 'message' => 'آپلود تکه‌ای OneDrive ناموفق: HTTP '.$res['status']];
            }
            $offset = $end + 1;
        }
        fclose($fp);

        if (empty($final['id'])) {
            return ['ok' => false, 'message' => 'آپلود OneDrive کامل نشد.'];
        }

        return ['ok' => true, 'message' => 'آپلود به OneDrive موفق: '.$folder.'/'.$name];
    }

    private function accessToken(): ?string
    {
        $cfg = BackupSettings::cloud('onedrive');
        if ($cfg['refresh_token'] === '' && $cfg['access_token'] === '') {
            return null;
        }
        if ($cfg['access_token'] !== '' && (int) $cfg['token_expires'] > time()) {
            return $cfg['access_token'];
        }
        if ($cfg['refresh_token'] === '' || $cfg['client_id'] === '' || $cfg['client_secret'] === '') {
            return $cfg['access_token'] !== '' ? $cfg['access_token'] : null;
        }

        $res = $this->http->postForm('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'refresh_token' => $cfg['refresh_token'],
            'grant_type' => 'refresh_token',
            'scope' => 'offline_access Files.ReadWrite User.Read',
        ]);
        $json = json_decode($res['body'], true) ?: [];
        if (empty($json['access_token'])) {
            BackupSettings::saveCloudTokens('onedrive', ['connected' => false]);

            return null;
        }
        BackupSettings::saveCloudTokens('onedrive', [
            'access_token' => (string) $json['access_token'],
            'refresh_token' => (string) ($json['refresh_token'] ?? $cfg['refresh_token']),
            'token_expires' => time() + (int) ($json['expires_in'] ?? 3600) - 60,
            'connected' => true,
        ]);

        return (string) $json['access_token'];
    }

    private function fetchEmail(string $accessToken): string
    {
        $res = $this->http->request('GET', 'https://graph.microsoft.com/v1.0/me', null, [
            'Authorization' => 'Bearer '.$accessToken,
        ]);
        $json = json_decode($res['body'], true) ?: [];

        return (string) ($json['userPrincipalName'] ?? $json['mail'] ?? '');
    }
}
