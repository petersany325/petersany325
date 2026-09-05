<?php
/**
 * Frontend Downloads — Windows-style application UI.
 * English Free / Paid (VIP) downloads for HDD LAND.
 */
define('THIS_SCRIPT', 'vbdl_index');
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
	die('vBulletin bootstrap not found.');
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';

global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : (isset($vbulletin->config['Database']['tableprefix']) ? $vbulletin->config['Database']['tableprefix'] : '');
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);
vbdl_Bootstrap::init($database, $prefix);

$repo = vbdl_Bootstrap::$repo;
$acl = vbdl_Bootstrap::$acl;
$service = vbdl_Bootstrap::$service;
$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0, 'usergroupid' => 1, 'username' => 'Guest');

header('Content-Type: text/html; charset=utf-8');

function vbdl_fe_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function vbdl_register_url()
{
	$path = '/vbdlmanager/';
	return '/register?urlpath=' . rawurlencode(base64_encode($path));
}

/**
 * Windows-style Sign in panel for guests (AJAX POST /auth/ajax-login).
 */
function vbdl_signin_panel_html()
{
	$html = '<div class="panel signin-panel" id="vbdl-signin-panel">';
	$html .= '<h1>Forum sign in</h1>';
	$html .= '<p class="sub">Downloads uses your vBulletin forum account automatically. Sign in once with your forum username and password — no separate Downloads registration.</p>';
	$html .= '<div class="signin-error" id="vbdl-signin-error" hidden></div>';
	$html .= '<form id="vbdl-signin-form" class="signin-form" method="post" action="/auth/ajax-login" autocomplete="on">';
	$html .= '<input type="hidden" name="logintype" value="" />';
	$html .= '<input type="hidden" name="securitytoken" value="guest" />';
	$html .= '<div class="signin-row"><label for="vbdl-username">User Name</label>';
	$html .= '<input id="vbdl-username" class="signin-input" type="text" name="username" required autocomplete="username" placeholder="User Name" /></div>';
	$html .= '<div class="signin-row"><label for="vbdl-password">Password</label>';
	$html .= '<input id="vbdl-password" class="signin-input" type="password" name="password" required autocomplete="current-password" placeholder="Password" /></div>';
	$html .= '<div class="signin-row signin-row-inline"><label><input type="checkbox" name="rememberme" value="1" /> Remember me</label></div>';
	$html .= '<div class="signin-actions">';
	$html .= '<button class="btn" type="submit" id="vbdl-signin-btn">Sign in with forum account</button>';
	$html .= '</div>';
	$html .= '<p class="meta" style="margin-top:10px">Need an account? Register on the <a href="../register">forum</a>. Access levels are controlled by the administrator.</p>';
	$html .= '</form></div>';
	$html .= vbdl_signin_script();
	return $html;
}

function vbdl_signin_script()
{
	return <<<'JS'
<script>
(function () {
  var form = document.getElementById('vbdl-signin-form');
  if (!form) return;
  var err = document.getElementById('vbdl-signin-error');
  var btn = document.getElementById('vbdl-signin-btn');
  function showError(msg) {
    if (!err) return;
    err.hidden = !msg;
    err.textContent = msg || '';
  }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    showError('');
    if (btn) { btn.disabled = true; btn.textContent = 'Signing in…'; }
    var body = new URLSearchParams(new FormData(form));
    fetch('/auth/ajax-login', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    }).then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (data) {
        if (data && data.userid && parseInt(data.userid, 10) > 0 && (!data.errors || !data.errors.length)) {
          location.href = '/vbdlmanager/';
          return;
        }
        var msg = 'Login failed. Check username/password.';
        if (data && data.errors && data.errors[0]) {
          var e0 = data.errors[0];
          if (Object.prototype.toString.call(e0) === '[object Array]') {
            msg = (e0[0] === 'badlogin_strikes_logintypeusername' || String(e0[0]).indexOf('badlogin') === 0)
              ? 'Incorrect username or password.'
              : String(e0[0]);
          } else {
            msg = String(e0);
          }
        }
        showError(msg);
        var pw = document.getElementById('vbdl-password');
        if (pw) pw.value = '';
        if (btn) { btn.disabled = false; btn.textContent = 'Sign in'; }
      })
      .catch(function () {
        showError('Network error. Please try again.');
        if (btn) { btn.disabled = false; btn.textContent = 'Sign in'; }
      });
  });
})();
</script>
JS;
}

