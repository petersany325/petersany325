<?php
/**
 * Reliable license file download (.src return / .lic send).
 * Bypasses broken Message Center filedata/fetch ("Invalid File Specified").
 *
 *   /vbdlmanager/pm_lic_download.php?token=VBDL-LIC-...&kind=src
 *   /vbdlmanager/pm_lic_download.php?token=VBDL-LIC-...&kind=lic
 */
define('THIS_SCRIPT', 'vbdl_pm_lic_download');
define('CSRF_PROTECTION', false);

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
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Forum bootstrap missing';
	exit;
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseMail.php';

global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : '';
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);

try
{
	vbdl_Bootstrap::init($database, $prefix);
}
catch (Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Download Manager unavailable';
	exit;
}

$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
if ($userid < 1)
{
	header('HTTP/1.1 401 Unauthorized');
	echo 'Please sign in to download.';
	exit;
}

$repo = vbdl_Bootstrap::$repo;
$acl = vbdl_Bootstrap::$acl;
$token = isset($_REQUEST['token']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$_REQUEST['token']) : '';
$kind = isset($_REQUEST['kind']) ? strtolower((string)$_REQUEST['kind']) : 'src';
if ($kind !== 'lic')
{
	$kind = 'src';
}
if ($token === '')
{
	header('HTTP/1.1 400 Bad Request');
	echo 'Missing token';
	exit;
}

function vbdl_licdl_db()
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

function vbdl_licdl_is_staff(array $userinfo, $repo, $acl = null)
{
	$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
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
	// Full administrators / supermods
	if (in_array(6, $groups, true) || in_array(5, $groups, true))
	{
		return true;
	}
	// Download Manager admin_bypass matrix
	if ($acl && method_exists($acl, 'globalPerms'))
	{
		$p = $acl->globalPerms($userinfo);
		if (!empty($p['admin_bypass']))
		{
			return true;
		}
	}
	// Configured license staff groups
	$raw = trim((string)$repo->getSetting('license_email_usergroupids', '6'));
	foreach (explode(',', $raw) as $g)
	{
		$g = (int)trim($g);
		if ($g > 0 && in_array($g, $groups, true))
		{
			return true;
		}
	}
	// Named support account
	$support = (int)$repo->getSetting('license_support_userid', '0');
	if ($support > 0 && $userid === $support)
	{
		return true;
	}
	return false;
}

$m = vbdl_licdl_db();
if (!$m)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Database unavailable';
	exit;
}

$lm = new vbdl_LicenseMail($m, $prefix, $repo, $acl);
$lm->ensureAttachmentTypes();
$rec = $lm->findByToken($token);
if (!$rec)
{
	header('HTTP/1.1 404 Not Found');
	echo 'Unknown tracking token';
	exit;
}

$isStaff = vbdl_licdl_is_staff($userinfo, $repo, $acl);
$isOwner = ((int)$rec['customer_userid'] === $userid);
// If staff opens download, heal PM participants across the whole ticket (fixes MC attach ACL).
if ($isStaff)
{
	$lm->healTicketStaffAccess($rec);
}
if (!$isStaff && !$isOwner)
{
	header('HTTP/1.1 403 Forbidden');
	echo 'Not allowed to download this file';
	exit;
}

$loaded = $lm->loadLicenseBytes($rec, $kind);
if (empty($loaded['ok']))
{
	header('HTTP/1.1 404 Not Found');
	header('Content-Type: text/plain; charset=utf-8');
	echo isset($loaded['error']) ? $loaded['error'] : 'File not available';
	exit;
}

$bytes = $loaded['bytes'];
$filename = !empty($loaded['filename']) ? (string)$loaded['filename'] : ($kind === 'lic' ? 'License.lic' : 'Source.src');
$filename = preg_replace('/[^\w.\-()+@]+/', '_', $filename);
if ($kind === 'src')
{
	$lm->bumpDownloadCount($rec);
}

header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($bytes));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, no-store');
header('X-VBDL-Via: ' . (isset($loaded['via']) ? $loaded['via'] : ''));
echo $bytes;
exit;
