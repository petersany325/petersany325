<?php
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['t'] ?? '') !== 'rep1314') { http_response_code(403); exit('Forbidden'); }
$root=dirname(__DIR__);
echo "ticket_cell=". (is_file($root.'/resources/views/partials/ticket-cell.blade.php')?'Y':'N')."\n";
echo "helpers_ticket=". (is_file($root.'/app/helpers_ticket.php')?'Y':'N')."\n";
echo "fn ticket_label=". (function_exists('ticket_label')?'Y':'N')."\n";
$h=$root.'/app/helpers.php';
echo "helpers has ticket_label=". (is_file($h) && str_contains(file_get_contents($h),'function ticket_label')?'Y':'N')."\n";
$r=$root.'/app/Models/Reception.php';
echo "Reception ticketLabel=". (is_file($r) && str_contains(file_get_contents($r),'function ticketLabel')?'Y':'N')."\n";
$p=$root.'/resources/views/reports/payments.blade.php';
echo "payments has شماره قبض=". (is_file($p) && str_contains(file_get_contents($p),'شماره قبض')?'Y':'N')."\n";
$c=$root.'/resources/views/reports/customer-show.blade.php';
echo "customer-show has شماره قبض=". (is_file($c) && str_contains(file_get_contents($c),'شماره قبض')?'Y':'N')."\n";
echo "customer-show has ticket-cell=". (is_file($c) && str_contains(file_get_contents($c),'ticket-cell')?'Y':'N')."\n";
try {
  require $root.'/vendor/autoload.php';
  $app=require $root.'/bootstrap/app.php';
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  echo "boot OK\n";
  echo "fn after boot=". (function_exists('ticket_label')?'Y':'N')."\n";
  echo "Reception method=". (method_exists(App\Models\Reception::class,'ticketLabel')?'Y':'N')."\n";
  // try render partial
  $html=view('partials.ticket-cell',['reception'=>null])->render();
  echo "partial render=[".trim(strip_tags($html))."]\n";
} catch (Throwable $e) {
  echo "ERR ".$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}
@unlink(__FILE__);
echo "DONE\n";
