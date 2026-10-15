<?php
/**
 * Import pending License Request (.txt) and VIP License (.src) replies from info@ into MC tickets.
 *
 *   /vbdlmanager/_import_req_replies.php                 (staff session or ?key=)
 *   /vbdlmanager/_import_req_replies.php?token=VBDL-REQ-…
 *   /vbdlmanager/_import_req_replies.php?token=VBDL-LIC-…
 *
 * Thin wrapper around vbdl_LicenseInbox (same engine as pm_lic_inbox.php).
 */
define('THIS_SCRIPT', 'vbdl_import_req_replies');
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
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$key = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
$expected = trim((string)$repo->getSetting('license_inbox_key', ''));
$keyOk = ($expected !== '' && hash_equals($expected, $key));
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
$isStaff = in_array(6, $groups, true) || in_array(5, $groups, true);
if (!$isStaff)
{
	$raw = trim((string)$repo->getSetting('license_email_usergroupids', '6'));
	foreach (explode(',', $raw) as $g)
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

$config = array();
$cfg = $forumRoot . '/core/includes/config.php';
if (!is_file($cfg))
{
	$cfg = $forumRoot . '/includes/config.php';
}
include $cfg;
$m = @new mysqli(
	$config['MasterServer']['servername'] ?? 'localhost',
	$config['MasterServer']['username'] ?? '',
	$config['MasterServer']['password'] ?? '',
	$config['Database']['dbname'] ?? '',
	(int)($config['MasterServer']['port'] ?? 3306)
);
if ($m->connect_errno)
{
	echo json_encode(array('ok' => false, 'error' => 'db'));
	exit;
}
$m->set_charset('utf8mb4');
$tp = $config['Database']['tableprefix'] ?? $prefix;

$lm = new vbdl_LicenseMail($m, $tp, $repo, vbdl_Bootstrap::$acl);
$lr = new vbdl_LicenseRequest($m, $tp, $repo, vbdl_Bootstrap::$acl, $lm);
$inbox = new vbdl_LicenseInbox($m, $tp, $repo, $lm, $lr);

$onlyToken = isset($_REQUEST['token']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$_REQUEST['token']) : '';
$result = $inbox->poll(array(
	'auto' => !empty($_REQUEST['auto']),
	'only_token' => $onlyToken,
	'max_files' => 500,
));
echo json_encode($result, JSON_PRETTY_PRINT);
exit;
