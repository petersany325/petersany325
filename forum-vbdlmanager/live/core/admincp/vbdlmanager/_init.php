<?php
/**
 * Download Manager - shared AdminCP helpers + 2026 UI shell.
 */

function vbdl_admin_paths()
{
	static $paths = null;
	if ($paths !== null)
	{
		return $paths;
	}
	// _init.php lives in admincp/vbdlmanager/ — do not treat that folder as AdminCP root.
	$admin = dirname(dirname(__FILE__));
	if (defined('DIR') && is_dir(DIR . '/packages/vbdlmanager'))
	{
		$core = rtrim(str_replace('\\', '/', DIR), '/');
		$forum = dirname($core);
	}
	else
	{
		// core/admincp/... → core; legacy forum/admincp/... → forum/core
		$coreCandidate = dirname($admin);
		if (is_dir($coreCandidate . '/packages/vbdlmanager'))
		{
			$core = $coreCandidate;
			$forum = dirname($core);
		}
		else
		{
			$forum = dirname($admin);
			$core = is_dir($forum . '/core') ? ($forum . '/core') : $forum;
		}
	}
	$paths = array(
		'admin' => $admin,
		'forum' => $forum,
		'core' => $core,
		'package' => $core . '/packages/vbdlmanager',
	);
	return $paths;
}

function vbdl_admin_init()
{
	$paths = vbdl_admin_paths();
	require_once $paths['package'] . '/library/Bootstrap.php';
	require_once $paths['package'] . '/library/Db.php';
	vbdl_Bootstrap::init(vbdl_Db::connection(), vbdl_Db::tablePrefix());
}

function vbdl_admin_require()
{
	global $vbulletin;
	$ok = false;
	if (!empty($vbulletin->userinfo['permissions']['adminpermissions']))
	{
		$ok = true;
	}
	if (!$ok && class_exists('vB', false) && is_callable(array('vB', 'getUserContext')))
	{
		try
		{
			$ctx = vB::getUserContext();
			if (is_object($ctx) && method_exists($ctx, 'hasAdminPermission') && $ctx->hasAdminPermission('canadminsettings'))
			{
				$ok = true;
			}
		}
		catch (Exception $e)
		{
		}
	}
	if (!$ok)
	{
		print_cp_message('You do not have permission to access Download Manager administration.');
		exit;
	}
}

function vbdl_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function vbdl_request_token_field()
{
	global $vbulletin;
	// Match native print_form_header(): emit BOTH
	// - adminhash: required by AdminCP global.php POST gate (missing → fake logout)
	// - securitytoken: timed CSRF token for verify_security_token()
	if (defined('ADMINHASH'))
	{
		echo '<input type="hidden" name="adminhash" value="' . vbdl_h(ADMINHASH) . '" />' . "\n";
	}

	$token = '';
	try
	{
		if (class_exists('vB', false) && is_callable(array('vB', 'getCurrentSession')))
		{
			$session = vB::getCurrentSession();
			if ($session)
			{
				$userInfo = $session->fetch_userinfo();
				if (!empty($userInfo['securitytoken']))
				{
					$token = $userInfo['securitytoken'];
				}
			}
		}
	}
	catch (Exception $e)
	{
		$token = '';
	}
	if ($token === '' && !empty($vbulletin->userinfo['securitytoken']))
	{
		$token = $vbulletin->userinfo['securitytoken'];
	}
	echo '<input type="hidden" name="securitytoken" value="' . vbdl_h($token) . '" />' . "\n";
}

