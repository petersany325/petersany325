<?php
/**
 * Import pending License Request email replies from info@ maildir into MC tickets.
 *
 *   /vbdlmanager/_import_req_replies.php                 (staff session or ?key=)
 *   /vbdlmanager/_import_req_replies.php?token=VBDL-REQ-…
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

$onlyToken = isset($_REQUEST['token']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$_REQUEST['token']) : '';
$maildir = trim((string)$repo->getSetting('license_maildir', '/home/hddrecov/mail/hdd-land.com/info'));
$out = array(
	'ok' => true,
	'maildir' => $maildir,
	'maildir_ok' => is_dir($maildir) && @is_readable($maildir),
	'processed' => array(),
	'errors' => array(),
	'checked' => 0,
);

function vbdl_imp_scan($dir, array &$files, $depth = 0)
{
	if ($depth > 5 || !is_dir($dir) || !@is_readable($dir))
	{
		return;
	}
	$ents = @scandir($dir);
	if (!$ents)
	{
		return;
	}
	foreach ($ents as $e)
	{
		if ($e === '.' || $e === '..' || strpos($e, 'dovecot') === 0)
		{
			continue;
		}
		$path = $dir . '/' . $e;
		if (is_dir($path))
		{
			vbdl_imp_scan($path, $files, $depth + 1);
		}
		elseif (is_file($path) && @is_readable($path) && filesize($path) > 100)
		{
			$files[] = $path;
		}
	}
}

function vbdl_imp_extract_txt($raw)
{
	$filename = 'license.txt';
	if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.txt)"?/i', $raw, $m)
		|| preg_match('/name="?([^";\r\n]+\.txt)"?/i', $raw, $m))
	{
		$filename = basename(urldecode(trim($m[1], "\"' ")));
	}
	$parts = array();
	if (preg_match('/boundary=("?)([^";\r\n]+)\1/i', $raw, $b))
	{
		$parts = preg_split('/--' . preg_quote($b[2], '/') . '(?:--)?\r?\n/', $raw);
	}
	foreach ($parts as $part)
	{
		if (!is_string($part) || $part === '')
		{
			continue;
		}
		if (!preg_match('/\.txt/i', $part) || !preg_match('/filename|name=/i', $part))
		{
			continue;
		}
		if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
		{
			continue;
		}
		$bodyTxt = preg_replace('/\r?\n--[^\r\n]*$/s', '', $body[1]);
		if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $part))
		{
			$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt));
		}
		elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $part))
		{
			$bytes = quoted_printable_decode($bodyTxt);
		}
		else
		{
			$bytes = $bodyTxt;
		}
		if (is_string($bytes) && strlen(trim($bytes)) > 3)
		{
			return array('filename' => $filename, 'bytes' => $bytes);
		}
	}
	return array('filename' => $filename, 'bytes' => null);
}

function vbdl_imp_extract_text($raw)
{
	$raw = (string)$raw;
	if (preg_match_all('/Content-Type:\s*text\/plain[^\r\n]*\r?\n(?:[^\r\n]+\r?\n)*\r?\n([\s\S]*?)(?=\r?\n--|\z)/i', $raw, $mm, PREG_SET_ORDER))
	{
		foreach ($mm as $row)
		{
			$chunk = $row[1];
			if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $row[0]))
			{
				$d = base64_decode(preg_replace('/\s+/', '', $chunk));
				if (is_string($d) && $d !== '')
				{
					$chunk = $d;
				}
			}
			elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $row[0]))
			{
				$chunk = quoted_printable_decode($chunk);
			}
			$t = trim(strip_tags($chunk));
			if (strlen($t) >= 12)
			{
				return $t;
			}
		}
	}
	if (preg_match('/\r?\n\r?\n([\s\S]+)$/', $raw, $m))
	{
		$t = trim(strip_tags($m[1]));
		if (strlen($t) >= 12)
		{
			return $t;
		}
	}
	return '';
}

if (!$out['maildir_ok'])
{
	$out['ok'] = false;
	$out['error'] = 'Maildir not readable: ' . $maildir;
	echo json_encode($out, JSON_PRETTY_PRINT);
	exit;
}

$files = array();
vbdl_imp_scan($maildir, $files, 0);
usort($files, function ($a, $b) {
	return filemtime($b) - filemtime($a);
});
$files = array_slice($files, 0, 80);
$out['checked'] = count($files);

foreach ($files as $path)
{
	$raw = @file_get_contents($path);
	if ($raw === false || $raw === '')
	{
		continue;
	}
	if (!preg_match('/\b(VBDL-REQ-[A-Z0-9\-]+)\b/i', $raw, $tm))
	{
		continue;
	}
	$rt = strtoupper($tm[1]);
	if ($onlyToken !== '' && strcasecmp($onlyToken, $rt) !== 0)
	{
		continue;
	}
	$rec = $lr->findByToken($rt);
	if (!$rec)
	{
		$out['errors'][] = array('token' => $rt, 'error' => 'token not in vbdl_license_request', 'file' => basename($path));
		continue;
	}
	if ($rec['status'] !== 'sent')
	{
		continue;
	}
	$parsed = vbdl_imp_extract_txt($raw);
	$bytes = !empty($parsed['bytes']) ? $parsed['bytes'] : null;
	$filename = !empty($parsed['filename']) ? $parsed['filename'] : 'license.txt';
	$textBody = vbdl_imp_extract_text($raw);
	if ($bytes === null || trim((string)$bytes) === '')
	{
		if (strlen($textBody) >= 12)
		{
			$bytes = $textBody . "\n";
			$filename = 'license.txt';
			$textBody = '';
		}
	}
	if ($bytes === null || trim((string)$bytes) === '')
	{
		$out['errors'][] = array('token' => $rt, 'error' => 'no .txt or usable body', 'file' => basename($path));
		continue;
	}
	$result = $lr->approveWithLicense($rec, $filename, $bytes, $textBody, (int)$rec['staff_userid']);
	if (!empty($result['error']))
	{
		$out['errors'][] = array('token' => $rt, 'error' => $result['error'], 'file' => basename($path));
		continue;
	}
	$out['processed'][] = array(
		'token' => $rt,
		'file' => isset($result['filename']) ? $result['filename'] : $filename,
		'status' => 'approved',
		'message_url' => isset($result['message_url']) ? $result['message_url'] : '',
		'mail_file' => basename($path),
		'text_nodeid' => isset($result['text_nodeid']) ? $result['text_nodeid'] : 0,
	);
}

echo json_encode($out, JSON_PRETTY_PRINT);
exit;
