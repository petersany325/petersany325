<?php
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['t'] ?? '') !== 'csrf-upd-cb9c') { http_response_code(403); exit; }
$root = dirname(__DIR__);
$branch = 'cursor/jalali-dates-settings-cb9c';
$url = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/bootstrap/app.php";
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'csrf-fix']);
$body = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
if ($code >= 400 || !is_string($body) || strlen($body) < 100) { echo "FAIL fetch $code\n"; exit; }
file_put_contents($root.'/bootstrap/app.php', $body);
echo "OK bootstrap/app.php ".strlen($body)."\n";
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Artisan::call('config:clear');
Illuminate\Support\Facades\Artisan::call('route:clear');
echo "cleared\nDONE\n";
@unlink(__FILE__);
