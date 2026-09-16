<?php
/**
 * Public download endpoint.
 * Place at forum root: /vbdlmanager/download.php
 */
define('THIS_SCRIPT', 'vbdl_download');
define('CSRF_PROTECTION', false);

$forumRoot = dirname(__FILE__) . '/..';
chdir($forumRoot);

// vBulletin 5/6 bootstrap
if (is_file($forumRoot . '/core/includes/init.php'))
{
	require_once $forumRoot . '/core/includes/init.php';
}
elseif (is_file($forumRoot . '/includes/init.php'))
{
	require_once $forumRoot . '/includes/init.php';
}
else
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'vBulletin bootstrap not found.';
	exit;
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';

global $vbulletin, $db, $table_prefix;

$prefix = isset($table_prefix) ? $table_prefix : (isset($vbulletin->config['Database']['tableprefix']) ? $vbulletin->config['Database']['tableprefix'] : '');
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);

try
{
	vbdl_Bootstrap::init($database, $prefix);
}
catch (Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Download Manager is not available.';
	exit;
}

$fileid = isset($_REQUEST['fileid']) ? (int)$_REQUEST['fileid'] : 0;
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0, 'usergroupid' => 1);
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

$result = vbdl_Bootstrap::$service->handleDownload($fileid, $userinfo, $ip, $ua);
header('HTTP/1.1 403 Forbidden');
header('Content-Type: text/html; charset=utf-8');
$html = !empty($result['html']) ? $result['html'] : ('<p>' . htmlspecialchars(isset($result['message']) ? $result['message'] : 'Download unavailable.', ENT_QUOTES, 'UTF-8') . '</p>');
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" /><title>Download unavailable</title>';
echo '<style>
body{font-family:Segoe UI,Tahoma,sans-serif;margin:0;background:#f4f6f8;color:#1f2933}
.wrap{max-width:640px;margin:40px auto;padding:0 16px}
.card{background:#fff;border:1px solid #d9e2ec;border-radius:8px;padding:20px}
.vbdl-vip-box{margin-top:12px;padding:12px 14px;background:#fff5f5;border:1px solid #ffa8a8;border-radius:8px}
.vbdl-vip-title{display:block;margin-bottom:6px;color:#c92a2a}
.vbdl-vip-btn{display:inline-block;background:#c92a2a;color:#fff!important;text-decoration:none;padding:6px 12px;border-radius:6px}
a{color:#243b53}
</style></head><body><div class="wrap"><div class="card">';
echo '<h1>Download unavailable</h1>';
echo $html;
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
echo '<p style="margin-top:18px">';
if ($userid < 1)
{
	// vB6: no login.php — use forum home header Login dropdown
	echo '<a href="../">Sign in on forum</a> | ';
}
echo '<a href="../">Return to forum</a> | <a href="index.php">Downloads</a></p>';
echo '</div></div></body></html>';
