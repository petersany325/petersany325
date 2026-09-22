<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['t'] ?? '') !== 'acc1312') { http_response_code(403); echo "Forbidden\n"; exit; }
$root = dirname(__DIR__);
$web = $root.'/routes/web.php';
$ctrl = $root.'/app/Http/Controllers/AccountingController.php';
$view = $root.'/resources/views/accounting/manual.blade.php';
echo "ROOT=$root\n";
echo "web exists=". (is_file($web)?'Y':'N') ." size=".(is_file($web)?filesize($web):0)."\n";
echo "web has manual/tickets=". (is_file($web) && str_contains(file_get_contents($web),'manual/tickets')?'Y':'N') ."\n";
echo "ctrl has searchDebtTickets=". (is_file($ctrl) && str_contains(file_get_contents($ctrl),'searchDebtTickets')?'Y':'N') ."\n";
echo "view has acc-customer-q=". (is_file($view) && str_contains(file_get_contents($view),'acc-customer-q')?'Y':'N') ."\n";
echo "writable web=". (is_writable($web)?'Y':'N') ."\n";
echo "writable routes dir=". (is_writable(dirname($web))?'Y':'N') ."\n";
@unlink(__FILE__);
echo "DONE\n";
