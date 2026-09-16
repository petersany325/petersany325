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
$acl = vbdl_Bootstrap::$acl;
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : '';

function vbdl_pmlic_license_mail()
{
	static $lm = null;
	if ($lm instanceof vbdl_LicenseMail)
	{
		return $lm;
	}
	$m = vbdl_pmlic_db();
	if (!$m)
	{
		return null;
	}
	$lm = new vbdl_LicenseMail($m, vbdl_pmlic_prefix(), vbdl_Bootstrap::$repo, vbdl_Bootstrap::$acl);
	return $lm;
}

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

	// Collect candidate ids — vB filedata/fetch?id= may be filedataid OR attach nodeid.
	$ids = array();
	foreach (array($filedataid, $attachmentid, $nodeid) as $id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			$ids[$id] = $id;
		}
	}
	if (!$ids)
	{
		return array('error' => 'Missing attachment id');
	}
	$idList = implode(',', array_map('intval', array_values($ids)));

	$sql = "
		SELECT
			a.filedataid,
			a.nodeid AS attach_nodeid,
			a.filename,
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
		WHERE (
			a.filedataid IN ($idList)
			OR a.nodeid IN ($idList)
			OR n.parentid IN ($idList)
			OR n.nodeid IN ($idList)
		)
		ORDER BY
			CASE
				WHEN LOWER(a.filename) LIKE '%.lic' THEN 0
				WHEN LOWER(IFNULL(fd.extension,'')) = 'lic' THEN 0
				ELSE 1
			END,
			a.filedataid DESC
		LIMIT 1
	";

	$res = $m->query($sql);
	if (!$res)
	{
		return array('error' => 'Attachment query failed: ' . $m->error);
	}
	if (!($row = $res->fetch_assoc()))
	{
		// Fallback: walk PM starter → children for any .lic attach
		$sql2 = "
			SELECT
				a.filedataid,
				a.nodeid AS attach_nodeid,
				a.filename,
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
			WHERE (
				n.parentid IN ($idList)
				OR n.starter IN ($idList)
				OR pn.starter IN ($idList)
				OR pn.parentid IN ($idList)
			)
			AND (
				LOWER(a.filename) LIKE '%.lic'
				OR LOWER(IFNULL(fd.extension,'')) = 'lic'
			)
			ORDER BY a.filedataid DESC
			LIMIT 1
		";
		$res2 = $m->query($sql2);
		if ($res2)
		{
			$row = $res2->fetch_assoc();
		}
		if (empty($row))
		{
			return array('error' => 'Attachment not found');
		}
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

	$fdUserid = !empty($row['fd_userid']) ? (int)$row['fd_userid'] : 0;
	if ($fdUserid < 1 && !empty($row['attach_userid']))
	{
		$fdUserid = (int)$row['attach_userid'];
	}
	$bytes = vbdl_pmlic_read_file_bytes(
		$m,
		$p,
		(int)$row['filedataid'],
		(string)$row['filehash'],
		$fdUserid
	);
	if ($bytes === null || $bytes === '')
	{
		return array('error' => 'Could not read license file contents');
	}
	$out['filesize'] = strlen($bytes);
	$out['bytes'] = $bytes;
	return $out;
}

/**
 * Resolve attachment storage roots used by this vB install.
 * @return string[]
 */
