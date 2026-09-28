<?php
/**
 * Diag + heal for License Request VBDL-REQ-4745BB2F98A4
 * (email arrived but Message Center Inbox missing for admin)
 *
 *   /vbdlmanager/_diag_req_4745bb.php
 *   /vbdlmanager/_diag_req_4745bb.php?heal=1
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
$token = 'VBDL-REQ-4745BB2F98A4';
$out = array('ok' => true, 'token' => $token);

$res = $m->query("SELECT * FROM {$tp}vbdl_license_request WHERE token='" . $m->real_escape_string($token) . "' LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
$out['record'] = $row;
$out['table_exists'] = true;
if (!$row)
{
	// Table may be missing on older installs
	$chk = $m->query("SHOW TABLES LIKE '{$tp}vbdl_license_request'");
	$out['table_exists'] = ($chk && $chk->num_rows > 0);
}

$msg = $row ? (int)$row['message_nodeid'] : 0;
$starter = $row ? (int)$row['starter_nodeid'] : 0;
if ($msg < 1 && $starter < 1)
{
	// Fallback: find PM by title token
	$r = $m->query(
		"SELECT nodeid, starter, userid, authorname, title FROM {$tp}node "
		. "WHERE title LIKE '%" . $m->real_escape_string($token) . "%' "
		. "ORDER BY nodeid DESC LIMIT 5"
	);
	$nodes = array();
	if ($r)
	{
		while ($n = $r->fetch_assoc())
		{
			$nodes[] = $n;
		}
	}
	$out['nodes_by_title'] = $nodes;
	if ($nodes)
	{
		$msg = (int)$nodes[0]['nodeid'];
		$starter = !empty($nodes[0]['starter']) ? (int)$nodes[0]['starter'] : $msg;
	}
}

$checkNodes = array_values(array_unique(array_filter(array($msg, $starter))));
$out['check_nodes'] = $checkNodes;
$sentto = array();
foreach ($checkNodes as $nid)
{
	$r = $m->query(
		"SELECT s.userid, s.folderid, s.deleted, u.username, u.usergroupid, u.email "
		. "FROM {$tp}sentto s LEFT JOIN {$tp}user u ON u.userid=s.userid "
		. "WHERE s.nodeid=" . (int)$nid . " ORDER BY s.userid ASC LIMIT 80"
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
$out['message_url'] = $starter > 0
	? ('/messagecenter/view/' . $starter)
	: ($msg > 0 ? ('/messagecenter/view/' . $msg) : '');
$out['license_request_page'] = '/vbdlmanager/sediv_license_request.php';
$out['heal_hint'] = '/vbdlmanager/_diag_req_4745bb.php?heal=1';

$doHeal = !empty($_GET['heal']) || !empty($_REQUEST['heal']);
if ($doHeal)
{
	chdir($base);
	try
	{
		if (is_file($base . '/core/includes/init.php'))
		{
			require_once $base . '/core/includes/init.php';
		}
		elseif (is_file($base . '/includes/init.php'))
		{
			require_once $base . '/includes/init.php';
		}
		require_once $base . '/core/packages/vbdlmanager/library/Bootstrap.php';
		require_once $base . '/core/packages/vbdlmanager/library/LicenseMail.php';
		require_once $base . '/core/packages/vbdlmanager/library/LicenseRequest.php';
		global $db, $table_prefix;
		$prefix = isset($table_prefix) ? $table_prefix : $tp;
		$database = isset($db) ? $db : null;
		vbdl_Bootstrap::init($database, $prefix);
		$lm = new vbdl_LicenseMail($m, $prefix, vbdl_Bootstrap::$repo, vbdl_Bootstrap::$acl);
		$lr = new vbdl_LicenseRequest($m, $prefix, vbdl_Bootstrap::$repo, vbdl_Bootstrap::$acl, $lm);
		if ($row)
		{
			$out['heal'] = $lr->healRequestTicketAccess($row);
		}
		else
		{
			$out['heal'] = $lm->healPmNodesAccess($checkNodes, 0);
		}
	}
	catch (Exception $e)
	{
		$out['heal'] = array('error' => $e->getMessage());
	}

	$sentto2 = array();
	foreach ($checkNodes as $nid)
	{
		$r = $m->query(
			"SELECT s.userid, u.username, u.usergroupid FROM {$tp}sentto s "
			. "LEFT JOIN {$tp}user u ON u.userid=s.userid WHERE s.nodeid=" . (int)$nid
			. " ORDER BY s.userid ASC LIMIT 80"
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

echo json_encode($out, JSON_PRETTY_PRINT);
