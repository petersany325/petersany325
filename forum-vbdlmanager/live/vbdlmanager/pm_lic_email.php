<?php
/**
 * Message Center: email a .lic PM attachment to the license activator.
 *
 * GET  ?do=config  → defaults + whether current user may use the feature
 * POST ?do=send    → email To/Subject + filedataid|attachmentid|nodeid
 *
 * Username in the email is ALWAYS resolved server-side from the attachment author
 * (never trusted from the browser).
 */
define('THIS_SCRIPT', 'vbdl_pm_lic_email');
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
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : '';

function vbdl_pmlic_fail($msg, $code = 400)
{
	http_response_code($code);
	echo json_encode(array('ok' => false, 'error' => $msg));
	exit;
}

function vbdl_pmlic_group_ids($userinfo)
{
	$ids = array();
	if (!empty($userinfo['usergroupid']))
	{
		$ids[] = (int)$userinfo['usergroupid'];
	}
	if (!empty($userinfo['membergroupids']))
	{
		foreach (explode(',', (string)$userinfo['membergroupids']) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$ids[] = $g;
			}
		}
	}
	return array_values(array_unique($ids));
}

function vbdl_pmlic_can_send($userinfo, $repo)
{
	if (empty($userinfo['userid']))
	{
		return false;
	}
	// Full administrators always allowed
	if (!empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6)
	{
		return true;
	}
	$raw = trim((string)$repo->getSetting('license_email_usergroupids', '6'));
	if ($raw === '')
	{
		$raw = '6';
	}
	$allowed = array();
	foreach (explode(',', $raw) as $g)
	{
		$g = (int)trim($g);
		if ($g > 0)
		{
			$allowed[] = $g;
		}
	}
	$userGroups = vbdl_pmlic_group_ids($userinfo);
	foreach ($userGroups as $g)
	{
		if (in_array($g, $allowed, true))
		{
			return true;
		}
	}
	return false;
}

function vbdl_pmlic_db()
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

function vbdl_pmlic_prefix()
{
	global $table_prefix;
	if (isset($table_prefix) && is_string($table_prefix))
	{
		return $table_prefix;
	}
	$config = array();
	$forumRoot = dirname(__FILE__) . '/..';
	$cfg = $forumRoot . '/core/includes/config.php';
	if (!is_file($cfg))
	{
		$cfg = $forumRoot . '/includes/config.php';
	}
	if (is_file($cfg))
	{
		include $cfg;
		return isset($config['Database']['tableprefix']) ? $config['Database']['tableprefix'] : '';
	}
	return '';
}

/**
 * Resolve .lic attachment metadata + authoritative customer username from DB.
 * Set $needBytes=false for UI preview (username only).
 */