function vbdl_check_token()
{
	global $vbulletin;

	// Submitted CSRF token (prefer POST).
	$token = '';
	if (isset($_POST['securitytoken']))
	{
		$token = (string)$_POST['securitytoken'];
	}
	else if (isset($_REQUEST['securitytoken']))
	{
		$token = (string)$_REQUEST['securitytoken'];
	}

	// vB securitytoken is TIMENOW-sha1(...) and is regenerated every request.
	// Comparing the submitted value to the CURRENT userinfo['securitytoken']
	// almost always fails. Native verification uses securitytoken_raw via
	// verify_security_token() (allows tokens up to 3 hours old).
	$raw = '';
	if (!empty($vbulletin->userinfo['securitytoken_raw']))
	{
		$raw = (string)$vbulletin->userinfo['securitytoken_raw'];
	}

	if ($raw !== '' && function_exists('verify_security_token'))
	{
		if (!verify_security_token($token, $raw))
		{
			print_cp_message('Security token mismatch. Please try again.');
			exit;
		}
		return;
	}

	// Fallback: AdminCP global.php already validated adminhash for POSTs with "do".
	// Soft-accept when adminhash matches so we do not block saves with a bad timed compare.
	if (defined('ADMINHASH') && !empty($_POST['do']))
	{
		$ah = '';
		if (isset($_POST['adminhash']))
		{
			$ah = (string)$_POST['adminhash'];
		}
		else if (isset($_REQUEST['adminhash']))
		{
			$ah = (string)$_REQUEST['adminhash'];
		}
		if ($ah !== '' && hash_equals((string)ADMINHASH, $ah))
		{
			return;
		}
	}

	if ($token === '' || empty($vbulletin->userinfo['securitytoken']) || !hash_equals((string)$vbulletin->userinfo['securitytoken'], $token))
	{
		print_cp_message('Security token mismatch. Please try again.');
		exit;
	}
}

function vbdl_list_usergroups()
{
	require_once vbdl_admin_paths()['package'] . '/library/Db.php';
	$db = vbdl_Db::connection();
	$tp = vbdl_Db::tablePrefix();
	$out = array();
	if (!$db)
	{
		return $out;
	}
	$res = $db->query_read("SELECT usergroupid, title FROM {$tp}usergroup ORDER BY usergroupid ASC");
	while ($row = $db->fetch_array($res))
	{
		$out[] = $row;
	}
	return $out;
}

function vbdl_admin_menu_items()
{
	return array(
		'dashboard' => array('label' => 'Dashboard', 'desc' => 'Overview, KPIs and health'),
		'files' => array('label' => 'Files', 'desc' => 'Upload, edit, permissions'),
		'categories' => array('label' => 'Categories', 'desc' => 'Organize downloads'),
		'vipusers' => array('label' => 'VIP Users', 'desc' => 'Add VIP by username + counts'),
		'storage' => array('label' => 'Storage', 'desc' => 'Local host and S3 cloud'),
		'access' => array('label' => 'User Access', 'desc' => 'Usergroup capability matrix'),
		'logs' => array('label' => 'Logs', 'desc' => 'Audit trail and CSV export'),
		'settings' => array('label' => 'Settings', 'desc' => 'Limits, modes, VIP contact'),
		'tools' => array('label' => 'Tools', 'desc' => 'Repair schema, diagnostics'),
	);
}

function vbdl_admin_script()
{
	// AdminCP <base href> is frontendurl/; links must include admincp/ prefix
	// or the browser resolves to /vbdlmanager.php → Invalid Page URL.
	return 'admincp/vbdlmanager.php';
}

function vbdl_admin_url($do, $extra = '')
{
	$url = vbdl_admin_script() . '?do=' . rawurlencode($do);
	if ($extra !== '')
	{
		$url .= '&' . ltrim($extra, '&');
	}
	return $url;
}

