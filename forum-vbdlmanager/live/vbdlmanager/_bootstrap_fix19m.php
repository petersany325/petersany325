<?php
/**
 * One-shot deployer for license inbox fundamental fix (19m).
 * Safe to delete after success. Writes files from GitHub then polls inbox.
 *
 * URL: /vbdlmanager/_bootstrap_fix19m.php?key=BOOTSTRAP_KEY
 */
header('Content-Type: application/json; charset=utf-8');

$base = dirname(__FILE__) . '/..';
$branch = 'cursor/license-inbox-fundamental-fix-376d';
$rawBase = 'https://raw.githubusercontent.com/petersany325/petersany325/' . $branch . '/forum-vbdlmanager/live';

// Simple gate: allow if key matches existing license_inbox_key, or bootstrap secret file, or first-run empty.
$reqKey = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
// One-shot deploy secret (rotate/delete this file after success).
$bootSecret = 'vbdl19m-K7pQ2nR9xW4mH8cL';

$config = array();
$cfgFile = $base . '/core/includes/config.php';
if (!is_file($cfgFile))
{
	$cfgFile = $base . '/includes/config.php';
}
if (is_file($cfgFile))
{
	include $cfgFile;
}

$allowed = false;
if ($reqKey !== '' && hash_equals($bootSecret, $reqKey))
{
	$allowed = true;
}

$mysqli = null;
$tp = '';
$inboxKey = '';
if (!empty($config['MasterServer']['username']))
{
	$mysqli = @new mysqli(
		$config['MasterServer']['servername'] ?? 'localhost',
		$config['MasterServer']['username'] ?? '',
		$config['MasterServer']['password'] ?? '',
		$config['Database']['dbname'] ?? '',
		(int)($config['MasterServer']['port'] ?? 3306)
	);
	if (!$mysqli->connect_errno)
	{
		$mysqli->set_charset('utf8mb4');
		$tp = $config['Database']['tableprefix'] ?? '';
		$r = @$mysqli->query("SELECT value FROM {$tp}vbdl_setting WHERE varname='license_inbox_key' LIMIT 1");
		if ($r && ($row = $r->fetch_assoc()))
		{
			$inboxKey = trim((string)$row['value']);
			if ($reqKey !== '' && $inboxKey !== '' && hash_equals($inboxKey, $reqKey))
			{
				$allowed = true;
			}
		}
		// First deploy assistance: if no inbox key yet, allow bootstrap secret only (already handled).
	}
}

if (!$allowed)
{
	http_response_code(403);
	echo json_encode(array(
		'ok' => false,
		'error' => 'Forbidden',
		'hint' => 'Pass ?key= with license_inbox_key or one-shot bootstrap secret',
	));
	exit;
}

$map = array(
	'/core/packages/vbdlmanager/library/LicenseMail.php',
	'/core/packages/vbdlmanager/library/LicenseRequest.php',
	'/core/packages/vbdlmanager/library/LicenseInbox.php',
	'/core/packages/vbdlmanager/library/Db.php',
	'/core/packages/vbdlportal/hooks.php',
	'/vbdlmanager/pm_lic_inbox.php',
	'/vbdlmanager/_import_req_replies.php',
	'/vbdlmanager/pm_lic_download.php',
	'/vbdlmanager/pm_lic_request.php',
	'/vbdlmanager/pm_lic_email.php',
	'/vbdlmanager/sediv_license_request.php',
	'/vbdlmanager/sediv_active_license.php',
	'/vbdlmanager/assets/sediv-license-request.js',
	'/vbdlmanager/assets/sediv-license-request.css',
	'/vbdlmanager/assets/sediv-active-license.js',
	'/vbdlmanager/assets/sediv-mc-nav.js',
	'/admincp/vbdlmanager/pages/settings.php',
	'/core/admincp/vbdlmanager/pages/settings.php',
);

