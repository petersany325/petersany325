<?php
/**
 * One-shot installer for vbdlportal (VIP DOWNLOAD nav + attachment notice setting).
 * Run once via browser with key, then delete.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$forumRoot = dirname(__FILE__);
$keyOk = (PHP_SAPI === 'cli') || (isset($_GET['key']) && $_GET['key'] === 'vbdlportal-install-20260811');
if (!$keyOk)
{
	header('HTTP/1.1 403 Forbidden');
	echo 'Forbidden';
	exit;
}

function vbdlp_db_connect($forumRoot)
{
	$configFile = $forumRoot . '/core/includes/config.php';
	if (!is_file($configFile))
	{
		$configFile = $forumRoot . '/includes/config.php';
	}
	if (!is_file($configFile))
	{
		throw new RuntimeException('config.php not found');
	}
	include $configFile;
	$host = $config['MasterServer']['servername'] ?? 'localhost';
	$port = $config['MasterServer']['port'] ?? 3306;
	$user = $config['MasterServer']['username'] ?? '';
	$pass = $config['MasterServer']['password'] ?? '';
	$dbn = $config['Database']['dbname'] ?? '';
	$prefix = $config['Database']['tableprefix'] ?? '';
	$mysqli = @new mysqli($host, $user, $pass, $dbn, (int)$port);
	if ($mysqli->connect_errno)
	{
		throw new RuntimeException('DB connect failed: ' . $mysqli->connect_error);
	}
	$mysqli->set_charset('utf8mb4');
	return array($mysqli, $prefix);
}

function q($db, $sql)
{
	$res = $db->query($sql);
	if ($res === false)
	{
		throw new RuntimeException($db->error . ' SQL=' . $sql);
	}
	return $res;
}

function esc($db, $v)
{
	return $db->real_escape_string((string)$v);
}

try
{
	list($db, $prefix) = vbdlp_db_connect($forumRoot);
}
catch (Throwable $e)
{
	echo 'ERROR: ' . $e->getMessage() . "\n";
	exit(1);
}

echo "Installing vbdlportal...\n";

$exists = q($db, "SELECT productid FROM {$prefix}product WHERE productid='vbdlportal'");
if ($exists->num_rows)
{
	q($db, "UPDATE {$prefix}product SET title='VIP DOWNLOAD Portal', description='Site-wide VIP DOWNLOAD link and attachment notice routing', version='1.0.0', active=1 WHERE productid='vbdlportal'");
	echo "Updated product row\n";
}
else
{
	q($db, "INSERT INTO {$prefix}product (productid, title, description, version, active, url, versioncheckurl) VALUES ('vbdlportal', 'VIP DOWNLOAD Portal', 'Site-wide VIP DOWNLOAD link and attachment notice routing', '1.0.0', 1, '', '')");
	echo "Inserted product row\n";
}

$sg = q($db, "SELECT grouptitle FROM {$prefix}settinggroup WHERE grouptitle='vbdlportal'");
if (!$sg->num_rows)
{
	$r = q($db, "SELECT MAX(displayorder) AS m FROM {$prefix}settinggroup");
	$row = $r->fetch_assoc();
	$ord = ((int)$row['m']) + 10;
	q($db, "INSERT INTO {$prefix}settinggroup (grouptitle, displayorder, volatile) VALUES ('vbdlportal', {$ord}, 1)");
	echo "Inserted settinggroup\n";
}

$settings = array(
	'vbdlportal_enabled' => array('1', 'boolean'),
	'force_dm_for_attachments' => array('0', 'boolean'),
	'vbdlportal_force_dm_guests_only' => array('0', 'boolean'),
);

$display = 10;
foreach ($settings as $varname => $meta)
{
	list($default, $datatype) = $meta;
	$vn = esc($db, $varname);
	$dv = esc($db, $default);
	$dt = esc($db, $datatype);
	$opt = ($datatype === 'boolean') ? 'yesno' : '';
	$chk = q($db, "SELECT varname FROM {$prefix}setting WHERE varname='{$vn}'");
	if ($chk->num_rows)
	{
		q($db, "UPDATE {$prefix}setting SET grouptitle='vbdlportal', datatype='{$dt}', optioncode='" . esc($db, $opt) . "' WHERE varname='{$vn}'");
	}
	else
	{
		q($db, "INSERT INTO {$prefix}setting (varname, grouptitle, value, defaultvalue, datatype, optioncode, displayorder, volatile) VALUES ('{$vn}', 'vbdlportal', '{$dv}', '{$dv}', '{$dt}', '" . esc($db, $opt) . "', {$display}, 1)");
	}
	$display += 10;
}
echo "Settings ready\n";

// Mirror force_dm into vbdl_setting (plugin settings table)
$tbl = q($db, "SHOW TABLES LIKE '" . esc($db, $prefix . 'vbdl_setting') . "'");
if ($tbl->num_rows)
{
	$chk = q($db, "SELECT varname FROM {$prefix}vbdl_setting WHERE varname='force_dm_for_attachments'");
	if ($chk->num_rows)
	{
		// keep existing value
		echo "vbdl_setting force_dm_for_attachments already present\n";
	}
	else
	{
		q($db, "INSERT INTO {$prefix}vbdl_setting (varname, value) VALUES ('force_dm_for_attachments', '0')");
		echo "Inserted vbdl_setting force_dm_for_attachments=0\n";
	}
}

// datastore products
$ds = q($db, "SELECT data FROM {$prefix}datastore WHERE title='products'");
if ($ds->num_rows)
{
	$row = $ds->fetch_assoc();
	$data = @unserialize($row['data']);
	if (!is_array($data))
	{
		$data = array();
	}
	$data['vbdlportal'] = '1';
	$ser = esc($db, serialize($data));
	q($db, "UPDATE {$prefix}datastore SET data='{$ser}' WHERE title='products'");
	echo "Datastore products updated\n";
}
else
{
	$data = array('vbdlportal' => '1');
	$ser = esc($db, serialize($data));
	q($db, "INSERT INTO {$prefix}datastore (title, data, unserialize) VALUES ('products', '{$ser}', 1)");
	echo "Datastore products inserted\n";
}

// options
$optRow = q($db, "SELECT data FROM {$prefix}datastore WHERE title='options'");
if ($optRow->num_rows)
{
	$row = $optRow->fetch_assoc();
	$options = @unserialize($row['data']);
	if (!is_array($options))
	{
		$options = array();
	}
	foreach ($settings as $varname => $meta)
	{
		$r = q($db, "SELECT value FROM {$prefix}setting WHERE varname='" . esc($db, $varname) . "'");
		if ($r->num_rows)
		{
			$vr = $r->fetch_assoc();
			$options[$varname] = $vr['value'];
		}
		else
		{
			$options[$varname] = $meta[0];
		}
	}
	$ser = esc($db, serialize($options));
	q($db, "UPDATE {$prefix}datastore SET data='{$ser}' WHERE title='options'");
	echo "Datastore options updated\n";
}

echo "DONE vbdlportal active=1\n";
echo "VIP DOWNLOAD link injects via hookFrontendBeforeOutput\n";
echo "force_dm_for_attachments default=0 (enable in Downloads Manager → Settings)\n";
