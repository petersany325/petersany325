<?php
/**
 * SeDiv VIP — Active License SeDiv desk.
 * VIP: pick license type → upload .lic → opens MC ticket (VIP + support) → emails sedivlic@list.ru
 * Returned .src (or text rejection) is posted into the same ticket. Staff can review every request.
 */
define('THIS_SCRIPT', 'vbdl_sediv_active_license');
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
$isVip = $acl->isVip($userinfo);
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

if (!$isVip && !$canStaff)
{
	header('HTTP/1.1 403 Forbidden');
	header('Content-Type: text/html; charset=utf-8');
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Active License SeDiv</title></head><body style="font-family:Segoe UI,Tahoma,sans-serif;padding:40px">';
	echo '<h1>Active License SeDiv</h1><p>This page is only for SeDiv VIP members.</p>';
	echo '<p><a href="/messagecenter">Back to Message Center</a> · <a href="/vbdlmanager/">VIP DOWNLOAD</a></p>';
	echo '</body></html>';
	exit;
}

$username = !empty($userinfo['username']) ? (string)$userinfo['username'] : '';
$email = !empty($userinfo['email']) ? (string)$userinfo['email'] : '';

// Build LicenseMail for type list (mysqli via Bootstrap repo db if available).
$licenseTypes = array();
try
{
	$m = null;
	if ($database instanceof mysqli)
	{
		$m = $database;
	}
	elseif (is_object($database) && method_exists($database, 'get_connection'))
	{
		$m = $database->get_connection();
	}
	if ($m instanceof mysqli)
	{
		$lm = new vbdl_LicenseMail($m, $prefix, vbdl_Bootstrap::$repo, $acl);
		$licenseTypes = $lm->licenseTypes();
	}
}
catch (Throwable $e)
{
	$licenseTypes = array();
}
if (!$licenseTypes)
{
	$licenseTypes = array(
		array('id' => 'sediv_imager', 'label' => 'All license SeDiv imager', 'subject' => 'Subject license SeDiv imager'),
		array('id' => 'sehitachi_imager', 'label' => 'All license SeHitachi imager', 'subject' => 'Subject license SeHitachi imager'),
		array('id' => 'sedivx_imager', 'label' => 'All license SeDivX imager', 'subject' => 'Subject license SeDivX imager'),
		array('id' => 'sehgst_imager', 'label' => 'All license SeHGST imager', 'subject' => 'Subject license SeHGST imager'),
		array('id' => 'sediv_repairs', 'label' => 'All license SeDiv repairs', 'subject' => 'Subject license SeDiv repairs'),
		array('id' => 'sehitachi_repairs', 'label' => 'All license SeHitachi repairs', 'subject' => 'Subject license SeHitachi repairs'),
	);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Active License SeDiv — HDD LAND</title>
<link rel="stylesheet" href="/vbdlmanager/assets/sediv-active-license.css?v=20260926src1" />
</head>
<body class="vbdl-sediv-page">
<header class="vbdl-sediv-top">
	<a class="vbdl-sediv-brand" href="/">HDD LAND</a>
	<nav>
		<a href="/messagecenter">Message Center</a>
		<a href="/vbdlmanager/">VIP DOWNLOAD</a>
	</nav>
</header>
<main class="vbdl-sediv-main">
	<p class="vbdl-sediv-kicker">SeDiv VIP · Message Center</p>
	<h1>Active License SeDiv</h1>
	<p class="vbdl-sediv-lead">Choose the license product, upload your <code>.lic</code>, and press Send. A Message Center ticket opens for you and support. The file is emailed with the exact subject for that product; when the activated <code>.src</code> (or a text reply) returns, it is posted into the same ticket.</p>

	<section class="vbdl-sediv-card" id="vbdl-sediv-upload-card" <?php echo ($isVip || $canStaff) ? '' : 'hidden'; ?>>
		<h2>Send license<?php echo ($canStaff && !$isVip) ? ' <span class="vbdl-sediv-muted">(admin)</span>' : ''; ?></h2>
		<div class="vbdl-sediv-meta">
			<div><span>Username</span><strong id="vbdl-sediv-user"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>Email</span><strong id="vbdl-sediv-email"><?php echo htmlspecialchars($email !== '' ? $email : '(not set)', ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<div><span>To</span><strong>sedivlic@list.ru</strong></div>
			<div><span>Subject</span><strong id="vbdl-sediv-subject-preview"><?php echo htmlspecialchars($licenseTypes[0]['subject'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
		</div>
		<fieldset class="vbdl-sediv-types">
			<legend>License type</legend>
			<?php foreach ($licenseTypes as $i => $t): ?>
			<label class="vbdl-sediv-type">
				<input type="radio" name="vbdl_sediv_type" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>"
					data-subject="<?php echo htmlspecialchars($t['subject'], ENT_QUOTES, 'UTF-8'); ?>"
					data-label="<?php echo htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8'); ?>"
					<?php echo $i === 0 ? 'checked' : ''; ?> />
				<span><?php echo htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8'); ?></span>
			</label>
			<?php endforeach; ?>
		</fieldset>
		<label class="vbdl-sediv-file">
			<span>License file (.lic)</span>
			<input type="file" id="vbdl-sediv-lic" accept=".lic,application/octet-stream" />
		</label>
		<label class="vbdl-sediv-note">
			<span>Note (optional)</span>
			<textarea id="vbdl-sediv-note" rows="2" placeholder="optional note"></textarea>
		</label>
		<button type="button" class="vbdl-sediv-btn" id="vbdl-sediv-submit">Send license</button>
		<p class="vbdl-sediv-msg" id="vbdl-sediv-msg" role="status"></p>
		<p class="vbdl-sediv-ticketlink" id="vbdl-sediv-ticketlink" hidden></p>
	</section>

	<?php if ($canStaff): ?>
	<section class="vbdl-sediv-card">
		<h2>Support desk</h2>
		<p class="vbdl-sediv-muted">Admins can send a license above (same VIP flow). You also receive VIP tickets as support and can poll inbox / upload returned <code>.src</code> below.</p>
	</section>
	<?php endif; ?>

	<section class="vbdl-sediv-card" id="vbdl-sediv-imap-card" <?php echo $canStaff ? '' : 'hidden'; ?>>
		<h2>Auto-return status</h2>
		<p class="vbdl-sediv-muted" id="vbdl-sediv-imap-msg">Checking…</p>
		<button type="button" class="vbdl-sediv-btn vbdl-sediv-btn-alt" id="vbdl-sediv-poll" hidden>Poll inbox now</button>
		<p class="vbdl-sediv-msg" id="vbdl-sediv-poll-msg" role="status"></p>
	</section>

	<section class="vbdl-sediv-card" id="vbdl-sediv-return-card" <?php echo $canStaff ? '' : 'hidden'; ?>>
		<h2>Manual return (.src)</h2>
		<p class="vbdl-sediv-muted">Staff fallback: paste the tracking token and upload <code>Source.src</code> into the same ticket.</p>
		<label class="vbdl-sediv-note"><span>Tracking token</span><input type="text" id="vbdl-sediv-token" placeholder="VBDL-LIC-..." /></label>
		<label class="vbdl-sediv-file"><span>Activated .src file</span><input type="file" id="vbdl-sediv-src" accept=".src,application/octet-stream" /></label>
		<button type="button" class="vbdl-sediv-btn vbdl-sediv-btn-alt" id="vbdl-sediv-return">Upload .src to ticket</button>
		<p class="vbdl-sediv-msg" id="vbdl-sediv-return-msg" role="status"></p>
	</section>

	<section class="vbdl-sediv-card">
		<div class="vbdl-sediv-row">
			<h2><?php echo $canStaff && !$isVip ? 'All license tickets' : 'Your license tickets'; ?></h2>
			<button type="button" class="vbdl-sediv-linkbtn" id="vbdl-sediv-refresh">Refresh</button>
		</div>
		<div id="vbdl-sediv-list" class="vbdl-sediv-list">Loading…</div>
	</section>
</main>
<script>
window.__VBDL_SEDIV_PAGE__ = {
  isVip: <?php echo $isVip ? 1 : 0; ?>,
  canStaff: <?php echo $canStaff ? 1 : 0; ?>,
  username: <?php echo json_encode($username); ?>,
  email: <?php echo json_encode($email); ?>,
  licenseTypes: <?php echo json_encode($licenseTypes); ?>
};
</script>
<script defer src="/vbdlmanager/assets/sediv-active-license.js?v=20260926src1"></script>
</body>
</html>
