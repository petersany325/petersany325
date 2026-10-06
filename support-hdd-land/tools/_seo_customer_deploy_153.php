<?php
/**
 * Customer SEO 1.3.53 one-shot deploy.
 * Upload to public/ and open ?t=seo153-cb9c
 */
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(300);
if (PHP_SAPI !== 'cli' && (($_GET['t'] ?? '') !== 'seo153-cb9c') && (($_GET['t'] ?? '') !== 's')) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}
$root = dirname(__DIR__);
if (basename(__DIR__) === 'public' || is_file(__DIR__.'/index.php') && is_dir(dirname(__DIR__).'/app')) {
    $root = dirname(__DIR__);
}
$branch = 'cursor/site-seo-engine-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";
$files = [
  'app/Support/SeoSettings.php',
  'app/Http/Controllers/SeoController.php',
  'app/Http/Controllers/SettingController.php',
  'app/Support/NavMenu.php',
  'config/updates.php',
  'routes/web.php',
  'resources/views/partials/seo-meta.blade.php',
  'resources/views/settings/index.blade.php',
  'resources/views/layouts/app.blade.php',
  'resources/views/layouts/portal.blade.php',
  'resources/views/gate.blade.php',
];
function seo_out($m){ echo $m."\n"; @ob_flush(); @flush(); }
seo_out('ROOT='.$root);
$ok=0;$fail=0;
foreach ($files as $rel) {
  $ch=curl_init($base.$rel);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>120,CURLOPT_USERAGENT=>'SEO-Upd153']);
  $body=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  if ($code>=400 || !is_string($body) || strlen($body)<20) { seo_out("FAIL $rel http=$code"); $fail++; continue; }
  $dest=$root.'/'.$rel; @mkdir(dirname($dest),0755,true);
  file_put_contents($dest,$body); seo_out('OK '.$rel.' '.strlen($body)); $ok++;
}
@mkdir($root.'/storage/app',0755,true);
@file_put_contents($root.'/storage/app/installed_version.json', json_encode([
  'version'=>'1.3.53','channel'=>'stable','updated_at'=>date('c'),'meta'=>['source'=>'seo_upd153']
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
seo_out('installed_version=1.3.53');
try {
  require $root.'/vendor/autoload.php';
  $app=require $root.'/bootstrap/app.php';
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  Illuminate\Support\Facades\Artisan::call('route:clear');
  Illuminate\Support\Facades\Artisan::call('view:clear');
  Illuminate\Support\Facades\Artisan::call('config:clear');
  seo_out('caches cleared');
} catch (Throwable $e) { seo_out('cache warn '.$e->getMessage()); }
seo_out("copied=$ok fail=$fail");
seo_out('DONE');
if (is_file(__FILE__) && str_contains(__FILE__, '/public/')) {
  @unlink(__FILE__);
}