function vbdl_admin_assets()
{
	static $done = false;
	if ($done)
	{
		return;
	}
	$done = true;
	echo '<style>
.vbdl-shell{font-family:Segoe UI,system-ui,-apple-system,sans-serif;color:#0f172a;margin:8px 0 24px}
.vbdl-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-end;margin-bottom:16px;padding:18px 20px;border-radius:14px;background:linear-gradient(135deg,#0b1f33 0%,#163a5f 55%,#1d4f7c 100%);color:#fff;box-shadow:0 10px 30px rgba(15,23,42,.18)}
.vbdl-hero h1{margin:0;font-size:1.55rem;font-weight:700;letter-spacing:.2px}
.vbdl-hero p{margin:6px 0 0;opacity:.85;font-size:.92rem}
.vbdl-badge{display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.14);font-size:.75rem;font-weight:600}
.vbdl-layout{display:grid;grid-template-columns:220px 1fr;gap:16px}
@media (max-width:960px){.vbdl-layout{grid-template-columns:1fr}}
.vbdl-side{background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:10px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.vbdl-side a{display:block;padding:10px 12px;border-radius:10px;text-decoration:none;color:#1e293b;margin-bottom:4px}
.vbdl-side a:hover{background:#f1f5f9}
.vbdl-side a.active{background:#0f172a;color:#fff}
.vbdl-side .meta{display:block;font-size:.72rem;opacity:.7;margin-top:2px}
.vbdl-main{min-width:0}
.vbdl-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
@media (max-width:1100px){.vbdl-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:640px){.vbdl-cards{grid-template-columns:1fr}}
.vbdl-card{background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.vbdl-card .k{font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;font-weight:600}
.vbdl-card .v{font-size:1.55rem;font-weight:700;margin-top:6px}
.vbdl-panel{background:#fff;border:1px solid #dbe3ee;border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04);margin-bottom:16px}
.vbdl-panel-h{padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;background:#f8fafc}
.vbdl-panel-b{padding:16px}
.vbdl-table{width:100%;border-collapse:collapse}
.vbdl-table th,.vbdl-table td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:left;font-size:.92rem;vertical-align:top}
.vbdl-table th{font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}
.vbdl-btn{display:inline-block;background:#0f172a;color:#fff!important;text-decoration:none;border:0;border-radius:10px;padding:8px 14px;font-weight:600;cursor:pointer}
.vbdl-btn.secondary{background:#e2e8f0;color:#0f172a!important}
.vbdl-btn.danger{background:#b91c1c}
.vbdl-btn:hover{opacity:.92}
.vbdl-input, .vbdl-select, .vbdl-textarea{width:100%;max-width:560px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:10px;font:inherit}
.vbdl-textarea{min-height:110px}
.vbdl-form-row{display:grid;grid-template-columns:200px 1fr;gap:12px;margin-bottom:12px;align-items:start}
@media (max-width:720px){.vbdl-form-row{grid-template-columns:1fr}}
.vbdl-muted{color:#64748b;font-size:.9rem}
.vbdl-ok{color:#047857;font-weight:600}
.vbdl-bad{color:#b91c1c;font-weight:600}
.vbdl-chip{display:inline-block;padding:2px 8px;border-radius:999px;background:#e2e8f0;font-size:.75rem;font-weight:600}
.vbdl-chip.ok{background:#d1fae5;color:#065f46}
.vbdl-chip.no{background:#fee2e2;color:#991b1b}
.vbdl-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
</style>';
}

function vbdl_admin_header($title, $do = 'dashboard')
{
	print_cp_header('Download Manager - ' . $title);
	vbdl_admin_assets();
	$items = vbdl_admin_menu_items();
	echo '<div class="vbdl-shell">';
	echo '<div class="vbdl-hero">';
	echo '<div><div class="vbdl-badge">vBulletin Download Manager 2026</div>';
	echo '<h1>' . vbdl_h($title) . '</h1>';
	echo '<p>Advanced host + cloud download control with usergroup ACL, storage profiles, and audit logs.</p></div>';
	echo '<div><a class="vbdl-btn secondary" href="' . vbdl_h('../vbdlmanager/index.php') . '" target="_blank" rel="noopener">Open Downloads App</a> ';
	echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('vipusers')) . '">VIP Users</a> ';
	echo '<a class="vbdl-btn" href="' . vbdl_h(vbdl_admin_url('files', 'action=add')) . '">Add File</a></div>';
	echo '</div>';
	echo '<div class="vbdl-layout"><nav class="vbdl-side">';
	foreach ($items as $key => $item)
	{
		$cls = ($key === $do) ? 'active' : '';
		echo '<a class="' . $cls . '" href="' . vbdl_h(vbdl_admin_url($key)) . '"><strong>' . vbdl_h($item['label']) . '</strong><span class="meta">' . vbdl_h($item['desc']) . '</span></a>';
	}
	echo '</nav><div class="vbdl-main">';
}

function vbdl_admin_footer()
{
	echo '</div></div></div>';
	print_cp_footer();
}

function vbdl_admin_nav()
{
	// Back-compat for old scripts; prefer vbdl_admin_header().
	vbdl_admin_assets();
}
