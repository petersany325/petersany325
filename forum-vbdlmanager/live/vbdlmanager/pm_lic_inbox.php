<?php
/**
 * SeDiv license inbox: poll maildir/IMAP for returned .src and .txt, post into MC tickets.
 *
 * Cron (required for unattended import):
 *   wget -q -O - "https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key=YOUR_KEY&do=poll"
 *
 * Staff pages also call ?do=poll&auto=1 (throttled) so imports run even without cron.
 *
 * Manual staff upload of .src is also available via pm_lic_email.php?do=return_upload
 */
define('THIS_SCRIPT', 'vbdl_pm_lic_inbox');
define('CSRF_PROTECTION', false);

header('Content-Type: application/json; charset=utf-8');

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
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseRequest.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseInbox.php';

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
$isAdmin = !empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6;
$keyOk = ($expected !== '' && hash_equals($expected, $key));
$isStaff = $isAdmin;
if (!$isStaff && !empty($userinfo['userid']))
{
	$groups = array();
	if (!empty($userinfo['usergroupid']))
	{
		$groups[] = (int)$userinfo['usergroupid'];
	}
	if (!empty($userinfo['membergroupids']))
	{
		foreach (explode(',', (string)$userinfo['membergroupids']) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$groups[] = $g;
			}
		}
	}
	if (in_array(5, $groups, true) || in_array(6, $groups, true))
	{
		$isStaff = true;
	}
	$rawStaff = trim((string)$repo->getSetting('license_email_usergroupids', '6'));
	foreach (explode(',', $rawStaff) as $g)
	{
		$g = (int)trim($g);
		if ($g > 0 && in_array($g, $groups, true))
		{
			$isStaff = true;
			break;
		}
	}
}

if (!$keyOk && !$isStaff)
{
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
	exit;
}

function vbdl_inbox_db()
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

function vbdl_inbox_prefix()
{
	global $table_prefix;
	if (isset($table_prefix) && is_string($table_prefix))
	{
		return $table_prefix;
	}
	return '';
}

$m = vbdl_inbox_db();
if (!$m)
{
	echo json_encode(array('ok' => false, 'error' => 'Database unavailable'));
	exit;
}

$lm = new vbdl_LicenseMail($m, vbdl_inbox_prefix(), $repo, vbdl_Bootstrap::$acl);
$lr = new vbdl_LicenseRequest($m, vbdl_inbox_prefix(), $repo, vbdl_Bootstrap::$acl, $lm);
$inbox = new vbdl_LicenseInbox($m, vbdl_inbox_prefix(), $repo, $lm, $lr);

if ($do === 'purge')
{
	$purged = $lm->purgeExpiredSrcFiles(80);
	echo json_encode(array(
		'ok' => true,
		'purged' => $purged,
		'retention_days' => $lm->srcRetentionDays(),
		'count' => count($purged),
	));
	exit;
}

if ($do !== 'poll')
{
	echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
	exit;
}

$onlyToken = isset($_REQUEST['token']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$_REQUEST['token']) : '';
$result = $inbox->poll(array(
	'auto' => !empty($_REQUEST['auto']),
	'only_token' => $onlyToken,
	'max_files' => isset($_REQUEST['max_files']) ? (int)$_REQUEST['max_files'] : 400,
));
echo json_encode($result);
exit;
