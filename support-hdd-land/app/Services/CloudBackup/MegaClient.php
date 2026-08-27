<?php

namespace App\Services\CloudBackup;

/**
 * Minimal MEGA API client (login + folder ensure + file upload).
 * Adapted for shared-hosting PHP without Composer packages.
 */
class MegaClient
{
    private string $seqno;
    private ?string $sid = null;
    /** @var array<int,string>|null */
    private ?array $masterKey = null;
    private ?string $rootId = null;
    private HttpClient $http;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? new HttpClient;
        $this->seqno = (string) random_int(0, PHP_INT_MAX);
    }

    /** @return array{ok:bool,message:string} */
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $password = (string) $password;
        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'ایمیل و رمز MEGA لازم است.'];
        }

        try {
            $passwordKey = $this->prepareKey($password);
            $uh = $this->stringhash($email, $passwordKey);
            $res = $this->api([['a' => 'us', 'user' => $email, 'uh' => $uh]]);
            if (! is_array($res) || isset($res['e']) || ! isset($res['k'], $res['privk'], $res['csid'])) {
                // Newer accounts may need prelogin (version 2)
                $pre = $this->api([['a' => 'us0', 'user' => $email]]);
                if (is_array($pre) && (int) ($pre['v'] ?? 1) === 2 && ! empty($pre['s'])) {
                    $salt = $this->base64urlDecode((string) $pre['s']);
                    $derived = hash_pbkdf2('sha512', $password, $salt, 100000, 32, true);
                    $passwordKey = $this->bytesToIntArray(substr($derived, 0, 16));
                    $uh = $this->base64urlEncode(substr($derived, 16, 16));
                    $res = $this->api([['a' => 'us', 'user' => $email, 'uh' => $uh]]);
                }
            }
            if (! is_array($res) || ! isset($res['k'], $res['privk'], $res['csid'])) {
                return ['ok' => false, 'message' => 'ورود MEGA ناموفق بود (ایمیل/رمز یا محدودیت API).'];
            }

            $this->masterKey = $this->decryptKey($this->base64urlDecode((string) $res['k']), $passwordKey);
            $privk = $this->decryptKey($this->base64urlDecode((string) $res['privk']), $this->masterKey);
            $rsa = $this->parsePrivateKey($privk);
            $csid = $this->base64urlDecode((string) $res['csid']);
            $sid = $this->rsaDecrypt($csid, $rsa);
            $this->sid = $this->base64urlEncode(substr($sid, 0, 43));

            $files = $this->api([['a' => 'f', 'c' => 1]]);
            if (! is_array($files) || empty($files['f'])) {
                return ['ok' => false, 'message' => 'ورود شد ولی فهرست فایل‌های MEGA خوانده نشد.'];
            }
            foreach ($files['f'] as $n) {
                if ((int) ($n['t'] ?? -1) === 2) {
                    $this->rootId = (string) $n['h'];
                    break;
                }
            }

            return ['ok' => true, 'message' => 'ورود MEGA موفق بود.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'خطای MEGA: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool,message:string} */
    public function upload(string $localPath, string $folderName = 'HDDLAND-Backups'): array
    {
        if (! $this->sid || ! $this->masterKey || ! $this->rootId) {
            return ['ok' => false, 'message' => 'ابتدا وارد MEGA شوید.'];
        }
        if (! is_file($localPath)) {
            return ['ok' => false, 'message' => 'فایل محلی یافت نشد.'];
        }

        try {
            $folderId = $this->ensureFolder($folderName);
            $size = filesize($localPath) ?: 0;
            $req = $this->api([['a' => 'u', 's' => $size]]);
            if (! is_array($req) || empty($req['p'])) {
                return ['ok' => false, 'message' => 'درخواست URL آپلود MEGA ناموفق بود.'];
            }
            $uploadUrl = (string) $req['p'];

            $keyBytes = random_bytes(16);
            $iv = random_bytes(8)."\0\0\0\0\0\0\0\0";
            $metaMac = str_repeat("\0", 16);

            // Encrypt file with AES-CTR (MEGA style) while uploading in one request for typical backup sizes.
            $plain = file_get_contents($localPath);
            if ($plain === false) {
                return ['ok' => false, 'message' => 'خواندن فایل ناموفق بود.'];
            }
            $encrypted = $this->aesCtrCrypt($plain, $keyBytes, $iv);
            $completion = $this->http->request('POST', $uploadUrl.'/0', $encrypted, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen($encrypted),
            ], 600);
            $uploadHandle = trim($completion['body']);
            if (($completion['status'] ?? 0) >= 400 || $uploadHandle === '' || str_starts_with($uploadHandle, '-')) {
                return ['ok' => false, 'message' => 'ارسال باینری به MEGA ناموفق بود.'];
            }

            $attribs = $this->base64urlEncode($this->aesCbcEncrypt(str_pad('MEGA{"n":'.json_encode(basename($localPath)).'}', 16, "\0"), $keyBytes));
            $fileKey = $this->makeFileKey($keyBytes, $iv, $metaMac);
            $encryptedKey = $this->encryptKey($fileKey, $this->masterKey);

            $add = $this->api([[
                'a' => 'p',
                't' => $folderId,
                'n' => [[
                    'h' => $uploadHandle,
                    't' => 0,
                    'a' => $attribs,
                    'k' => $this->base64urlEncode($encryptedKey),
                ]],
            ]]);
            if (! is_array($add) || empty($add['f'][0]['h'])) {
                return ['ok' => false, 'message' => 'ثبت فایل در پوشه MEGA ناموفق بود.'];
            }

            return ['ok' => true, 'message' => 'آپلود به MEGA موفق: '.basename($localPath)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'آپلود MEGA ناموفق: '.$e->getMessage()];
        }
    }

    private function ensureFolder(string $name): string
    {
        $files = $this->api([['a' => 'f', 'c' => 1]]);
        $nodes = is_array($files) ? ($files['f'] ?? []) : [];
        foreach ($nodes as $n) {
            if ((int) ($n['t'] ?? -1) !== 1) {
                continue;
            }
            if ((string) ($n['p'] ?? '') !== (string) $this->rootId) {
                continue;
            }
            $nodeName = $this->decryptNodeName($n);
            if ($nodeName === $name) {
                return (string) $n['h'];
            }
        }

        $folderKey = random_bytes(16);
        $attribs = $this->base64urlEncode($this->aesCbcEncrypt(str_pad('MEGA{"n":'.json_encode($name).'}', 16, "\0"), $folderKey));
        $encKey = $this->encryptKey($this->bytesToIntArray($folderKey), $this->masterKey);
        $created = $this->api([[
            'a' => 'p',
            't' => $this->rootId,
            'n' => [[
                'h' => 'xxxxxxxx',
                't' => 1,
                'a' => $attribs,
                'k' => $this->base64urlEncode($this->intArrayToBytes($encKey)),
            ]],
        ]]);
        if (! empty($created['f'][0]['h'])) {
            return (string) $created['f'][0]['h'];
        }

        return (string) $this->rootId;
    }

    /** @param array<int,mixed> $req */
    private function api(array $req): mixed
    {
        $url = 'https://g.api.mega.co.nz/cs?id='.$this->seqno.($this->sid ? '&sid='.$this->sid : '');
        $this->seqno = (string) ((int) $this->seqno + 1);
        $res = $this->http->request('POST', $url, json_encode($req), [
            'Content-Type' => 'application/json',
        ], 60);
        $json = json_decode($res['body'], true);
        if (is_array($json) && array_is_list($json) && count($json) === 1) {
            return $json[0];
        }

        return $json;
    }

    /** @return array<int,int> */
    private function prepareKey(string $password): array
    {
        $pkey = [0x93C467E3, 0x7DB0C7A4, 0xD1BE3F81, 0x0152CB56];
        $passwordBytes = $password;
        $padding = 4 - (strlen($passwordBytes) % 4);
        if ($padding < 4) {
            $passwordBytes .= str_repeat("\0", $padding);
        }
        $key = [];
        for ($i = 0; $i < strlen($passwordBytes); $i += 4) {
            $key[] = unpack('N', substr($passwordBytes, $i, 4))[1];
        }
        for ($r = 0; $r < 65536; $r++) {
            for ($j = 0; $j < count($key); $j += 4) {
                $block = [
                    $key[$j] ?? 0,
                    $key[$j + 1] ?? 0,
                    $key[$j + 2] ?? 0,
                    $key[$j + 3] ?? 0,
                ];
                $pkey = $this->aesEncryptBlock($block, $pkey);
            }
        }

        return $pkey;
    }

    /** @param array<int,int> $aeskey */
    private function stringhash(string $email, array $aeskey): string
    {
        $h = [0, 0, 0, 0];
        for ($i = 0; $i < strlen($email); $i++) {
            $h[$i % 4] ^= (ord($email[$i]) << (24 - (($i % 4) * 8)));
        }
        for ($i = 0; $i < 16384; $i++) {
            $h = $this->aesEncryptBlock($h, $aeskey);
        }

        return $this->base64urlEncode(pack('N*', $h[0], $h[2]));
    }

    /**
     * @param  array<int,int>  $key
     * @return array<int,int>
     */
    private function aesEncryptBlock(array $block, array $key): array
    {
        $cipher = $this->intArrayToBytes($key);
        $data = $this->intArrayToBytes($block);
        $enc = openssl_encrypt($data, 'AES-128-ECB', $cipher, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return $this->bytesToIntArray($enc ?: str_repeat("\0", 16));
    }

    private function aesCbcEncrypt(string $data, string $key): string
    {
        $pad = 16 - (strlen($data) % 16);
        if ($pad !== 16) {
            $data .= str_repeat("\0", $pad);
        }
        $enc = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, str_repeat("\0", 16));

        return $enc ?: '';
    }

    private function aesCtrCrypt(string $data, string $key, string $iv): string
    {
        // MEGA uses AES-128 CTR with 8-byte nonce + 8-byte counter starting at 0
        $out = '';
        $len = strlen($data);
        $counter = 0;
        $nonce = substr($iv, 0, 8);
        for ($offset = 0; $offset < $len; $offset += 16) {
            $counterBlock = $nonce.pack('J', $counter); // big-endian 64-bit may differ; use N2
            $counterBlock = $nonce.pack('N2', 0, $counter);
            $keystream = openssl_encrypt($counterBlock, 'AES-128-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            $chunk = substr($data, $offset, 16);
            $out .= $chunk ^ substr($keystream ?: str_repeat("\0", 16), 0, strlen($chunk));
            $counter++;
        }

        return $out;
    }

    /**
     * @param  array<int,int>  $key
     * @return array<int,int>
     */
    private function decryptKey(string $data, array $key): array
    {
        $out = '';
        for ($i = 0; $i < strlen($data); $i += 16) {
            $block = $this->bytesToIntArray(substr($data, $i, 16));
            $dec = $this->aesDecryptBlock($block, $key);
            $out .= $this->intArrayToBytes($dec);
        }

        return $this->bytesToIntArray($out);
    }

    /**
     * @param  array<int,int>  $data
     * @param  array<int,int>  $key
     * @return array<int,int>
     */
    private function encryptKey(array $data, array $key): array
    {
        $bytes = $this->intArrayToBytes($data);
        $out = '';
        for ($i = 0; $i < strlen($bytes); $i += 16) {
            $block = $this->bytesToIntArray(substr($bytes, $i, 16));
            $enc = $this->aesEncryptBlock($block, $key);
            $out .= $this->intArrayToBytes($enc);
        }

        return $this->bytesToIntArray($out);
    }

    /**
     * @param  array<int,int>  $block
     * @param  array<int,int>  $key
     * @return array<int,int>
     */
    private function aesDecryptBlock(array $block, array $key): array
    {
        $cipher = $this->intArrayToBytes($key);
        $data = $this->intArrayToBytes($block);
        $dec = openssl_decrypt($data, 'AES-128-ECB', $cipher, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return $this->bytesToIntArray($dec ?: str_repeat("\0", 16));
    }

    /** @param array<int,int> $privk */
    private function parsePrivateKey(array $privk): array
    {
        $raw = $this->intArrayToBytes($privk);
        // MEGA stores RSA private key components as MPI sequence; use openssl if possible via reconstructed PEM is hard.
        // Fallback: extract sid via modular exponent with components.
        $parts = [];
        $offset = 0;
        for ($i = 0; $i < 4; $i++) {
            if ($offset + 2 > strlen($raw)) {
                break;
            }
            $bits = unpack('n', substr($raw, $offset, 2))[1];
            $offset += 2;
            $bytes = (int) ceil($bits / 8);
            $parts[] = substr($raw, $offset, $bytes);
            $offset += $bytes;
        }
        // p,q,d,u
        return [
            'p' => $parts[0] ?? '',
            'q' => $parts[1] ?? '',
            'd' => $parts[2] ?? '',
            'u' => $parts[3] ?? '',
        ];
    }

    /** @param array{p:string,q:string,d:string,u:string} $rsa */
    private function rsaDecrypt(string $cipher, array $rsa): string
    {
        if ($rsa['p'] === '' || $rsa['q'] === '' || $rsa['d'] === '') {
            throw new \RuntimeException('کلید خصوصی MEGA ناقص است.');
        }
        if (! function_exists('gmp_import')) {
            throw new \RuntimeException('افزونه GMP برای ورود MEGA لازم است.');
        }
        $p = gmp_import($rsa['p']);
        $q = gmp_import($rsa['q']);
        $d = gmp_import($rsa['d']);
        $n = gmp_mul($p, $q);
        $c = gmp_import($cipher);
        $m = gmp_powm($c, $d, $n);
        $out = gmp_export($m);

        return is_string($out) ? $out : '';
    }

    /** @param array<string,mixed> $node */
    private function decryptNodeName(array $node): string
    {
        try {
            if (empty($node['k']) || ! $this->masterKey) {
                return '';
            }
            $parts = explode(':', (string) $node['k']);
            $enc = $this->base64urlDecode($parts[1] ?? $parts[0]);
            $key = $this->decryptKey($enc, $this->masterKey);
            $keyBytes = $this->intArrayToBytes(array_slice($key, 0, 4));
            $attr = $this->base64urlDecode((string) ($node['a'] ?? ''));
            $plain = openssl_decrypt($attr, 'AES-128-CBC', $keyBytes, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, str_repeat("\0", 16));
            if (! is_string($plain) || ! str_starts_with($plain, 'MEGA')) {
                return '';
            }
            $json = json_decode(substr($plain, 4), true);

            return (string) ($json['n'] ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  string  $keyBytes
     * @param  string  $iv
     * @param  string  $metaMac
     * @return array<int,int>
     */
    private function makeFileKey(string $keyBytes, string $iv, string $metaMac): array
    {
        $k = $this->bytesToIntArray($keyBytes);
        $n = $this->bytesToIntArray(substr($iv, 0, 8)."\0\0\0\0\0\0\0\0");
        $m = $this->bytesToIntArray($metaMac);

        return [
            $k[0] ^ $n[0],
            $k[1] ^ $n[1],
            $k[2] ^ $m[0],
            $k[3] ^ $m[1],
            $n[0],
            $n[1],
            $m[0],
            $m[1],
        ];
    }

    /** @return array<int,int> */
    private function bytesToIntArray(string $bytes): array
    {
        $bytes = str_pad($bytes, (int) (ceil(strlen($bytes) / 4) * 4), "\0");
        $out = [];
        for ($i = 0; $i < strlen($bytes); $i += 4) {
            $out[] = unpack('N', substr($bytes, $i, 4))[1];
        }

        return $out;
    }

    /** @param array<int,int> $arr */
    private function intArrayToBytes(array $arr): string
    {
        $out = '';
        foreach ($arr as $n) {
            $out .= pack('N', $n & 0xFFFFFFFF);
        }

        return $out;
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        $b64 = strtr($data, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        return (string) base64_decode($b64, true);
    }
}
