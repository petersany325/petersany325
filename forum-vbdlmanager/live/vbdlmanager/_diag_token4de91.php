<?php
/**
 * One-shot diag + optional staff ACL heal for VBDL-LIC-4DE91C02580F.
 * ?heal=1  → add all admins/staff to sentto on related PM nodes
 */
header('Content-Type: application/json; charset=utf-8');
$base = dirname(__FILE__) . '/..';
$config = array();
$cfg = $base . '/core/includes/config.php';
if (!is_file($cfg))
{
	$cfg = $base . '/includes/config.php';
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
$tp = $config['Database']['tableprefix'] ?? '';
$token = 'VBDL-LIC-4DE91C02580F';
$res = $m->query("SELECT * FROM {$tp}vbdl_license_mail WHERE token='" . $m->real_escape_string($token) . "' LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
$out = array(
	'ok' => true,
	'token' => $token,
	'record' => $row,
	'download_url' => '/vbdlmanager/pm_lic_download.php?token=' . rawurlencode($token) . '&kind=src',
	'heal_hint' => '/vbdlmanager/_diag_token4de91.php?heal=1',
);
if ($row)
{
	$fd = (int)$row['return_filedataid'];
	$node = (int)$row['return_nodeid'];
	$msg = (int)$row['message_nodeid'];
	$starter = (int)$row['starter_nodeid'];
	if ($fd > 0)
	{
		$r = $m->query("SELECT filedataid,userid,filesize,extension,LENGTH(filedata) bloblen,refcount FROM {$tp}filedata WHERE filedataid=$fd");
		$out['filedata'] = $r ? $r->fetch_assoc() : null;
	}
	if ($node > 0)
	{
		$r = $m->query("SELECT nodeid,filedataid,filename,counter,visible FROM {$tp}attach WHERE nodeid=$node");
		$out['attach'] = $r ? $r->fetch_assoc() : null;
	}
	$forumRoot = $base;
	$uid = (int)($out['filedata']['userid'] ?? 0);
	$cust = (int)$row['customer_userid'];
	$staff = (int)$row['staff_userid'];
	$paths = array();
	foreach (array_unique(array($uid, $cust, $staff, 1)) as $u)
	{
		if ($u < 1 || $fd < 1)
		{
			continue;
		}
		$seg = implode('/', str_split((string)$u));
		foreach (array($forumRoot . '/core/attachment', $forumRoot . '/attachment') as $root)
		{
			$p = "$root/$seg/$fd.attach";
			$paths[] = array('path' => $p, 'exists' => is_file($p), 'size' => is_file($p) ? filesize($p) : 0);
		}
	}
	$mirror = $forumRoot . '/vbdlmanager/storage/license/' . $token;
	$out['disk_paths'] = $paths;
	$out['mirror_dir'] = $mirror;
	$out['mirror_files'] = is_dir($mirror) ? array_values(array_diff(scandir($mirror), array('.', '..'))) : array();
	$out['download_endpoint_exists'] = is_file($forumRoot . '/vbdlmanager/pm_lic_download.php');
	$out['has_heal_method'] = false;

	// sentto participants on key nodes (admin missing here → Invalid File Specified)
	$checkNodes = array_values(array_unique(array_filter(array($msg, $starter, $node))));
	$sentto = array();
	foreach ($checkNodes as $nid)
	{
		$r = $m->query(
			"SELECT s.userid, s.folderid, s.deleted, u.username, u.usergroupid FROM {$tp}sentto s "
			. "LEFT JOIN {$tp}user u ON u.userid=s.userid WHERE s.nodeid=" . (int)$nid . " ORDER BY s.userid ASC LIMIT 80"
		);
		$list = array();
		if ($r)
		{
			while ($st = $r->fetch_assoc())
			{
				$list[] = $st;
			}
		}
		$sentto[(string)$nid] = $list;
	}
	$out['sentto'] = $sentto;

	$doHeal = !empty($_GET['heal']) || !empty($_REQUEST['heal']);
	if ($doHeal)
	{
		chdir($forumRoot);
		$bootOk = false;
		try
		{
			if (is_file($forumRoot . '/core/includes/init.php'))
			{
				require_once $forumRoot . '/core/includes/init.php';
			}
			elseif (is_file($forumRoot . '/includes/init.php'))
			{
				require_once $forumRoot . '/includes/init.php';
			}
			require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
			require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseMail.php';
			global $db, $table_prefix;
			$prefix = isset($table_prefix) ? $table_prefix : $tp;
			$database = isset($db) ? $db : null;
			vbdl_Bootstrap::init($database, $prefix);
			$lm = new vbdl_LicenseMail($m, $prefix, vbdl_Bootstrap::$repo, vbdl_Bootstrap::$acl);
			$out['has_heal_method'] = method_exists($lm, 'healTicketStaffAccess');
			if ($out['has_heal_method'])
			{
				$out['heal'] = $lm->healTicketStaffAccess($token);
			}
			else
			{
				$out['heal'] = array('error' => 'healTicketStaffAccess missing — redeploy fix19g');
			}
			$bootOk = true;
		}
		catch (Exception $e)
		{
			$out['heal'] = array('error' => 'bootstrap: ' . $e->getMessage());
		}
		$out['heal_boot'] = $bootOk;

		// Re-read sentto after heal
		$sentto2 = array();
		foreach ($checkNodes as $nid)
		{
			$r = $m->query(
				"SELECT s.userid, u.username, u.usergroupid FROM {$tp}sentto s "
				. "LEFT JOIN {$tp}user u ON u.userid=s.userid WHERE s.nodeid=" . (int)$nid . " ORDER BY s.userid ASC LIMIT 80"
			);
			$list = array();
			if ($r)
			{
				while ($st = $r->fetch_assoc())
				{
					$list[] = $st;
				}
			}
			$sentto2[(string)$nid] = $list;
		}
		$out['sentto_after_heal'] = $sentto2;
	}
}
echo json_encode($out, JSON_PRETTY_PRINT);