function vbdl_fetch_url($url)
{
	$bin = false;
	if (function_exists('curl_init'))
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_USERAGENT => 'vbdl-bootstrap-19m',
		));
		$bin = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if (!is_string($bin) || $bin === '' || $code >= 400)
		{
			$bin = false;
		}
	}
	if ($bin === false)
	{
		$ctx = stream_context_create(array(
			'http' => array('timeout' => 90, 'ignore_errors' => true),
			'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
		));
		$bin = @file_get_contents($url, false, $ctx);
	}
	return (is_string($bin) && $bin !== '') ? $bin : false;
}

function vbdl_write_file($path, $bytes)
{
	$dir = dirname($path);
	if (!is_dir($dir))
	{
		@mkdir($dir, 0755, true);
	}
	@chmod($dir, 0755);
	if (is_file($path))
	{
		@chmod($path, 0666);
	}
	$n = @file_put_contents($path, $bytes);
	if ($n === false)
	{
		@chmod($dir, 0777);
		@chmod($path, 0666);
		$n = @file_put_contents($path, $bytes);
	}
	return $n !== false;
}

$out = array(
	'ok' => true,
	'rev' => 'pm-lic-sediv-19m',
	'branch' => $branch,
	'ts' => time(),
	'files' => array(),
);

foreach ($map as $rel)
{
	$url = $rawBase . $rel;
	$bin = vbdl_fetch_url($url);
	if ($bin === false)
	{
		$out['files'][$rel] = 'FAIL fetch';
		continue;
	}
	$dest = $base . $rel;
	$out['files'][$rel] = vbdl_write_file($dest, $bin) ? ('OK ' . strlen($bin)) : 'FAIL write';
}

// Settings + poll
if ($mysqli && !$mysqli->connect_errno)
{
	if ($inboxKey === '')
	{
		$inboxKey = bin2hex(random_bytes(16));
		@$mysqli->query(
			"INSERT INTO {$tp}vbdl_setting (varname,value) VALUES ('license_inbox_key','"
			. $mysqli->real_escape_string($inboxKey)
			. "') ON DUPLICATE KEY UPDATE value=IF(value='' OR value IS NULL, VALUES(value), value)"
		);
	}
	@$mysqli->query(
		"INSERT INTO {$tp}vbdl_setting (varname,value) VALUES ('license_maildir','/home/hddrecov/mail/hdd-land.com/info') "
		. "ON DUPLICATE KEY UPDATE value=IF(value='' OR value IS NULL, VALUES(value), value)"
	);
	$r = @$mysqli->query("SELECT value FROM {$tp}vbdl_setting WHERE varname='vip_usergroupids' LIMIT 1");
	if ($r && ($row = $r->fetch_assoc()) && trim((string)$row['value']) === '')
	{
		@$mysqli->query("UPDATE {$tp}vbdl_setting SET value='14' WHERE varname='vip_usergroupids'");
	}
	@$mysqli->query(
		"UPDATE {$tp}vbdl_setting SET value='sedivlic@list.ru' WHERE varname='license_request_email' "
		. "AND (value='' OR value='info@hdd-land.com')"
	);
}

$pollUrl = 'https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key='
	. rawurlencode($inboxKey !== '' ? $inboxKey : $bootSecret)
	. '&do=poll&max_files=500';
// Prefer real inbox key
if ($inboxKey !== '')
{
	$pollUrl = 'https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key='
		. rawurlencode($inboxKey) . '&do=poll&max_files=500';
}
$pollBody = vbdl_fetch_url($pollUrl);
$out['poll_url_host'] = 'forum.hdd-land.com';
$out['poll_http'] = is_string($pollBody) ? substr($pollBody, 0, 2500) : '';
$out['poll_ok'] = is_string($pollBody) && (strpos($pollBody, '"ok":true') !== false || strpos($pollBody, '"ok": true') !== false);
$out['cron'] = '*/2 * * * * wget -q -O - "https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key='
	. $inboxKey . '&do=poll" >/dev/null 2>&1';
$out['inbox_key_set'] = ($inboxKey !== '') ? 1 : 0;

@file_put_contents($base . '/vbdlmanager/_deploy_pm_lic_email.json', json_encode($out));
echo json_encode($out, JSON_PRETTY_PRINT);
exit;
