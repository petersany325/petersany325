<?php
/**
 * License purchase request API (all users).
 *
 * do=config | list | submit | admin_pending | admin_add_vip
 */
define('THIS_SCRIPT', 'vbdl_pm_lic_request');
define('CSRF_PROTECTION', false);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

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

// pm_lic_email.php executes on include — cannot require it. Duplicate minimal helpers below.

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

function vbdl_req_fail($msg, $code = 400)
{
	http_response_code($code);
	echo json_encode(array('ok' => false, 'error' => $msg));
	exit;
}

function vbdl_req_db()
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

function vbdl_req_prefix()
{
	global $table_prefix;
	return (isset($table_prefix) && is_string($table_prefix)) ? $table_prefix : '';
}

function vbdl_req_is_staff($userinfo, $repo)
{
	if (empty($userinfo['userid']))
	{
		return false;
	}
	if (!empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6)
	{
		return true;
	}
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
	$raw = trim((string)$repo->getSetting('license_email_usergroupids', '6'));
	foreach (explode(',', $raw) as $g)
	{
		$g = (int)trim($g);
		if ($g > 0 && in_array($g, $groups, true))
		{
			return true;
		}
	}
	return false;
}

function vbdl_req_send_mail($to, $subject, $bodyText, $filename, $bytes, $fromEmail, $fromName)
{
	$to = trim($to);
	$subject = trim($subject);
	if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL))
	{
		return 'Invalid destination email';
	}
	$boundary = 'vbdl_req_' . md5(uniqid((string)mt_rand(), true));
	$safeName = preg_replace('/[^\w.\-()+@]+/', '_', $filename);
	if ($safeName === '')
	{
		$safeName = 'receipt.bin';
	}
	$headers = array();
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $fromEmail);
	$headers[] = 'Reply-To: ' . $fromEmail;
	$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
	$headers[] = 'X-Mailer: HDD-LAND-VBDL-LicenseRequest';
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
	return $ok ? '' : 'mail() failed — check server mail configuration';
}

if ($userid < 1)
{
	vbdl_req_fail('Please sign in', 401);
}

$m = vbdl_req_db();
if (!$m)
{
	vbdl_req_fail('Database unavailable', 500);
}
$lm = new vbdl_LicenseMail($m, vbdl_req_prefix(), $repo, $acl);
$lr = new vbdl_LicenseRequest($m, vbdl_req_prefix(), $repo, $acl, $lm);
$isStaff = vbdl_req_is_staff($userinfo, $repo);

if ($do === 'config')
{
	echo json_encode(array(
		'ok' => true,
		'logged_in' => 1,
		'show_license_request_menu' => 1,
		'license_request_url' => '/vbdlmanager/sediv_license_request.php',
		'can_staff' => $isStaff ? 1 : 0,
		'request_to' => $lr->requestEmail(),
		'request_subject' => $lr->requestSubject(),
		'target_vip_usergroupid' => $lr->targetVipGroupId(),
		'from_email' => 'info@hdd-land.com',
	));
	exit;
}

if ($do === 'list')
{
	$rows = $isStaff && !empty($_REQUEST['all']) ? $lr->listAll(80) : $lr->listForUser($userid);
	$items = array();
	foreach ($rows as $row)
	{
		$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['message_nodeid'];
		$items[] = array(
			'token' => $row['token'],
			'customer_username' => $row['customer_username'],
			'customer_userid' => (int)$row['customer_userid'],
			'receipt_filename' => $row['receipt_filename'],
			'license_filename' => $row['license_filename'],
			'status' => $row['status'],
			'sent_label' => !empty($row['sent_dateline']) ? date('Y-m-d H:i', (int)$row['sent_dateline']) : '',
			'approved_label' => !empty($row['approved_dateline']) ? date('Y-m-d H:i', (int)$row['approved_dateline']) : '',
			'message_url' => $msgId > 0 ? ('/messagecenter/view/' . $msgId) : '',
		);
	}
	echo json_encode(array('ok' => true, 'items' => $items, 'staff_view' => ($isStaff && !empty($_REQUEST['all'])) ? 1 : 0));
	exit;
}

if ($do === 'admin_pending')
{
	if (!$isStaff)
	{
		vbdl_req_fail('Staff only', 403);
	}
	$rows = $lr->listPendingVip(50);
	$items = array();
	foreach ($rows as $row)
	{
		$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['message_nodeid'];
		$items[] = array(
			'token' => $row['token'],
			'customer_username' => $row['customer_username'],
			'customer_userid' => (int)$row['customer_userid'],
			'customer_email' => $row['customer_email'],
			'license_filename' => $row['license_filename'],
			'receipt_filename' => $row['receipt_filename'],
			'approved_label' => !empty($row['approved_dateline']) ? date('Y-m-d H:i', (int)$row['approved_dateline']) : '',
			'message_url' => $msgId > 0 ? ('/messagecenter/view/' . $msgId) : '',
			'target_vip_usergroupid' => $lr->targetVipGroupId(),
		);
	}
	echo json_encode(array('ok' => true, 'items' => $items, 'target_vip_usergroupid' => $lr->targetVipGroupId()));
	exit;
}

if ($do === 'admin_add_vip')
{
	if (!$isStaff)
	{
		vbdl_req_fail('Staff only', 403);
	}
	$token = isset($_POST['token']) ? (string)$_POST['token'] : '';
	$rec = $lr->findByToken($token);
	if (!$rec)
	{
		vbdl_req_fail('Unknown tracking token');
	}
	$result = $lr->addCustomerToVip($rec, $userid);
	if (!empty($result['error']))
	{
		vbdl_req_fail($result['error'], 500);
	}
	echo json_encode(array_merge(array('ok' => true), $result));
	exit;
}

if ($do === 'submit')
{
	if (empty($_FILES['receipt']) || !is_uploaded_file($_FILES['receipt']['tmp_name']))
	{
		vbdl_req_fail('Upload payment receipt photo');
	}
	$filename = (string)$_FILES['receipt']['name'];
	$bytes = file_get_contents($_FILES['receipt']['tmp_name']);
	$note = isset($_POST['note']) ? trim((string)$_POST['note']) : '';
	$username = !empty($userinfo['username']) ? (string)$userinfo['username'] : ('userid-' . $userid);
	$email = !empty($userinfo['email']) ? (string)$userinfo['email'] : $lm->customerEmail($userid);
	$result = $lr->submitReceipt($userid, $username, $email, $filename, $bytes, $note);
	if (!empty($result['error']))
	{
		vbdl_req_fail($result['error'], 500);
	}
	$err = vbdl_req_send_mail(
		$result['to_email'],
		$result['subject'],
		$result['body'],
		$result['filename'],
		$result['bytes'],
		'info@hdd-land.com',
		'HDD LAND License Desk'
	);
	if ($err !== '')
	{
		vbdl_req_fail($err, 500);
	}
	unset($result['bytes'], $result['body']);
	echo json_encode($result);
	exit;
}

vbdl_req_fail('Unknown action');
