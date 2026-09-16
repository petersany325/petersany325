<?php
/**
 * SeDiv license inbox: poll IMAP for returned .src and post into Message Center tickets.
 *
 * Cron example:
 *   wget -q -O - "https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key=YOUR_KEY&do=poll"
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

if (!$keyOk && !$isAdmin)
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

if ($do !== 'poll')
{
	echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
	exit;
}

$host = trim((string)$repo->getSetting('license_imap_host', ''));
$user = trim((string)$repo->getSetting('license_imap_user', ''));
$pass = (string)$repo->getSetting('license_imap_pass', '');
$port = (int)$repo->getSetting('license_imap_port', '993');
$flags = trim((string)$repo->getSetting('license_imap_flags', '/imap/ssl/novalidate-cert'));

// Prefer local Maildir when readable (same host as forum) — no IMAP password needed.
$maildir = trim((string)$repo->getSetting('license_maildir', '/home/hddrecov/mail/hdd-land.com/info'));
if ($maildir !== '' && is_dir($maildir) && @is_readable($maildir))
{
	$processed = array();
	$errors = array();
	$files = array();
	vbdl_inbox_scan_maildir($maildir, $files, 0);
	// newest first
	usort($files, function ($a, $b) {
		return filemtime($b) - filemtime($a);
	});
	$files = array_slice($files, 0, 40);
	foreach ($files as $path)
	{
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '')
		{
			continue;
		}
		$token = $lm->extractTokenFromText($raw);
		if ($token === '')
		{
			continue;
		}
		$rec = $lm->findByToken($token);
		if (!$rec || $rec['status'] === 'returned')
		{
			continue;
		}
		$parsed = vbdl_inbox_extract_src_from_rfc822($raw);
		if (empty($parsed['bytes']))
		{
			$errors[] = array('token' => $token, 'error' => 'No .src attachment found in maildir message');
			continue;
		}
		$result = $lm->returnSrcToTicket($rec, $parsed['filename'], $parsed['bytes'], (int)$rec['staff_userid']);
		if (!empty($result['error']))
		{
			$errors[] = array('token' => $token, 'error' => $result['error']);
			continue;
		}
		$processed[] = array('token' => $token, 'file' => $parsed['filename'], 'node' => $result['attach_nodeid'], 'via' => 'maildir');
	}
	echo json_encode(array(
		'ok' => true,
		'mode' => 'maildir',
		'maildir' => $maildir,
		'processed' => $processed,
		'errors' => $errors,
		'checked' => count($files),
	));
	exit;
}

if ($host === '' || $user === '' || $pass === '')
{
	echo json_encode(array(
		'ok' => true,
		'skipped' => 1,
		'message' => 'IMAP not configured — set license_imap_* in AdminCP. Manual .src upload still works.',
	));
	exit;
}

if (!function_exists('imap_open'))
{
	echo json_encode(array('ok' => false, 'error' => 'PHP IMAP extension not installed'));
	exit;
}

$mailbox = '{' . $host . ':' . ($port > 0 ? $port : 993) . $flags . '}INBOX';
$imap = @imap_open($mailbox, $user, $pass);
if (!$imap)
{
	echo json_encode(array('ok' => false, 'error' => 'IMAP login failed: ' . imap_last_error()));
	exit;
}

$processed = array();
$errors = array();
$ids = imap_search($imap, 'UNSEEN') ?: array();
// Also scan recent mail with .src in case flags are wrong
if (!$ids)
{
	$ids = imap_search($imap, 'ALL') ?: array();
	$ids = array_slice(array_reverse($ids), 0, 30);
}

foreach ($ids as $msgno)
{
	$overview = imap_fetch_overview($imap, (string)$msgno, 0);
	$subject = '';
	if (!empty($overview[0]->subject))
	{
		$subject = imap_utf8($overview[0]->subject);
	}
	$body = imap_body($imap, $msgno);
	$header = imap_fetchheader($imap, $msgno);
	$blob = $subject . "\n" . $header . "\n" . $body;
	$token = $lm->extractTokenFromText($blob);
	if ($token === '')
	{
		continue;
	}
	$rec = $lm->findByToken($token);
	if (!$rec || $rec['status'] === 'returned')
	{
		continue;
	}

	$structure = imap_fetchstructure($imap, $msgno);
	$parts = array();
	vbdl_inbox_flatten_parts($structure, '', $parts);
	$srcBytes = null;
	$srcName = '';
	foreach ($parts as $part)
	{
		$name = isset($part['filename']) ? (string)$part['filename'] : '';
		if ($name === '' || !preg_match('/\.src$/i', $name))
		{
			continue;
		}
		$data = imap_fetchbody($imap, $msgno, $part['section']);
		if ((int)$part['encoding'] === 3)
		{
			$data = base64_decode($data);
		}
		elseif ((int)$part['encoding'] === 4)
		{
			$data = quoted_printable_decode($data);
		}
		if ($data !== false && $data !== '')
		{
			$srcBytes = $data;
			$srcName = $name;
			break;
		}
	}
	if ($srcBytes === null)
	{
		$errors[] = array('token' => $token, 'error' => 'No .src attachment found');
		continue;
	}

	$result = $lm->returnSrcToTicket($rec, $srcName, $srcBytes, (int)$rec['staff_userid']);
	if (!empty($result['error']))
	{
		$errors[] = array('token' => $token, 'error' => $result['error']);
		continue;
	}
	$processed[] = array('token' => $token, 'file' => $srcName, 'node' => $result['attach_nodeid']);
	@imap_setflag_full($imap, (string)$msgno, '\\Seen');
}

imap_close($imap);
echo json_encode(array(
	'ok' => true,
	'processed' => $processed,
	'errors' => $errors,
	'checked' => count($ids),
));
exit;

function vbdl_inbox_flatten_parts($structure, $prefix, array &$out)
{
	if (!isset($structure->parts) || !is_array($structure->parts))
	{
		$filename = vbdl_inbox_part_filename($structure);
		$out[] = array(
			'section' => $prefix === '' ? '1' : $prefix,
			'filename' => $filename,
			'encoding' => isset($structure->encoding) ? (int)$structure->encoding : 0,
		);
		return;
	}
	foreach ($structure->parts as $i => $part)
	{
		$section = ($prefix === '' ? '' : ($prefix . '.')) . (string)($i + 1);
		if (!empty($part->parts))
		{
			vbdl_inbox_flatten_parts($part, $section, $out);
		}
		else
		{
			$out[] = array(
				'section' => $section,
				'filename' => vbdl_inbox_part_filename($part),
				'encoding' => isset($part->encoding) ? (int)$part->encoding : 0,
			);
		}
	}
}

function vbdl_inbox_part_filename($part)
{
	$filename = '';
	if (!empty($part->dparameters) && is_array($part->dparameters))
	{
		foreach ($part->dparameters as $p)
		{
			if (strtolower($p->attribute) === 'filename')
			{
				$filename = $p->value;
			}
		}
	}
	if ($filename === '' && !empty($part->parameters) && is_array($part->parameters))
	{
		foreach ($part->parameters as $p)
		{
			if (strtolower($p->attribute) === 'name')
			{
				$filename = $p->value;
			}
		}
	}
	return (string)$filename;
}

function vbdl_inbox_scan_maildir($dir, array &$files, $depth = 0)
{
	if ($depth > 4 || !is_dir($dir) || !@is_readable($dir))
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
		if ($e === '.' || $e === '..')
		{
			continue;
		}
		// Skip dovecot index files
		if (strpos($e, 'dovecot') === 0 || $e === 'subscriptions' || $e === 'maildirfolder')
		{
			continue;
		}
		$path = $dir . '/' . $e;
		if (is_dir($path))
		{
			// Prefer cur/new; still recurse into Archive lightly
			vbdl_inbox_scan_maildir($path, $files, $depth + 1);
		}
		elseif (is_file($path) && @is_readable($path) && filesize($path) > 200)
		{
			$files[] = $path;
		}
	}
}

function vbdl_inbox_extract_src_from_rfc822($raw)
{
	$filename = 'Source.src';
	if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.src)"?/i', $raw, $m)
		|| preg_match('/name="?([^";\r\n]+\.src)"?/i', $raw, $m))
	{
		$filename = basename(urldecode(trim($m[1], "\"' ")));
	}

	$parts = array();
	if (preg_match('/boundary=("?)([^";\r\n]+)\1/i', $raw, $b))
	{
		$boundary = $b[2];
		$parts = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?\r?\n/', $raw);
	}
	if (!$parts)
	{
		// Generic multipart split
		if (preg_match_all('/(--[^\r\n]+)\r?\n([\s\S]*?)(?=\r?\n--[^\r\n]+|$)/', $raw, $mm, PREG_SET_ORDER))
		{
			foreach ($mm as $row)
			{
				$parts[] = $row[2];
			}
		}
	}

	foreach ($parts as $part)
	{
		if (!is_string($part) || $part === '')
		{
			continue;
		}
		if (!preg_match('/\.src/i', $part) || !preg_match('/filename|name=/i', $part))
		{
			continue;
		}
		if (!preg_match('/Content-Transfer-Encoding:\s*base64/i', $part))
		{
			continue;
		}
		if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
		{
			continue;
		}
		$bodyTxt = $body[1];
		$bodyTxt = preg_replace('/\r?\n--[^\r\n]*$/s', '', $bodyTxt);
		$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt), true);
		if ($bytes === false)
		{
			$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt));
		}
		if (is_string($bytes) && strlen($bytes) > 100)
		{
			return array('filename' => $filename, 'bytes' => $bytes);
		}
	}

	// Fallback: name/filename .src then later base64 body (any header order)
	if (preg_match('/(?:filename|name)="?[^"\n]+\.src"?[\s\S]*?Content-Transfer-Encoding:\s*base64[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m)
		|| preg_match('/Content-Transfer-Encoding:\s*base64[\s\S]*?(?:filename|name)="?[^"\n]+\.src"?[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m))
	{
		$bytes = base64_decode(preg_replace('/\s+/', '', $m[1]));
		if (is_string($bytes) && $bytes !== '')
		{
			return array('filename' => $filename, 'bytes' => $bytes);
		}
	}
	return array('filename' => $filename, 'bytes' => null);
}