function vbdl_pmlic_resolve_attachment($filedataid, $attachmentid, $nodeid, $needBytes = true)
{
	$m = vbdl_pmlic_db();
	if (!$m)
	{
		return array('error' => 'Database unavailable');
	}
	$p = vbdl_pmlic_prefix();
	$filedataid = (int)$filedataid;
	$attachmentid = (int)$attachmentid;
	$nodeid = (int)$nodeid;

	$sql = "
		SELECT
			a.filedataid,
			a.nodeid AS attach_nodeid,
			a.filename,
			a.filesize AS attach_filesize,
			fd.filesize AS fd_filesize,
			fd.extension,
			fd.filehash,
			fd.userid AS fd_userid,
			n.userid AS attach_userid,
			n.parentid AS message_nodeid,
			u.username AS attach_username,
			pn.userid AS message_userid,
			pu.username AS message_username
		FROM {$p}attach a
		INNER JOIN {$p}filedata fd ON fd.filedataid = a.filedataid
		INNER JOIN {$p}node n ON n.nodeid = a.nodeid
		INNER JOIN {$p}user u ON u.userid = n.userid
		LEFT JOIN {$p}node pn ON pn.nodeid = n.parentid
		LEFT JOIN {$p}user pu ON pu.userid = pn.userid
		WHERE 1=1
	";
	if ($filedataid > 0)
	{
		$sql .= ' AND a.filedataid = ' . $filedataid;
	}
	elseif ($attachmentid > 0)
	{
		// Some skins use attachmentid == attach.nodeid
		$sql .= ' AND a.nodeid = ' . $attachmentid;
	}
	elseif ($nodeid > 0)
	{
		$sql .= ' AND (a.nodeid = ' . $nodeid . ' OR n.parentid = ' . $nodeid . ')';
	}
	else
	{
		return array('error' => 'Missing attachment id');
	}
	$sql .= ' ORDER BY a.filedataid DESC LIMIT 1';

	$res = $m->query($sql);
	if (!$res || !($row = $res->fetch_assoc()))
	{
		return array('error' => 'Attachment not found');
	}

	$filename = (string)$row['filename'];
	$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	if ($ext === '' && !empty($row['extension']))
	{
		$ext = strtolower((string)$row['extension']);
	}
	if ($ext !== 'lic')
	{
		return array('error' => 'Only .lic license files can be emailed');
	}

	// Prefer the user who uploaded the attach node; fallback to parent message author.
	$customerUser = trim((string)$row['attach_username']);
	$customerId = (int)$row['attach_userid'];
	if ($customerUser === '' && !empty($row['message_username']))
	{
		$customerUser = trim((string)$row['message_username']);
		$customerId = (int)$row['message_userid'];
	}
	if ($customerUser === '')
	{
		return array('error' => 'Could not resolve customer username for this file');
	}

	$out = array(
		'filedataid' => (int)$row['filedataid'],
		'filename' => $filename !== '' ? $filename : ('license-' . $customerUser . '.lic'),
		'customer_username' => $customerUser,
		'customer_userid' => $customerId,
		'message_nodeid' => !empty($row['message_nodeid']) ? (int)$row['message_nodeid'] : (int)$row['attach_nodeid'],
	);

	if (!$needBytes)
	{
		return $out;
	}

	$bytes = vbdl_pmlic_read_file_bytes($m, $p, (int)$row['filedataid'], (string)$row['filehash']);
	if ($bytes === null || $bytes === '')
	{
		return array('error' => 'Could not read license file contents');
	}
	$out['filesize'] = strlen($bytes);
	$out['bytes'] = $bytes;
	return $out;
}

function vbdl_pmlic_read_file_bytes(mysqli $m, $prefix, $filedataid, $filehash)
{
	$filedataid = (int)$filedataid;
	// 1) Blob column (common on smaller forums)
	$res = $m->query('SELECT filedata FROM ' . $prefix . 'filedata WHERE filedataid=' . $filedataid . ' LIMIT 1');
	if ($res && ($row = $res->fetch_assoc()) && isset($row['filedata']) && $row['filedata'] !== '' && $row['filedata'] !== null)
	{
		return $row['filedata'];
	}

	// 2) Filesystem paths used by vBulletin
	$forumRoot = dirname(__FILE__) . '/..';
	$config = array();
	$cfg = $forumRoot . '/core/includes/config.php';
	if (!is_file($cfg))
	{
		$cfg = $forumRoot . '/includes/config.php';
	}
	if (is_file($cfg))
	{
		include $cfg;
	}
	$attachPath = '';
	if (!empty($config['Misc']['attachmentpath']))
	{
		$attachPath = rtrim((string)$config['Misc']['attachmentpath'], '/');
	}
	$candidates = array();
	if ($attachPath !== '')
	{
		$candidates[] = $attachPath . '/' . floor($filedataid / 1000) . '/' . $filedataid . '.attach';
		$candidates[] = $attachPath . '/' . $filedataid . '.attach';
		if ($filehash !== '')
		{
			$candidates[] = $attachPath . '/' . $filehash;
			$candidates[] = $attachPath . '/' . substr($filehash, 0, 2) . '/' . $filehash;
		}
	}
	$candidates[] = $forumRoot . '/core/attachment/' . floor($filedataid / 1000) . '/' . $filedataid . '.attach';
	$candidates[] = $forumRoot . '/attachment/' . floor($filedataid / 1000) . '/' . $filedataid . '.attach';

	foreach ($candidates as $path)
	{
		if (is_file($path) && is_readable($path))
		{
			$data = @file_get_contents($path);
			if ($data !== false && $data !== '')
			{
				return $data;
			}
		}
	}

	// 3) vB library if present
	try
	{
		if (class_exists('vB_Library', false))
		{
			$lib = vB_Library::instance('filedata');
			if ($lib && method_exists($lib, 'fetchImageFile'))
			{
				// not ideal; skip
			}
		}
	}
	catch (Throwable $e)
	{
	}

	return null;
}

