<?php
/**
 * License Request desk (all signed-in users).
 * Upload payment receipt → MC ticket + email → .txt license return → auto VIP SeDiv.
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
$isVip = false;
try
{
	$m = ($database instanceof mysqli) ? $database : null;
	if ($m instanceof mysqli)
	{
		$lm = new vbdl_LicenseMail($m, $prefix, vbdl_Bootstrap::$repo, $acl);
		$lr = new vbdl_LicenseRequest($m, $prefix, vbdl_Bootstrap::$repo, $acl, $lm);
		$requestTo = $lr->requestEmail();
	}
	$isVip = $acl->isVip($userinfo);
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
<link rel="stylesheet" href="/vbdlmanager/assets/sediv-license-request.css?v=20260930vip1" />
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
	<p class="vbdl-req-kicker">Message Center · License Request · VIP SeDiv</p>
	<h1>License Request</h1>

	<div class="vbdl-req-banner" role="note">
		<strong>VIP SeDiv access required</strong>
		<p>New members and users who are not VIP SeDiv must send a License Request with their payment receipt photo. When the license email returns the <code>.txt</code> license file, VIP SeDiv is activated automatically and you can use Active License SeDiv and all VIP SeDiv areas.</p>
	</div>

	<?php if ($isVip && !$canStaff): ?>
	<p class="vbdl-req-lead">Your account already has VIP SeDiv. You can still send a new License Request below if you purchased another license. Use <a href="/vbdlmanager/sediv_active_license.php">active license sediv</a> to activate <code>.lic</code> files.</p>
	<?php else: ?>
	<p class="vbdl-req-lead">Upload your payment receipt. A Message Center ticket opens and the receipt is emailed to the license inbox. When the <code>.txt</code> license comes back, it is posted into the same ticket and your VIP SeDiv access is turned on automatically.</p>
	<?php endif; ?>

	<section class="vbdl-req-card" id="vbdl-req-upload">
		<h2>Send payment receipt (VIP request)</h2>
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
		<button type="button" class="vbdl-req-btn" id="vbdl-req-submit">Send VIP / license request</button>
		<p class="vbdl-req-msg" id="vbdl-req-msg" role="status"></p>
		<p class="vbdl-req-ticketlink" id="vbdl-req-ticketlink" hidden></p>
	</section>

	<?php if ($canStaff): ?>
	<section class="vbdl-req-card" id="vbdl-req-return">
		<div class="vbdl-req-row">
			<h2>Staff — post license reply into ticket</h2>
			<button type="button" class="vbdl-req-linkbtn" id="vbdl-req-poll">Poll inbox now</button>
		</div>
		<p class="vbdl-req-muted">Posting a <code>.txt</code> license (or polling the mailbox) also activates VIP SeDiv automatically for that customer. Use this if email auto-import did not run.</p>
		<label class="vbdl-req-note">
			<span>Tracking token</span>
			<input type="text" id="vbdl-req-return-token" placeholder="VBDL-REQ-…" value="" />
		</label>
		<label class="vbdl-req-file">
			<span>License .txt file</span>
			<input type="file" id="vbdl-req-return-file" accept=".txt,text/plain" />
		</label>
		<label class="vbdl-req-note">
			<span>Or paste license text</span>
			<textarea id="vbdl-req-return-text" rows="4" placeholder="paste license reply here if no .txt file"></textarea>
		</label>
		<button type="button" class="vbdl-req-btn" id="vbdl-req-return-btn">Post license + activate VIP</button>
		<p class="vbdl-req-msg" id="vbdl-req-return-msg" role="status"></p>
	</section>
	<section class="vbdl-req-card" id="vbdl-req-admin">
		<div class="vbdl-req-row">
			<h2>Admin fallback — VIP not auto-added</h2>
			<button type="button" class="vbdl-req-linkbtn" id="vbdl-req-admin-refresh">Refresh</button>
		</div>
		<p class="vbdl-req-muted">Normally empty. Shows requests where the license <code>.txt</code> was received but VIP SeDiv was not granted yet. Use <strong>Add to VIP SeDiv</strong> only as a fallback.</p>
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
  isVip: <?php echo $isVip ? 1 : 0; ?>,
  username: <?php echo json_encode($username); ?>,
  email: <?php echo json_encode($email); ?>
};
</script>
<script defer src="/vbdlmanager/assets/sediv-license-request.js?v=20260930vip1"></script>
</body>
</html>
