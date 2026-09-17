<?php
/**
 * Ticket auto-reply poller for Message Center → support/admin.
 *
 * Cron:
 *   wget -q -O - "https://forum.hdd-land.com/vbdlmanager/pm_ticket_autoreply.php?key=YOUR_KEY&do=poll"
 *
 * Staff can also trigger from AdminCP or while browsing Message Center.
 */
define('THIS_SCRIPT', 'vbdl_pm_ticket_autoreply');
define('CSRF_PROTECTION', false);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$forumRoot = dirname(__FILE__) . '/..';
chdir($forumRoot);

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
	echo json_encode(array('ok' => false, 'error' => 'Forum bootstrap missing'));
	exit;
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseMail.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/TicketAutoReply.php';

global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : '';
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);

try
{
	vbdl_Bootstrap::init($database, $prefix);
}
catch (Exception $e)
{
	echo json_encode(array('ok' => false, 'error' => 'Download Manager unavailable'));
	exit;
}

$repo = vbdl_Bootstrap::$repo;
$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : 'poll';
$key = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
$expected = trim((string)$repo->getSetting('license_inbox_key', ''));
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
$isAdmin = !empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6;
$keyOk = ($expected !== '' && hash_equals($expected, $key));
// Logged-in members may only call poll_self (own tickets). Full poll needs key or admin.
$isSelfPoll = ($do === 'poll_self' && $userid > 0);

if (!$keyOk && !$isAdmin && !$isSelfPoll)
{
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
	exit;
}

function vbdl_ar_db()
{
	static $mysqli = null;
	if ($mysqli instanceof mysqli)
	{
		return $mysqli;
	}
	$config = array();
	$forumRoot = dirname(__FILE__) . '/..';
	$cfg = $forumRoot . '/core/includes/config.php';
	if (!is_file($cfg))
	{
		$cfg = $forumRoot . '/includes/config.php';
	}
	if (!is_file($cfg))
	{
		return null;
	}
	include $cfg;
	$host = $config['MasterServer']['servername'] ?? 'localhost';
	$port = !empty($config['MasterServer']['port']) ? (int)$config['MasterServer']['port'] : 3306;
	$user = $config['MasterServer']['username'] ?? '';
	$pass = $config['MasterServer']['password'] ?? '';
	$dbn = $config['Database']['dbname'] ?? '';
	$mysqli = @new mysqli($host, $user, $pass, $dbn, $port);
	if ($mysqli->connect_errno)
	{
		return null;
	}
	$mysqli->set_charset('utf8mb4');
	return $mysqli;
}

$m = vbdl_ar_db();
if (!$m)
{
	echo json_encode(array('ok' => false, 'error' => 'Database unavailable'));
	exit;
}

$lm = new vbdl_LicenseMail($m, $prefix, $repo, vbdl_Bootstrap::$acl);
$ar = new vbdl_TicketAutoReply($m, $prefix, $repo, $lm);

if ($do === 'config')
{
	echo json_encode(array(
		'ok' => true,
		'enabled' => $ar->enabled() ? 1 : 0,
		'support_userid' => $ar->supportUserid(),
		'watch_userids' => $ar->watchUserids(),
		'subject' => $ar->replySubject(),
	));
	exit;
}

if ($do === 'process' && !empty($_REQUEST['nodeid']))
{
	if (!$keyOk && !$isAdmin)
	{
		http_response_code(403);
		echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
		exit;
	}
	$result = $ar->processNode((int)$_REQUEST['nodeid']);
	echo json_encode(array_merge(array('ok' => empty($result['error'])), $result));
	exit;
}

if ($do === 'poll_self')
{
	$result = $ar->pollRecent(20, 172800, $userid);
	echo json_encode($result);
	exit;
}

if (!$keyOk && !$isAdmin)
{
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
	exit;
}

$result = $ar->pollRecent(50, 172800);
echo json_encode($result);
exit;
