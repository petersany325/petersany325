<?php
/**
 * Post editor upload API for Download Manager.
 * URL: /vbdlmanager/upload_api.php
 *
 * Actions:
 *  - GET  ?do=categories  → categories user may upload into
 *  - POST do=upload       → multipart file + categoryid → returns BBCode
 */
define('THIS_SCRIPT', 'vbdl_upload_api');
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
$acl = vbdl_Bootstrap::$acl;
$service = vbdl_Bootstrap::$service;
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : '';

if ((int)$repo->getSetting('post_upload_enabled', '1') !== 1)
{
	echo json_encode(array('ok' => false, 'error' => 'Post upload is disabled'));
	exit;
}

if ($userid < 1)
{
	echo json_encode(array('ok' => false, 'error' => 'Please sign in to upload'));
	exit;
}

if ($do === 'categories')
{
	$cats = $acl->uploadableCategories($userinfo);
	$out = array();
	foreach ($cats as $c)
	{
		$out[] = array(
			'categoryid' => (int)$c['categoryid'],
			'title' => $c['title'],
			'access_mode' => !empty($c['access_mode']) ? $c['access_mode'] : 'free_open',
		);
	}
	echo json_encode(array('ok' => true, 'categories' => $out));
	exit;
}

if ($do === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$categoryid = isset($_POST['categoryid']) ? (int)$_POST['categoryid'] : 0;
	$cat = $categoryid ? $repo->getCategory($categoryid) : null;
	if (!$cat || !$acl->canUploadToCategory($cat, $userinfo))
	{
		echo json_encode(array('ok' => false, 'error' => 'You cannot upload to this category'));
		exit;
	}

	$accessType = (!empty($_POST['access_type']) && $_POST['access_type'] === 'paid') ? 'paid' : 'free';
	// grant_required categories force paid/VIP style lock unless admin grants download
	if (!empty($cat['access_mode']) && $cat['access_mode'] === 'grant_required')
	{
		$accessType = 'paid';
	}

	$title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
	$result = $service->uploadFile($userinfo, array(
		'title' => $title,
		'description' => isset($_POST['description']) ? $_POST['description'] : 'Uploaded from post editor',
		'categoryid' => $categoryid,
		'access_type' => $accessType,
		'require_grant' => (!empty($cat['access_mode']) && $cat['access_mode'] === 'grant_required') ? 1 : 0,
		'active' => 1,
		'inherit_category_perms' => 1,
	), isset($_FILES['upload']) ? $_FILES['upload'] : array(), array());

	if (empty($result['ok']))
	{
		echo json_encode(array('ok' => false, 'error' => isset($result['message']) ? $result['message'] : 'Upload failed'));
		exit;
	}

	$fileid = (int)$result['fileid'];
	$nodeid = isset($_POST['nodeid']) ? (int)$_POST['nodeid'] : 0;
	if ($fileid > 0)
	{
		$repo->linkPostUpload($fileid, $nodeid, $userid);
	}
	$file = $repo->getFile($fileid);
	$label = $file && !empty($file['title']) ? $file['title'] : ('file-' . $fileid);
	$bbcode = '[download=' . $fileid . ']' . $label . '[/download]';
	$url = 'vbdlmanager/download.php?fileid=' . $fileid;

	echo json_encode(array(
		'ok' => true,
		'fileid' => $fileid,
		'title' => $label,
		'bbcode' => $bbcode,
		'url' => $url,
		'message' => 'Uploaded. BBCode inserted for users with download grant/access.',
	));
	exit;
}

echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