$fileid = isset($_GET['fileid']) ? (int)$_GET['fileid'] : 0;
$categoryid = isset($_GET['categoryid']) ? (int)$_GET['categoryid'] : 0;
$view = isset($_GET['view']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['view'])) : 'list';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = max(1, (int)$repo->getSetting('downloads_per_page', '20'));
$vipBadge = $repo->getSetting('vip_badge_label', 'VIP');
$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
$username = !empty($userinfo['username']) ? $userinfo['username'] : 'Guest';
$isVip = $acl->isVip($userinfo);
$canView = $acl->canViewSection($userinfo) || !empty($acl->globalPerms($userinfo)['admin_bypass']);
$canBrowse = method_exists($acl, 'canBrowseLibrary') ? $acl->canBrowseLibrary($userinfo) : $canView;
$limitedAccess = !$canView; // guests / members without full section rights — free library only

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />';
echo '<title>HDD LAND Downloads</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&family=IBM+Plex+Sans:wght@400;600;700&display=swap" rel="stylesheet">';
echo '<style>
:root{--win-bg:#f3f3f3;--win-card:#ffffff;--win-title:#1f1f1f;--win-accent:#0f6cbd;--win-accent2:#115ea3;--win-border:#d1d1d1;--win-muted:#616161;--win-ok:#0e7a0d;--win-vip:#c50f1f;--win-shadow:0 8px 24px rgba(0,0,0,.12)}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:linear-gradient(160deg,#1b3a5a 0%,#0b1c2c 45%,#102a43 100%);font-family:"Segoe UI","IBM Plex Sans",Tahoma,sans-serif;color:var(--win-title)}
.app{max-width:1100px;margin:28px auto;padding:0 14px 40px}
.window{background:var(--win-card);border:1px solid rgba(255,255,255,.2);border-radius:10px;overflow:hidden;box-shadow:var(--win-shadow)}
.titlebar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;background:linear-gradient(180deg,#2b2b2b,#1f1f1f);color:#fff}
.titlebar .left{display:flex;align-items:center;gap:10px;font-weight:700;font-size:.95rem}
.titlebar .dot{width:12px;height:12px;border-radius:50%}
.titlebar .dots{display:flex;gap:6px}
.titlebar .d1{background:#ff5f57}.titlebar .d2{background:#febc2e}.titlebar .d3{background:#28c840}
.menubar{display:flex;flex-wrap:wrap;gap:2px;padding:6px 8px;background:#f0f0f0;border-bottom:1px solid var(--win-border);font-size:.88rem}
.menubar a{color:#1f1f1f;text-decoration:none;padding:6px 10px;border-radius:4px}
.menubar a:hover,.menubar a.active{background:#e5e5e5}
.toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px 12px;background:#fafafa;border-bottom:1px solid var(--win-border)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:36px;padding:8px 14px;border-radius:6px;border:1px solid transparent;background:var(--win-accent);color:#fff!important;text-decoration:none;font-weight:700;font-size:.92rem;cursor:pointer}
.btn:hover{background:var(--win-accent2)}
.btn.secondary{background:#fff;color:#1f1f1f!important;border-color:var(--win-border)}
.btn.vip{background:var(--win-vip)}
.btn[disabled],.btn.disabled{opacity:.55;pointer-events:none}
.content{padding:16px}
h1{margin:0 0 6px;font-size:1.45rem}
.sub{margin:0 0 14px;color:var(--win-muted);font-size:.92rem}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:700}
.badge-free{background:#dff6dd;color:var(--win-ok)}
.badge-paid{background:#fde7e9;color:var(--win-vip)}
.badge-user{background:#e8f3ff;color:var(--win-accent)}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:12px 10px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
.table th{font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:var(--win-muted);background:#fafafa}
.table tr:hover td{background:#f7fbff}
.file-title{font-weight:700;color:#1f1f1f;text-decoration:none}
.file-title:hover{color:var(--win-accent)}
.status{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:8px 12px;background:#f3f3f3;border-top:1px solid var(--win-border);font-size:.82rem;color:var(--win-muted)}
.panel{border:1px solid var(--win-border);border-radius:8px;padding:14px;margin-bottom:14px;background:#fff}
.vip-box{margin-top:12px;padding:12px 14px;background:#fff5f5;border:1px solid #f1aeb5;border-radius:8px}
.denied{color:var(--win-vip)}
.meta{color:var(--win-muted);font-size:.9rem}
.grid-cats{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0 0}
.chip{display:inline-block;padding:6px 10px;border:1px solid var(--win-border);border-radius:999px;background:#fff;text-decoration:none;color:#1f1f1f;font-size:.85rem}
.chip.active{border-color:var(--win-accent);background:#e8f3ff;color:var(--win-accent);font-weight:700}
.cat-board{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:14px}
.cat-card{display:flex;flex-direction:column;gap:8px;padding:16px;border:1px solid var(--win-border);border-radius:10px;background:linear-gradient(180deg,#fff,#f7fafc);text-decoration:none;color:#1f1f1f;box-shadow:0 2px 8px rgba(0,0,0,.04);transition:border-color .15s,transform .15s}
.cat-card:hover{border-color:var(--win-accent);transform:translateY(-1px)}
.cat-card .ico{font-size:1.6rem}
.cat-card .name{font-weight:700;font-size:1.02rem}
.cat-card .count{color:var(--win-muted);font-size:.86rem}
.cat-card .mode{display:inline-flex;align-self:flex-start;padding:2px 8px;border-radius:999px;background:#eef6ff;color:var(--win-accent);font-size:.72rem;font-weight:700}
.link-box{display:flex;flex-direction:column;gap:4px;max-width:280px}
.link-box code{display:block;padding:6px 8px;border:1px solid #e1e1e1;border-radius:6px;background:#fafafa;font-size:.72rem;word-break:break-all;color:#333}
.link-box .copy{font-size:.75rem;color:var(--win-accent);cursor:pointer;background:none;border:0;padding:0;text-align:left}
.crumb{margin:0 0 10px;font-size:.9rem;color:var(--win-muted)}
.crumb a{color:var(--win-accent);text-decoration:none;font-weight:600}
.signin-panel{max-width:420px;background:linear-gradient(180deg,#fafafa,#fff);border-color:#c9d6e5}
.signin-form .signin-row{margin:0 0 12px}
.signin-form label{display:block;font-size:.85rem;font-weight:600;margin:0 0 4px;color:#323130}
.signin-row-inline label{font-weight:500;display:flex;align-items:center;gap:8px}
.signin-input{width:100%;min-height:38px;padding:8px 10px;border:1px solid var(--win-border);border-radius:6px;font:inherit;background:#fff}
.signin-input:focus{outline:2px solid #0f6cbd55;border-color:var(--win-accent)}
.signin-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
.signin-error{margin:0 0 12px;padding:8px 10px;border-radius:6px;background:#fde7e9;color:#c50f1f;border:1px solid #f1aeb5;font-size:.9rem}
.vbdl-vip-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.vbdl-vip-btn{display:inline-flex;align-items:center;padding:8px 12px;border-radius:6px;background:var(--win-accent);color:#fff!important;text-decoration:none;font-weight:700;font-size:.88rem}
.vbdl-vip-telegram{background:#229ED9}.vbdl-vip-whatsapp{background:#128C7E}
.vbdl-limited-box{margin-bottom:14px}
@media (max-width:720px){.table .hide-sm{display:none}.btn{width:100%}.signin-actions .btn{width:auto;flex:1}}
</style></head><body><div class="app"><div class="window">';

echo '<div class="titlebar"><div class="left"><span>💾</span><span>HDD LAND · Downloads Manager</span></div><div class="dots"><span class="dot d1"></span><span class="dot d2"></span><span class="dot d3"></span></div></div>';

echo '<div class="menubar">';
echo '<a class="' . ($view === 'list' && !$fileid ? 'active' : '') . '" href="index.php">Categories</a>';
echo '<a class="' . ($view === 'account' ? 'active' : '') . '" href="index.php?view=account">My Account</a>';
echo '<a href="../index.php">Forum Home</a>';
if (!empty($userinfo['permissions']['adminpermissions']))
{
	echo '<a href="../admincp/vbdlmanager.php?do=dashboard">Admin Panel</a>';
}
echo '</div>';

echo '<div class="toolbar">';
if ($userid > 0)
{
	echo '<span class="badge badge-user">Signed in: ' . vbdl_fe_h($username) . '</span>';
	echo $isVip ? '<span class="badge badge-paid">' . vbdl_fe_h($vipBadge) . ' Member</span>' : '<span class="badge badge-free">Standard Member</span>';
}
else
{
	echo '<span class="badge badge-user">Guest · forum session</span>';
	echo '<a class="btn secondary" href="#vbdl-signin-panel">Forum sign in</a>';
}
echo '<a class="btn secondary" href="index.php">Refresh list</a>';
echo '</div><div class="content">';

// Allow My Account for guests so Sign in is reachable before section ACL.
if ($view === 'account')
{
	echo '<div class="panel">';
	echo '<h1>My Downloads Account</h1>';
	echo '<p class="sub">Your membership status and quick access to files.</p>';
	if ($userid < 1)
	{
		echo '<p class="denied">Please sign in to view your download account.</p>';
		echo '</div>';
		echo vbdl_signin_panel_html();
	}
	else
	{
		echo '<p><strong>Username:</strong> ' . vbdl_fe_h($username) . '</p>';
		echo '<p><strong>Status:</strong> ' . ($isVip ? ('<span class="badge badge-paid">' . vbdl_fe_h($vipBadge) . '</span> You can download Paid/VIP files.') : '<span class="badge badge-free">Standard</span> Free files only. Contact admin for VIP.') . '</p>';
		if (!$isVip)
		{
			echo $service->vipContactMessageHtml();
		}
		echo '<p style="margin-top:16px"><a class="btn" href="index.php">Open Downloads Library</a></p>';
		echo '</div>';
	}
	echo '</div><div class="status"><span>User account cartable</span><span>' . vbdl_fe_h($username) . '</span></div></div></div></body></html>';
	exit;
}

if (!$canBrowse)
{
	echo '<div class="panel"><h1>Downloads</h1>';
	echo $service->limitedAccessMessageHtml();
	echo '<p class="denied" style="margin-top:12px">Your access is limited. Please contact the administrator.</p></div>';
	if ($userid < 1)
	{
		echo vbdl_signin_panel_html();
	}
	echo '</div><div class="status"><span>Limited access</span><span>HDD LAND Downloads</span></div></div></div></body></html>';
	exit;
}

if ($limitedAccess)
{
	echo $service->limitedAccessMessageHtml();
	if ($userid < 1)
	{
		echo '<p class="meta" style="margin:8px 0 14px">Already a forum member? <a href="#vbdl-signin-panel">Sign in with your forum account</a> — Downloads will recognize you automatically.</p>';
	}
}

if ($fileid > 0)
{
	$file = $repo->getFile($fileid);
	echo '<div class="panel">';
	if (!$file || !$acl->canAccessFile($file, $userinfo, 'view'))
	{
		echo '<h1>File unavailable</h1><p class="denied">You do not have permission to view this download.</p>';
	}
	else
	{
		$canDl = $acl->canAccessFile($file, $userinfo, 'download');
		$isPaid = $acl->isPaidFile($file);
		echo '<h1>' . vbdl_fe_h($file['title']) . ' ';
		echo $isPaid ? '<span class="badge badge-paid">' . vbdl_fe_h($vipBadge) . '</span>' : '<span class="badge badge-free">Free</span>';
		echo '</h1>';
		echo '<p class="meta">' . vbdl_fe_h($service->formatBytes($file['filesize'])) . ' · ' . (int)$file['downloads_count'] . ' downloads</p>';
		if ($file['description'] !== '')
		{
			echo '<p>' . nl2br(vbdl_fe_h($file['description'])) . '</p>';
		}
		$dlUrl = $service->downloadUrl((int)$file['fileid'], true);
		$bb = '[download=' . (int)$file['fileid'] . ']' . $file['title'] . '[/download]';
		if ($canDl)
		{
			echo '<p><a class="btn" href="download.php?fileid=' . (int)$file['fileid'] . '">⬇ Download File</a></p>';
			echo '<div class="link-box" style="margin:10px 0;max-width:100%"><strong>Download address</strong>';
			echo '<code id="vbdl-file-url">' . vbdl_fe_h($dlUrl) . '</code>';
			echo '<button type="button" class="copy" onclick="navigator.clipboard.writeText(document.getElementById(\'vbdl-file-url\').textContent)">Copy download link</button></div>';
		}
		else
		{
			echo $service->deniedDownloadMessage($file, $userinfo);
		}
		echo '<p class="meta">BBCode: <code>' . vbdl_fe_h($bb) . '</code></p>';
		if (!empty($file['categoryid']))
		{
			echo '<p class="meta"><a href="index.php?categoryid=' . (int)$file['categoryid'] . '">⟵ Back to category files</a></p>';
		}
	}
	echo '<p><a class="btn secondary" href="index.php">⟵ Back to library</a></p></div>';
	echo '</div><div class="status"><span>File details</span><span>HDD LAND Downloads</span></div></div></div></body></html>';
	exit;
}

$categories = $repo->listCategories(true);
$countsByCat = $repo->countFilesByCategory(true);

if ($categoryid < 1)
{
	echo '<h1>Download Categories</h1>';
	if ($limitedAccess)
	{
		echo '<p class="sub">Open a category to see only its files. Free categories are available below. VIP categories may need a grant.</p>';
	}
	else
	{
		echo '<p class="sub">Each category is its own library. Open a folder to browse only files uploaded into that category.</p>';
	}

	echo '<div class="cat-board">';
	foreach ($categories as $c)
	{
		$cid = (int)$c['categoryid'];
		$cnt = isset($countsByCat[$cid]) ? (int)$countsByCat[$cid] : 0;
		$mode = !empty($c['access_mode']) ? $c['access_mode'] : 'free_open';
		$modeLabel = ($mode === 'grant_required') ? 'Grant required' : 'Open';
		echo '<a class="cat-card" href="index.php?categoryid=' . $cid . '">';
		echo '<span class="ico">📁</span>';
		echo '<span class="name">' . vbdl_fe_h($c['title']) . '</span>';
		echo '<span class="count">' . $cnt . ' file' . ($cnt === 1 ? '' : 's') . '</span>';
		echo '<span class="mode">' . vbdl_fe_h($modeLabel) . '</span>';
		echo '</a>';
	}
	echo '</div>';

	if ($userid < 1)
	{
		echo vbdl_signin_panel_html();
	}

	$totalCats = count($categories);
	echo '</div><div class="status"><span>' . (int)$totalCats . ' categor' . ($totalCats === 1 ? 'y' : 'ies') . '</span><span>' . ($limitedAccess ? 'Limited · Free files' : 'Full library') . ' · HDD LAND</span></div>';
	echo '</div></div></body></html>';
	exit;
}

$currentCat = $repo->getCategory($categoryid);
$total = $repo->countFiles(array('active_only' => true, 'categoryid' => $categoryid));
$offset = ($page - 1) * $perPage;
$files = $repo->listFiles(array('active_only' => true, 'categoryid' => $categoryid, 'limit' => $perPage, 'offset' => $offset));

echo '<p class="crumb"><a href="index.php">Categories</a> / <strong>' . vbdl_fe_h($currentCat ? $currentCat['title'] : ('Category #' . $categoryid)) . '</strong></p>';
echo '<h1>' . vbdl_fe_h($currentCat ? $currentCat['title'] : 'Category') . '</h1>';
echo '<p class="sub">' . (int)$total . ' file' . ($total === 1 ? '' : 's') . ' in this category only. Download address is shown for each file.</p>';

if ($categories)
{
	echo '<div class="grid-cats">';
	foreach ($categories as $c)
	{
		$cid = (int)$c['categoryid'];
		$cnt = isset($countsByCat[$cid]) ? (int)$countsByCat[$cid] : 0;
		$active = ($cid === (int)$categoryid) ? ' active' : '';
		echo '<a class="chip' . $active . '" href="index.php?categoryid=' . $cid . '">' . vbdl_fe_h($c['title']) . ' (' . $cnt . ')</a>';
	}
	echo '</div>';
}

echo '<div class="panel" style="padding:0;overflow:auto;margin-top:14px">';
if (empty($files))
{
	echo '<p style="padding:16px">No files in this category yet.</p>';
}
else
{
	echo '<table class="table"><thead><tr><th>Title</th><th>Access</th><th class="hide-sm">Size</th><th class="hide-sm">Downloads</th><th>Download address</th><th>Action</th></tr></thead><tbody>';
	$shown = 0;
	foreach ($files as $f)
	{
		if (!$acl->canAccessFile($f, $userinfo, 'view'))
		{
			continue;
		}
		$shown++;
		$canDl = $acl->canAccessFile($f, $userinfo, 'download');
		$isPaid = $acl->isPaidFile($f);
		$dlUrl = $service->downloadUrl((int)$f['fileid'], true);
		$uid = 'vbdl-url-' . (int)$f['fileid'];
		echo '<tr>';
		echo '<td><a class="file-title" href="index.php?fileid=' . (int)$f['fileid'] . '&categoryid=' . (int)$categoryid . '">' . vbdl_fe_h($f['title']) . '</a></td>';
		echo '<td>' . ($isPaid ? '<span class="badge badge-paid">' . vbdl_fe_h($vipBadge) . '</span>' : '<span class="badge badge-free">Free</span>') . '</td>';
		echo '<td class="hide-sm">' . vbdl_fe_h($service->formatBytes($f['filesize'])) . '</td>';
		$dlCount = isset($f['downloads_count']) ? (int)$f['downloads_count'] : (isset($f['download_count']) ? (int)$f['download_count'] : 0);
		echo '<td class="hide-sm">' . $dlCount . '</td>';
		echo '<td><div class="link-box"><code id="' . $uid . '">' . vbdl_fe_h($dlUrl) . '</code>';
		echo '<button type="button" class="copy" onclick="navigator.clipboard.writeText(document.getElementById(\'' . $uid . '\').textContent)">Copy link</button></div></td>';
		if ($canDl)
		{
			echo '<td><a class="btn" href="download.php?fileid=' . (int)$f['fileid'] . '" title="Download this file">⬇ Download</a></td>';
		}
		elseif ($acl->needsVipPurchase($f, $userinfo))
		{
			echo '<td><a class="btn vip" href="index.php?fileid=' . (int)$f['fileid'] . '">Get VIP Access</a></td>';
		}
		else
		{
			echo '<td><span class="denied">No access</span></td>';
		}
		echo '</tr>';
	}
	if ($shown < 1)
	{
		echo '<tr><td colspan="6" style="padding:16px">No files visible for your account in this category.</td></tr>';
	}
	echo '</tbody></table>';
}
echo '</div>';

$pages = max(1, (int)ceil($total / $perPage));
if ($pages > 1)
{
	echo '<p class="meta" style="margin-top:12px">Page: ';
	for ($p = 1; $p <= $pages; $p++)
	{
		$url = 'index.php?categoryid=' . (int)$categoryid . '&page=' . $p;
		echo ($p === $page) ? '<strong>' . $p . '</strong> ' : '<a href="' . $url . '">' . $p . '</a> ';
	}
	echo '</p>';
}

if ($userid < 1)
{
	echo vbdl_signin_panel_html();
}

echo '</div><div class="status"><span>' . (int)$total . ' file(s) in this category</span><span>' . ($limitedAccess ? 'Limited · Free files' : 'Full library') . ' · HDD LAND</span></div>';
echo '</div></div></body></html>';
