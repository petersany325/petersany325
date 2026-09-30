<?php
/**
 * Download Manager AdminCP entry point (vBulletin 6).
 * Linked from cpnav: vbdlmanager.php?do=...
 */
define('THIS_SCRIPT', 'vbdlmanager');
chdir(dirname(__FILE__));
require_once './global.php';
require_once './vbdlmanager/_init.php';

vbdl_admin_init();
vbdl_admin_require();

$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : 'dashboard';
$allowed = array_keys(vbdl_admin_menu_items());
if (!in_array($do, $allowed, true))
{
	$do = 'dashboard';
}

$page = dirname(__FILE__) . '/vbdlmanager/pages/' . $do . '.php';
if (!is_file($page))
{
	print_cp_message('Download Manager page missing: ' . vbdl_h($do) . '. Re-upload admincp/vbdlmanager/pages/.');
	exit;
}
require $page;