function vbdl_pmlic_attach_roots()
{
	global $vbulletin;
	$roots = array();
	$forumRoot = realpath(dirname(__FILE__) . '/..');
	if ($forumRoot === false)
	{
		$forumRoot = dirname(__FILE__) . '/..';
	}

	$add = function ($path) use (&$roots, $forumRoot)
	{
		$path = trim((string)$path);
		if ($path === '')
		{
			return;
		}
		$path = rtrim(str_replace('\\', '/', $path), '/');
		// Relative paths are against forum root (chdir already set there).
		if ($path[0] !== '/')
		{
			$path = rtrim(str_replace('\\', '/', $forumRoot), '/') . '/' . ltrim($path, './');
		}
		$roots[$path] = $path;
	};

	if (!empty($vbulletin->config['Misc']['attachmentpath']))
	{
		$add($vbulletin->config['Misc']['attachmentpath']);
	}
	if (!empty($vbulletin->options['attachpath']))
	{
		$add($vbulletin->options['attachpath']);
	}
	if (class_exists('vB', false))
	{
		try
		{
			$cfg = vB::getConfig();
			if (!empty($cfg['Misc']['attachmentpath']))
			{
				$add($cfg['Misc']['attachmentpath']);
			}
		}
		catch (Throwable $e)
		{
		}
		try
		{
			if (method_exists('vB', 'getDatastore'))
			{
				$opt = vB::getDatastore()->getOption('attachpath');
				if (!empty($opt))
				{
					$add($opt);
				}
			}
		}
		catch (Throwable $e)
		{
		}
	}

	// Local config include (may no-op if already loaded; still safe).
	$config = array();
	$cfgFile = $forumRoot . '/core/includes/config.php';
	if (!is_file($cfgFile))
	{
		$cfgFile = $forumRoot . '/includes/config.php';
	}
	if (is_file($cfgFile))
	{
		include $cfgFile;
	}
	if (!empty($config['Misc']['attachmentpath']))
	{
		$add($config['Misc']['attachmentpath']);
	}

	$add($forumRoot . '/core/attachment');
	$add($forumRoot . '/attachment');
	$add($forumRoot . '/core/internal_data/attachments');
	$add($forumRoot . '/internal_data/attachments');

	return array_values($roots);
}

function vbdl_pmlic_read_file_bytes(mysqli $m, $prefix, $filedataid, $filehash, $userid = 0)
{
	$filedataid = (int)$filedataid;
	$userid = (int)$userid;
	$filehash = (string)$filehash;

	// 1) Blob column (DB storage / small forums)
	$res = $m->query(
		'SELECT filedata, userid, filehash, filesize FROM ' . $prefix . 'filedata WHERE filedataid=' . $filedataid . ' LIMIT 1'
	);
	$row = ($res) ? $res->fetch_assoc() : null;
	if ($row)
	{
		if ($userid < 1 && !empty($row['userid']))
		{
			$userid = (int)$row['userid'];
		}
		if ($filehash === '' && !empty($row['filehash']))
		{
			$filehash = (string)$row['filehash'];
		}
		if (isset($row['filedata']) && $row['filedata'] !== '' && $row['filedata'] !== null)
		{
			return $row['filedata'];
		}
	}

	// 2) Filesystem — vB5/6 uses {attachpath}/{u/s/e/r/i/d}/{filedataid}.attach
	$candidates = array();
	$userSeg = ($userid > 0) ? implode('/', str_split((string)$userid)) : '';
	foreach (vbdl_pmlic_attach_roots() as $root)
	{
		if ($userSeg !== '')
		{
			$candidates[] = $root . '/' . $userSeg . '/' . $filedataid . '.attach';
		}
		$candidates[] = $root . '/' . floor($filedataid / 1000) . '/' . $filedataid . '.attach';
		$candidates[] = $root . '/' . $filedataid . '.attach';
		if ($filehash !== '')
		{
			$candidates[] = $root . '/' . $filehash;
			$candidates[] = $root . '/' . substr($filehash, 0, 2) . '/' . $filehash;
			if ($userSeg !== '')
			{
				$candidates[] = $root . '/' . $userSeg . '/' . $filehash;
			}
		}
	}

	foreach (array_unique($candidates) as $path)
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

	// 3) vB API / library (handles storage type, permissions, local FS)
	try
	{
		if (class_exists('vB_Api', false))
		{
			$api = vB_Api::instanceInternal('filedata');
			if ($api && method_exists($api, 'fetchImageByFiledataid'))
			{
				$size = 'full';
				if (class_exists('vB_Api_Filedata', false))
				{
					try
					{
						$ref = new ReflectionClass('vB_Api_Filedata');
						if ($ref->hasConstant('SIZE_FULL'))
						{
							$size = $ref->getConstant('SIZE_FULL');
						}
					}
					catch (Throwable $e)
					{
					}
				}
				$img = $api->fetchImageByFiledataid($filedataid, $size, true, 0);
				if (is_array($img) && !empty($img['filedata']))
				{
					return $img['filedata'];
				}
			}
		}
	}
	catch (Throwable $e)
	{
	}

	try
	{
		if (class_exists('vB_Library', false))
		{
			$lib = vB_Library::instance('filedata');
			if ($lib)
			{
				if (method_exists($lib, 'fetchImageByFiledataid'))
				{
					$img = $lib->fetchImageByFiledataid($filedataid, true);
					if (is_array($img) && !empty($img['filedata']))
					{
						return $img['filedata'];
					}
				}
				if (method_exists($lib, 'getFileData'))
				{
					$data = $lib->getFileData($filedataid);
					if (is_string($data) && $data !== '')
					{
						return $data;
					}
					if (is_array($data) && !empty($data['filedata']))
					{
						return $data['filedata'];
					}
				}
			}
		}
	}
	catch (Throwable $e)
	{
	}

	return null;
}

