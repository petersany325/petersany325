<?php
/**
 * License purchase request desk (all signed-in users).
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
$requestTo = 'info@hdd-land.com';
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
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>درخواست لایسنس — HDD LAND</title>
<link rel="stylesheet" href="/vbdlmanager/assets/sediv-license-request.css?v=20260916a" />
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
	<p class="vbdl-req-kicker">Message Center · License purchase</p>
	<h1>درخواست لایسنس</h1>
	<p class="vbdl-req-lead">عکس فیش واریز را بفرستید. تیکت Message Center باز می‌شود و فیش به ایمیل اکتیو ارسال می‌گردد. وقتی لایسنس به‌صورت فایل <code>.txt</code> برگردد، داخل همین تیکت قرار می‌گیرد و وضعیت «تأیید شده» می‌شود. سپس ادمین شما را به VIP SeDiv اضافه می‌کند تا بتوانید از active license sediv استفاده کنید.</p>

	<section class="vbdl-req-card" id="vbdl-req-upload">
		<h2>ارسال فیش پرداخت</h2>
		<div class="vbdl-req-meta">
			<div><span>Username</span><strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>Email</span><strong><?php echo htmlspecialchars($email !== '' ? $email : '(not set)', ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>To</span><strong><?php echo htmlspecialchars($requestTo, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>Subject</span><strong>License purchase request</strong></div>
		</div>
		<label class="vbdl-req-file">
			<span>عکس فیش / رسید (jpg, png, webp, pdf)</span>
			<input type="file" id="vbdl-req-receipt" accept="image/*,.pdf,application/pdf" />
		</label>
		<label class="vbdl-req-note">
			<span>توضیح (اختیاری)</span>
			<textarea id="vbdl-req-note" rows="2" placeholder="مثلاً مبلغ و تاریخ واریز"></textarea>
		</label>
		<button type="button" class="vbdl-req-btn" id="vbdl-req-submit">ارسال درخواست</button>
		<p class="vbdl-req-msg" id="vbdl-req-msg" role="status"></p>
		<p class="vbdl-req-ticketlink" id="vbdl-req-ticketlink" hidden></p>
	</section>

	<?php if ($canStaff): ?>
	<section class="vbdl-req-card" id="vbdl-req-admin">
		<div class="vbdl-req-row">
			<h2>گزارش ادمین — افزودن به VIP SeDiv</h2>
			<button type="button" class="vbdl-req-linkbtn" id="vbdl-req-admin-refresh">Refresh</button>
		</div>
		<p class="vbdl-req-muted">درخواست‌های تأییدشده (لایسنس `.txt` دریافت شده). کاربر را به گروه VIP SeDiv اضافه کنید.</p>
		<div id="vbdl-req-admin-list" class="vbdl-req-list">Loading…</div>
	</section>
	<?php endif; ?>

	<section class="vbdl-req-card">
		<div class="vbdl-req-row">
			<h2><?php echo $canStaff ? 'همه درخواست‌ها' : 'درخواست‌های شما'; ?></h2>
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
<script defer src="/vbdlmanager/assets/sediv-license-request.js?v=20260916a"></script>
</body>
</html>