function vbdl_pmlic_send_mail($to, $subject, $bodyText, $filename, $bytes, $fromEmail, $fromName)
{
	$to = trim($to);
	$subject = trim($subject);
	if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL))
	{
		return 'Invalid destination email';
	}
	if ($subject === '')
	{
		$subject = 'License file';
	}

	// Prefer vBulletin mailer when available
	try
	{
		if (class_exists('vB_Mail', false) || class_exists('vB_MailQueue', false))
		{
			// Fall through to MIME mail for attachment support; vB queue APIs vary by version.
		}
	}
	catch (Throwable $e)
	{
	}

	$boundary = 'vbdl_' . md5(uniqid((string)mt_rand(), true));
	$safeName = preg_replace('/[^\w.\-()+@]+/', '_', $filename);
	if ($safeName === '' || strtolower(pathinfo($safeName, PATHINFO_EXTENSION)) !== 'lic')
	{
		$safeName = 'license.lic';
	}

	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $fromEmail);
	$headers[] = 'Reply-To: ' . $fromEmail;
	$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
	$headers[] = 'X-Mailer: HDD-LAND-VBDL-License';

	$msg = '';
	$msg .= '--' . $boundary . "\r\n";
	$msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
	$msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
	$msg .= $bodyText . "\r\n\r\n";
	$msg .= '--' . $boundary . "\r\n";
	$msg .= 'Content-Type: application/octet-stream; name="' . $safeName . "\"\r\n";
	$msg .= "Content-Transfer-Encoding: base64\r\n";
	$msg .= 'Content-Disposition: attachment; filename="' . $safeName . "\"\r\n\r\n";
	$msg .= chunk_split(base64_encode($bytes)) . "\r\n";
	$msg .= '--' . $boundary . "--\r\n";

	$ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $msg, implode("\r\n", $headers));
	if (!$ok)
	{
		return 'mail() failed — check server mail configuration';
	}
	return '';
}

if ($userid < 1)
{
	vbdl_pmlic_fail('Please sign in', 401);
}

$can = vbdl_pmlic_can_send($userinfo, $repo);

if ($do === 'config')
{
	$defaultTo = trim((string)$repo->getSetting('license_activation_email', ''));
	if ($defaultTo === '')
	{
		$defaultTo = trim((string)$repo->getSetting('vip_contact_email', 'info@hdd-land.com'));
	}
	echo json_encode(array(
		'ok' => true,
		'can_send' => $can ? 1 : 0,
		'default_to' => $defaultTo,
		'default_subject_prefix' => '[License]',
	));
	exit;
}

if (!$can)
{
	vbdl_pmlic_fail('You are not allowed to email license files', 403);
}