function vbdl_pmlic_send_mail($to, $subject, $bodyText, $filename, $bytes, $fromEmail, $fromName, $replyTo = '')
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
	$replyTo = trim($replyTo);
	if ($replyTo === '' || !filter_var($replyTo, FILTER_VALIDATE_EMAIL))
	{
		$replyTo = $fromEmail;
	}

	$boundary = 'vbdl_' . md5(uniqid((string)mt_rand(), true));
	$safeName = preg_replace('/[^\w.\-()+@]+/', '_', $filename);
	$ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
	if ($safeName === '' || ($ext !== 'lic' && $ext !== 'src'))
	{
		$safeName = 'license.lic';
	}

	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $fromEmail);
	$headers[] = 'Reply-To: ' . $replyTo;
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
$lm = vbdl_pmlic_license_mail();

if ($do === 'config')
{
	$sedivTo = $lm ? $lm->sedivEmail() : 'sedivlic@list.ru';
	$sedivSubject = $lm ? $lm->sedivSubject() : 'Active SeDiv 2026';
	$vipOnly = $lm ? ($lm->vipOnly() ? 1 : 0) : 1;
	echo json_encode(array(
		'ok' => true,
		'can_send' => $can ? 1 : 0,
		'vip_only' => $vipOnly,
		'locked_to' => $sedivTo,
		'locked_subject' => $sedivSubject,
		'default_to' => $sedivTo,
		'default_subject_prefix' => $sedivSubject,
		'return_ext' => 'src',
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
	$custId = (int)$resolved['customer_userid'];
	$isVip = $lm ? $lm->isCustomerVip($custId) : false;
	$custEmail = $lm ? $lm->customerEmail($custId) : '';
	$vipOnly = $lm ? $lm->vipOnly() : true;
	echo json_encode(array(
		'ok' => true,
		'customer_username' => $resolved['customer_username'],
		'customer_userid' => $custId,
		'customer_email' => $custEmail,
		'is_vip' => $isVip ? 1 : 0,
		'vip_only' => $vipOnly ? 1 : 0,
		'can_use_sediv_flow' => (!$vipOnly || $isVip) ? 1 : 0,
		'locked_to' => $lm ? $lm->sedivEmail() : 'sedivlic@list.ru',
		'locked_subject' => $lm ? $lm->sedivSubject() : 'Active SeDiv 2026',
		'filename' => $resolved['filename'],
		'filedataid' => (int)$resolved['filedataid'],
		'message_nodeid' => (int)$resolved['message_nodeid'],
	));
	exit;
}

if ($do === 'return_upload')
{
	// Staff can manually drop returned .src into the ticket (also used while IMAP is configured).
	$token = isset($_POST['token']) ? (string)$_POST['token'] : '';
	if (!$lm)
	{
		vbdl_pmlic_fail('License mail unavailable', 500);
	}
	$rec = $lm->findByToken($token);
	if (!$rec)
	{
		vbdl_pmlic_fail('Unknown tracking token');
	}
	if (empty($_FILES['srcfile']) || !is_uploaded_file($_FILES['srcfile']['tmp_name']))
	{
		vbdl_pmlic_fail('Upload a .src file');
	}
	$name = (string)$_FILES['srcfile']['name'];
	if (!preg_match('/\.src$/i', $name))
	{
		vbdl_pmlic_fail('Only .src return files are accepted');
	}
	$bytes = file_get_contents($_FILES['srcfile']['tmp_name']);
	$result = $lm->returnSrcToTicket($rec, $name, $bytes, $userid);
	if (!empty($result['error']))
	{
		vbdl_pmlic_fail($result['error'], 500);
	}
	echo json_encode(array_merge(array('ok' => true), $result));
	exit;
}

if ($do !== 'send')
{
	vbdl_pmlic_fail('Unknown action');
}

if (!$lm)
{
	vbdl_pmlic_fail('License mail unavailable', 500);
}

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
$customerEmail = $lm->customerEmail($customerId);
$isVip = $lm->isCustomerVip($customerId);

if ($lm->vipOnly() && !$isVip)
{
	vbdl_pmlic_fail('SeDiv license email is only for VIP SeDiv customers');
}

// Locked destination + subject for VIP SeDiv flow
$to = $lm->sedivEmail();
$subjectBase = $lm->sedivSubject();
$token = $lm->makeToken();
$subject = $subjectBase . ' [' . $token . ']';

$messageNode = (int)$resolved['message_nodeid'];
$starterNode = $messageNode;
$m = vbdl_pmlic_db();
if ($m && $messageNode > 0)
{
	$res = $m->query('SELECT starter, parentid FROM ' . vbdl_pmlic_prefix() . 'node WHERE nodeid=' . $messageNode . ' LIMIT 1');
	if ($res && ($nrow = $res->fetch_assoc()))
	{
		if (!empty($nrow['starter']))
		{
			$starterNode = (int)$nrow['starter'];
		}
	}
}

$rec = $lm->createSentRecord(array(
	'token' => $token,
	'message_nodeid' => $messageNode,
	'starter_nodeid' => $starterNode,
	'customer_userid' => $customerId,
	'customer_username' => $customer,
	'customer_email' => $customerEmail,
	'staff_userid' => $userid,
	'filedataid' => (int)$resolved['filedataid'],
	'lic_filename' => $filename,
	'to_email' => $to,
	'subject' => $subject,
));
if (!empty($rec['error']))
{
	vbdl_pmlic_fail($rec['error'], 500);
}

$staffName = !empty($userinfo['username']) ? (string)$userinfo['username'] : ('userid-' . $userid);
$forumUrl = 'https://forum.hdd-land.com/';
$body = "Active SeDiv 2026 — license activation request\n";
$body .= "==============================================\n\n";
$body .= "Tracking token: " . $token . "\n";
$body .= "(Please keep this token in your reply subject or body)\n\n";
$body .= "Customer username: " . $customer . "\n";
$body .= "Customer email: " . ($customerEmail !== '' ? $customerEmail : '(not set)') . "\n";
$body .= "Customer userid: " . $customerId . "\n";
$body .= "License filename: " . $filename . "\n";
$body .= "Message node id: " . $messageNode . "\n";
$body .= "Sent by staff: " . $staffName . "\n";
$body .= "Forum: " . $forumUrl . "\n";
if ($note !== '')
{
	$body .= "\nStaff note:\n" . $note . "\n";
}
$body .= "\nPlease activate this .lic and reply with the activated .src file.\n";
$body .= "The .src will be posted automatically back into the same Message Center ticket.\n";

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
	'HDD LAND License Desk',
	$fromEmail
);
if ($err !== '')
{
	vbdl_pmlic_fail($err, 500);
}

echo json_encode(array(
	'ok' => true,
	'sent_to' => $to,
	'subject' => $subject,
	'token' => $token,
	'customer_username' => $customer,
	'customer_email' => $customerEmail,
	'filename' => $filename,
	'is_vip' => 1,
	'message' => 'License emailed to ' . $to . ' for VIP user ' . $customer,
));
exit;
