<?php
define('THIS_SCRIPT', 'vbdl_quickupload');
define('CSRF_PROTECTION', false);
$forumRoot = dirname(__FILE__) . '/..';
chdir($forumRoot);
if (is_file($forumRoot . '/core/includes/init.php')) { require_once $forumRoot . '/core/includes/init.php'; }
elseif (is_file($forumRoot . '/includes/init.php')) { require_once $forumRoot . '/includes/init.php'; }
else { header('HTTP/1.1 500 Internal Server Error'); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('ok'=>false,'message'=>'vBulletin bootstrap not found.')); exit; }
require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : '';
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);
header('Content-Type: application/json; charset=utf-8');
function vbdl_qu_fail($message, $code = 400) { http_response_code($code); echo json_encode(array('ok'=>false,'message'=>$message,'error'=>$message)); exit; }
try { vbdl_Bootstrap::init($database, $prefix); } catch (Exception $e) { vbdl_qu_fail('Download Manager is not available.', 500); }
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid'=>0,'usergroupid'=>1);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
if ($userid < 1) { vbdl_qu_fail('You must be signed in to upload files.', 403); }
$acl = vbdl_Bootstrap::$acl; $service = vbdl_Bootstrap::$service; $repo = vbdl_Bootstrap::$repo;
function vbdl_qu_allowed_categories($acl, $repo, array $userinfo) {
  $out = array();
  if (is_object($acl) && method_exists($acl, 'uploadableCategories')) {
    foreach ($acl->uploadableCategories($userinfo) as $c) {
      $out[] = array('categoryid'=>(int)$c['categoryid'],'title'=>$c['title'],'access_mode'=>!empty($c['access_mode'])?$c['access_mode']:'free_open');
    }
    if ($out) { return $out; }
  }
  $global = method_exists($acl,'globalPerms') ? $acl->globalPerms($userinfo) : array();
  $admin = !empty($global['admin_bypass']);
  $canUpload = !empty($global['can_upload']) || $admin;
  if (!$canUpload) { return $out; }
  foreach ($repo->listCategories(true) as $c) {
    $cid = (int)$c['categoryid'];
    if ($admin || (method_exists($acl,'canUploadToCategory') && $acl->canUploadToCategory($c, $userinfo))) {
      $out[] = array('categoryid'=>$cid,'title'=>$c['title'],'access_mode'=>!empty($c['access_mode'])?$c['access_mode']:'free_open');
    }
  }
  return $out;
}
if (isset($_GET['action']) && $_GET['action'] === 'categories') {
  echo json_encode(array('ok'=>true,'categories'=>vbdl_qu_allowed_categories($acl,$repo,$userinfo))); exit;
}
if (empty($_FILES['file']) || empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
  vbdl_qu_fail('No file was received.');
}
$allowed = vbdl_qu_allowed_categories($acl,$repo,$userinfo);
if (!$allowed) { vbdl_qu_fail('You do not have permission to upload to any category.', 403); }
$categoryid = isset($_POST['categoryid']) ? (int)$_POST['categoryid'] : 0;
$map = array(); foreach ($allowed as $c) { $map[(int)$c['categoryid']] = $c; }
if ($categoryid < 1 || !isset($map[$categoryid])) { $categoryid = (int)$allowed[0]['categoryid']; }
$cat = isset($map[$categoryid]) ? $map[$categoryid] : $repo->getCategory($categoryid);
$title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
if ($title === '') { $title = $_FILES['file']['name']; }
$title = substr($title, 0, 250);
$accessType = (!empty($cat['access_mode']) && $cat['access_mode'] === 'grant_required') ? 'paid' : 'free';
$result = $service->uploadFile($userinfo, array(
  'title'=>$title,
  'description'=>'Uploaded from post editor by ' . (!empty($userinfo['username']) ? $userinfo['username'] : ('user #'.$userid)),
  'categoryid'=>$categoryid,
  'active'=>1,
  'inherit_category_perms'=>1,
  'access_type'=>$accessType,
  'require_grant'=>(!empty($cat['access_mode']) && $cat['access_mode'] === 'grant_required') ? 1 : 0,
), $_FILES['file']);
if (empty($result['ok'])) { vbdl_qu_fail(!empty($result['message']) ? $result['message'] : 'Upload failed.'); }
$fileid = (int)$result['fileid'];
$bbcode = '[download=' . $fileid . ']' . $title . '[/download]';
echo json_encode(array('ok'=>true,'fileid'=>$fileid,'title'=>$title,'bbcode'=>$bbcode,'categoryid'=>$categoryid));
exit;
