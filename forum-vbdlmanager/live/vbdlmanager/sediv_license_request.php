<?php
/**
 * License Request desk (all signed-in users).
 * Upload payment receipt → MC ticket + email → .txt license return → admin adds VIP SeDiv.
 */
define('THIS_SCRIPT', 'vbdl_sediv_license_request');
define('CSRF_PROTECTION', false);

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
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Forum bootstrap missing';
	exit;
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseMail.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseRequest.php';

global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : '';
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);

try
{
	vbdl_Bootstrap::init($database, $prefix);
}
catch (Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Download Manager unavailable';
	exit;
}

$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
if ($userid < 1)
{
	header('Location: /');
	exit;
}

$acl = vbdl_Bootstrap::$acl;
$canStaff = false;
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
if (in_array(6, $groups, true) || in_array(5, $groups, true))
{
	$canStaff = true;
}
$rawStaff = (string)vbdl_Bootstrap::$repo->getSetting('license_email_usergroupids', '6');
foreach (explode(',', $rawStaff) as $g)
{
	$g = (int)trim($g);
	if ($g > 0 && in_array($g, $groups, true))
	{
		$canStaff = true;
	}
}

$username = !empty($userinfo['username']) ? (string)$userinfo['username'] : '';
$email = !empty($userinfo['email']) ? (string)$userinfo['email'] : '';
$requestTo = 'sedivlic@list.ru';
try
{
	$m = ($database instanceof mysqli) ? $database : null;
	if ($m instanceof mysqli)
	{
		$lm = new vbdl_LicenseMail($m, $prefix, vbdl_Bootstrap::$repo, $acl);
		$lr = new vbdl_LicenseRequest($m, $prefix, vbdl_Bootstrap::$repo, $acl, $lm);
		$requestTo = $lr->requestEmail();
	}
}
catch (Throwable $e)
{
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>License Request — HDD LAND</title>
<link rel="stylesheet" href="/vbdlmanager/assets/sediv-license-request.css?v=20260916c" />
</head>
<body class="vbdl-req-page">
<header class="vbdl-req-top">
	<a class="vbdl-req-brand" href="/">HDD LAND</a>
	<nav>
		<a href="/messagecenter">Message Center</a>
		<a href="/vbdlmanager/sediv_active_license.php">active license sediv</a>
	</nav>
</header>
<main class="vbdl-req-main">
	<p class="vbdl-req-kicker">Message Center · License Request</p>
	<h1>License Request</h1>
	<p class="vbdl-req-lead">Upload your payment receipt. A Message Center ticket opens and the receipt is emailed to the license inbox. When the license comes back as a <code>.txt</code> file, it is posted into the same ticket and marked approved. An admin can then add you to VIP SeDiv so you can use Active License SeDiv.</p>

	<section class="vbdl-req-card" id="vbdl-req-upload">
		<h2>Send payment receipt</h2>
		<div class="vbdl-req-meta">
			<div><span>Username</span><strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>Email</span><strong><?php echo htmlspecialchars($email !== '' ? $email : '(not set)', ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>To</span><strong><?php echo htmlspecialchars($requestTo, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>Subject</span><strong>License Request</strong></div>
		</div>
		<label class="vbdl-req-file">
			<span>Payment receipt (jpg, png, webp, pdf)</span>
			<input type="file" id="vbdl-req-receipt" accept="image/*,.pdf,application/pdf" />
		</label>
		<label class="vbdl-req-note">
			<span>Note (optional)</span>
			<textarea id="vbdl-req-note" rows="2" placeholder="optional note"></textarea>
		</label>
		<button type="button" class="vbdl-req-btn" id="vbdl-req-submit">Send request</button>
		<p class="vbdl-req-msg" id="vbdl-req-msg" role="status"></p>
		<p class="vbdl-req-ticketlink" id="vbdl-req-ticketlink" hidden></p>
	</section>

	<?php if ($canStaff): ?>
	<section class="vbdl-req-card" id="vbdl-req-admin">
		<div class="vbdl-req-row">
			<h2>Admin report — Add to VIP SeDiv</h2>
			<button type="button" class="vbdl-req-linkbtn" id="vbdl-req-admin-refresh">Refresh</button>
		</div>
		<p class="vbdl-req-muted">Approved requests (license <code>.txt</code> received). Add the user to the VIP SeDiv group.</p>
		<div id="vbdl-req-admin-list" class="vbdl-req-list">Loading…</div>
	</section>
	<?php endif; ?>

	<section class="vbdl-req-card">
		<div class="vbdl-req-row">
			<h2><?php echo $canStaff ? 'All requests' : 'Your requests'; ?></h2>
			<button type="button" class="vbdl-req-linkbtn" id="vbdl-req-refresh">Refresh</button>
		</div>
		<div id="vbdl-req-list" class="vbdl-req-list">Loading…</div>
	</section>
</main>
<script>
window.__VBDL_REQ_PAGE__ = {
  canStaff: <?php echo $canStaff ? 1 : 0; ?>,
  username: <?php echo json_encode($username); ?>,
  email: <?php echo json_encode($email); ?>
};
</script>
<script defer src="/vbdlmanager/assets/sediv-license-request.js?v=20260916c"></script>
</body>
</html>