if ($do === 'resolve')
{
	$filedataid = isset($_REQUEST['filedataid']) ? (int)$_REQUEST['filedataid'] : 0;
	$attachmentid = isset($_REQUEST['attachmentid']) ? (int)$_REQUEST['attachmentid'] : 0;
	$nodeid = isset($_REQUEST['nodeid']) ? (int)$_REQUEST['nodeid'] : 0;
	$resolved = vbdl_pmlic_resolve_attachment($filedataid, $attachmentid, $nodeid, false);
	if (!empty($resolved['error']))
	{
		vbdl_pmlic_fail($resolved['error']);
	}
	echo json_encode(array(
		'ok' => true,
		'customer_username' => $resolved['customer_username'],
		'customer_userid' => (int)$resolved['customer_userid'],
		'filename' => $resolved['filename'],
		'filedataid' => (int)$resolved['filedataid'],
		'message_nodeid' => (int)$resolved['message_nodeid'],
	));
	exit;
}

if ($do !== 'send')
{
	vbdl_pmlic_fail('Unknown action');
}

$to = isset($_POST['to']) ? trim((string)$_POST['to']) : '';
$subject = isset($_POST['subject']) ? trim((string)$_POST['subject']) : '';
$note = isset($_POST['note']) ? trim((string)$_POST['note']) : '';
$filedataid = isset($_POST['filedataid']) ? (int)$_POST['filedataid'] : 0;
$attachmentid = isset($_POST['attachmentid']) ? (int)$_POST['attachmentid'] : 0;
$nodeid = isset($_POST['nodeid']) ? (int)$_POST['nodeid'] : 0;

$resolved = vbdl_pmlic_resolve_attachment($filedataid, $attachmentid, $nodeid);
if (!empty($resolved['error']))
{
	vbdl_pmlic_fail($resolved['error']);
}

$customer = $resolved['customer_username'];
$customerId = (int)$resolved['customer_userid'];
$filename = $resolved['filename'];

if ($subject === '')
{
	$subject = '[License] ' . $customer . ' — ' . $filename;
}
// Always ensure username is present in subject (avoid silent omission)
if (stripos($subject, $customer) === false)
{
	$subject = '[License] ' . $customer . ' — ' . $subject;
}

$staffName = !empty($userinfo['username']) ? (string)$userinfo['username'] : ('userid-' . $userid);
$forumUrl = 'https://forum.hdd-land.com/';
$body = "Forum license activation request\n";
$body .= "================================\n\n";
$body .= "*** FOR USERNAME: " . $customer . " ***\n";
$body .= "*** نام کاربری مشتری: " . $customer . " ***\n\n";
$body .= "Customer username: " . $customer . "\n";
$body .= "Customer userid: " . $customerId . "\n";
$body .= "License filename: " . $filename . "\n";
$body .= "Filedata id: " . (int)$resolved['filedataid'] . "\n";
$body .= "Message node id: " . (int)$resolved['message_nodeid'] . "\n";
$body .= "Sent by staff: " . $staffName . " (userid " . $userid . ")\n";
$body .= "Forum: " . $forumUrl . "\n";
if ($note !== '')
{
	$body .= "\nStaff note:\n" . $note . "\n";
}
$body .= "\nPlease activate this .lic file for username \"" . $customer . "\" and send the active license back to staff.\n";

$fromEmail = trim((string)$repo->getSetting('license_mail_from', ''));
if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL))
{
	$fromEmail = trim((string)$repo->getSetting('vip_contact_email', 'info@hdd-land.com'));
}
if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL))
{
	$fromEmail = 'noreply@hdd-land.com';
}

$err = vbdl_pmlic_send_mail(
	$to,
	$subject,
	$body,
	$filename,
	$resolved['bytes'],
	$fromEmail,
	'HDD LAND License Desk'
);
if ($err !== '')
{
	vbdl_pmlic_fail($err, 500);
}

echo json_encode(array(
	'ok' => true,
	'sent_to' => $to,
	'subject' => $subject,
	'customer_username' => $customer,
	'filename' => $filename,
	'message' => 'License file emailed for user ' . $customer,
));
exit;
