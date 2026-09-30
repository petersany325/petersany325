<?php
/**
 * AdminCP: Add / remove VIP members — search by username/email + exact username add.
 */
$repo = vbdl_Bootstrap::$repo;
global $vbulletin, $db;

$action = isset($_REQUEST['action']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['action'])) : 'list';
$message = '';
$error = '';
$searchQ = trim(isset($_REQUEST['q']) ? (string)$_REQUEST['q'] : '');
$searchResults = array();

function vbdl_vip_group_ids_configured($repo)
{
	$ids = array();
	foreach (explode(',', (string)$repo->getSetting('vip_usergroupids', '14,15')) as $g)
	{
		$g = (int)trim($g);
		if ($g > 0)
		{
			$ids[] = $g;
		}
	}
	return array_values(array_unique($ids));
}

function vbdl_vip_usergroup_map()
{
	global $db;
	$out = array();
	$res = $db->query_read("SELECT usergroupid, title FROM usergroup ORDER BY usergroupid ASC");
	while ($row = $db->fetch_array($res))
	{
		$out[(int)$row['usergroupid']] = $row['title'];
	}
	return $out;
}

function vbdl_user_has_group(array $user, $ugid)
{
	$ugid = (int)$ugid;
	if ((int)$user['usergroupid'] === $ugid)
	{
		return true;
	}
	$ids = array_filter(array_map('intval', explode(',', (string)$user['membergroupids'])));
	return in_array($ugid, $ids, true);
}

function vbdl_add_member_group(array $user, $ugid)
{
	global $db;
	$ugid = (int)$ugid;
	$userid = (int)$user['userid'];
	if (vbdl_user_has_group($user, $ugid))
	{
		return false;
	}
	$ids = array_filter(array_map('intval', explode(',', (string)$user['membergroupids'])));
	$ids[] = $ugid;
	$ids = array_values(array_unique($ids));
	$csv = $db->escape_string(implode(',', $ids));
	$db->query_write("UPDATE user SET membergroupids = '{$csv}' WHERE userid = {$userid}");
	return true;
}

function vbdl_remove_member_group(array $user, $ugid)
{
	global $db;
	$ugid = (int)$ugid;
	$userid = (int)$user['userid'];
	$ids = array_filter(array_map('intval', explode(',', (string)$user['membergroupids'])));
	$ids = array_values(array_diff($ids, array($ugid)));
	$csv = $db->escape_string(implode(',', $ids));
	$setPrimary = '';
	if ((int)$user['usergroupid'] === $ugid)
	{
		// Do not demote primary VIP to guest unexpectedly — move to Registered (2) if primary was VIP
		$setPrimary = ', usergroupid = 2';
	}
	$db->query_write("UPDATE user SET membergroupids = '{$csv}'{$setPrimary} WHERE userid = {$userid}");
	return true;
}

function vbdl_user_group_labels(array $user, array $groupMap)
{
	$ids = array((int)$user['usergroupid']);
	foreach (array_filter(array_map('intval', explode(',', (string)$user['membergroupids']))) as $g)
	{
		$ids[] = $g;
	}
	$ids = array_values(array_unique($ids));
	$labels = array();
	foreach ($ids as $g)
	{
		$labels[] = (isset($groupMap[$g]) ? $groupMap[$g] : ('#' . $g)) . ' (#' . $g . ')';
	}
	return implode(', ', $labels);
}

$vipIds = vbdl_vip_group_ids_configured($repo);
$groupMap = vbdl_vip_usergroup_map();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
	if ($action === 'add' || $action === 'add_userid')
	{
		$ugid = (int)(isset($_POST['usergroupid']) ? $_POST['usergroupid'] : 0);
		$user = null;
		if ($action === 'add_userid')
		{
			$userid = (int)(isset($_POST['userid']) ? $_POST['userid'] : 0);
			if ($userid > 0)
			{
				$user = $db->query_first("SELECT userid, username, email, usergroupid, membergroupids FROM user WHERE userid = {$userid}");
			}
		}
		else
		{
			$username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
			if ($username !== '')
			{
				$safe = $db->escape_string($username);
				$user = $db->query_first("SELECT userid, username, email, usergroupid, membergroupids FROM user WHERE username = '{$safe}'");
			}
		}

		if (!$user || !in_array($ugid, $vipIds, true))
		{
			$error = 'Select a valid user and VIP group.';
			if ($action === 'add' && empty($user) && trim(isset($_POST['username']) ? (string)$_POST['username'] : '') !== '')
			{
				$error = 'User not found: ' . trim((string)$_POST['username']);
			}
		}
		elseif (vbdl_user_has_group($user, $ugid))
		{
			$message = 'User already has this VIP group.';
		}
		else
		{
			vbdl_add_member_group($user, $ugid);
			$message = 'Added ' . $user['username'] . ' to VIP group #' . $ugid . (isset($groupMap[$ugid]) ? (' (' . $groupMap[$ugid] . ')') : '') . '.';
		}
		// Keep search results visible after add-from-search
		if ($searchQ === '' && isset($_POST['q']))
		{
			$searchQ = trim((string)$_POST['q']);
		}
	}
	elseif ($action === 'remove')
	{
		$userid = (int)(isset($_POST['userid']) ? $_POST['userid'] : 0);
		$ugid = (int)(isset($_POST['usergroupid']) ? $_POST['usergroupid'] : 0);
		$user = $db->query_first("SELECT userid, username, usergroupid, membergroupids FROM user WHERE userid = {$userid}");
		if (!$user || !in_array($ugid, $vipIds, true))
		{
			$error = 'Unable to remove VIP membership.';
		}
		else
		{
			vbdl_remove_member_group($user, $ugid);
			$message = 'Removed VIP group from ' . $user['username'] . '.';
		}
	}
}

// Partial search (username / email LIKE)
if ($searchQ !== '' && strlen($searchQ) >= 2)
{
	$like = $db->escape_string('%' . str_replace(array('%', '_'), array('\\%', '\\_'), $searchQ) . '%');
	$res = $db->query_read("
		SELECT userid, username, email, usergroupid, membergroupids
		FROM user
		WHERE username LIKE '{$like}' OR email LIKE '{$like}'
		ORDER BY username ASC
		LIMIT 50
	");
	while ($row = $db->fetch_array($res))
	{
		$searchResults[] = $row;
	}
}

// Counts
$counts = array();
$totalVipUsers = 0;
foreach ($vipIds as $ugid)
{
	$row = $db->query_first("
		SELECT COUNT(*) AS c FROM user
		WHERE usergroupid = {$ugid} OR FIND_IN_SET('{$ugid}', membergroupids)
	");
	$counts[$ugid] = (int)(isset($row['c']) ? $row['c'] : 0);
}

$whereParts = array('0');
foreach ($vipIds as $ugid)
{
	$whereParts[] = "usergroupid = {$ugid}";
	$whereParts[] = "FIND_IN_SET('{$ugid}', membergroupids)";
}
$vipUsers = array();
$res = $db->query_read("
	SELECT userid, username, usergroupid, membergroupids, lastactivity
	FROM user
	WHERE " . implode(' OR ', $whereParts) . "
	ORDER BY username ASC
	LIMIT 500
");
while ($row = $db->fetch_array($res))
{
	$vipUsers[] = $row;
}
$totalVipUsers = count($vipUsers);

vbdl_admin_header('VIP Users', 'vipusers');

if ($message !== '')
{
	echo '<div class="vbdl-panel"><div class="vbdl-panel-b"><p class="vbdl-ok">' . vbdl_h($message) . '</p></div></div>';
}
if ($error !== '')
{
	echo '<div class="vbdl-panel"><div class="vbdl-panel-b"><p class="vbdl-bad">' . vbdl_h($error) . '</p></div></div>';
}

echo '<div class="vbdl-cards">';
echo '<div class="vbdl-card"><div class="k">VIP members listed</div><div class="v">' . (int)$totalVipUsers . '</div></div>';
foreach ($vipIds as $ugid)
{
	$title = isset($groupMap[$ugid]) ? $groupMap[$ugid] : ('Group #' . $ugid);
	echo '<div class="vbdl-card"><div class="k">' . vbdl_h($title) . ' (#' . (int)$ugid . ')</div><div class="v">' . (int)$counts[$ugid] . '</div></div>';
}
echo '</div>';

// --- Search users ---
echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Search users (add VIP)</div><div class="vbdl-panel-b">';
echo '<p class="vbdl-muted">Search by partial username or email, then click <strong>Add VIP</strong> on a result row.</p>';
echo '<form method="get" action="admincp/vbdlmanager.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px">';
echo '<input type="hidden" name="do" value="vipusers" />';
echo '<input class="vbdl-input" style="max-width:320px" type="text" name="q" value="' . vbdl_h($searchQ) . '" placeholder="Username or email (partial)…" minlength="2" />';
echo '<button class="vbdl-btn" type="submit">Search users</button>';
if ($searchQ !== '')
{
	echo '<a class="vbdl-btn secondary" href="admincp/vbdlmanager.php?do=vipusers">Clear</a>';
}
echo '</form>';

if ($searchQ !== '' && strlen($searchQ) < 2)
{
	echo '<p class="vbdl-bad">Enter at least 2 characters to search.</p>';
}
elseif ($searchQ !== '')
{
	if (empty($searchResults))
	{
		echo '<p class="vbdl-muted">No users matched <strong>' . vbdl_h($searchQ) . '</strong>.</p>';
	}
	else
	{
		echo '<table class="vbdl-table"><tr><th>User ID</th><th>Username</th><th>Email</th><th>Usergroups</th><th>Add VIP</th></tr>';
		foreach ($searchResults as $su)
		{
			echo '<tr>';
			echo '<td>#' . (int)$su['userid'] . '</td>';
			echo '<td><strong>' . vbdl_h($su['username']) . '</strong></td>';
			echo '<td>' . vbdl_h(isset($su['email']) ? $su['email'] : '') . '</td>';
			echo '<td class="vbdl-muted">' . vbdl_h(vbdl_user_group_labels($su, $groupMap)) . '</td>';
			echo '<td>';
			if (empty($vipIds))
			{
				echo '<span class="vbdl-muted">Configure VIP groups first</span>';
			}
			else
			{
				echo '<form method="post" action="admincp/vbdlmanager.php" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:0">';
				vbdl_request_token_field();
				echo '<input type="hidden" name="do" value="vipusers" />';
				echo '<input type="hidden" name="action" value="add_userid" />';
				echo '<input type="hidden" name="userid" value="' . (int)$su['userid'] . '" />';
				echo '<input type="hidden" name="q" value="' . vbdl_h($searchQ) . '" />';
				echo '<select class="vbdl-select" name="usergroupid" style="max-width:200px">';
				foreach ($vipIds as $ugid)
				{
					$title = isset($groupMap[$ugid]) ? $groupMap[$ugid] : ('Group #' . $ugid);
					$already = vbdl_user_has_group($su, $ugid);
					echo '<option value="' . (int)$ugid . '"' . ($already ? ' disabled' : '') . '>' . vbdl_h($title) . ' (#' . (int)$ugid . ')' . ($already ? ' — already' : '') . '</option>';
				}
				echo '</select>';
				echo '<button class="vbdl-btn" type="submit">Add VIP</button></form>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
}
echo '</div></div>';

// --- Exact username add (kept) ---
echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Add VIP by exact username</div><div class="vbdl-panel-b">';
if (empty($vipIds))
{
	echo '<p class="vbdl-bad">No VIP usergroup IDs configured. Set them in Settings first.</p>';
}
else
{
	echo '<form method="post" action="admincp/vbdlmanager.php">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="vipusers" /><input type="hidden" name="action" value="add" />';
	echo '<div class="vbdl-form-row"><label>Username</label><input class="vbdl-input" type="text" name="username" required placeholder="Exact forum username" /></div>';
	echo '<div class="vbdl-form-row"><label>VIP group</label><select class="vbdl-select" name="usergroupid">';
	foreach ($vipIds as $ugid)
	{
		$title = isset($groupMap[$ugid]) ? $groupMap[$ugid] : ('Group #' . $ugid);
		echo '<option value="' . (int)$ugid . '">' . vbdl_h($title) . ' (#' . (int)$ugid . ')</option>';
	}
	echo '</select></div>';
	echo '<div class="vbdl-actions"><button class="vbdl-btn" type="submit">Add user as VIP</button>';
	echo '<a class="vbdl-btn secondary" href="' . vbdl_h('../vbdlmanager/index.php') . '" target="_blank" rel="noopener">Open Downloads App</a></div>';
	echo '</form>';
}
echo '</div></div>';

echo '<div class="vbdl-panel"><div class="vbdl-panel-h">VIP members</div><div class="vbdl-panel-b">';
echo '<table class="vbdl-table"><tr><th>User</th><th>Primary group</th><th>VIP groups</th><th>Last activity</th><th></th></tr>';
if (empty($vipUsers))
{
	echo '<tr><td colspan="5" class="vbdl-muted">No VIP users found.</td></tr>';
}
foreach ($vipUsers as $u)
{
	$userVip = array();
	if (in_array((int)$u['usergroupid'], $vipIds, true))
	{
		$userVip[] = (int)$u['usergroupid'];
	}
	foreach (array_filter(array_map('intval', explode(',', (string)$u['membergroupids']))) as $g)
	{
		if (in_array($g, $vipIds, true))
		{
			$userVip[] = $g;
		}
	}
	$userVip = array_values(array_unique($userVip));
	$labels = array();
	foreach ($userVip as $g)
	{
		$labels[] = (isset($groupMap[$g]) ? $groupMap[$g] : ('#' . $g)) . ' (#' . $g . ')';
	}
	$primary = isset($groupMap[(int)$u['usergroupid']]) ? $groupMap[(int)$u['usergroupid']] : ('#' . (int)$u['usergroupid']);
	echo '<tr>';
	echo '<td><strong>' . vbdl_h($u['username']) . '</strong> <span class="vbdl-muted">#' . (int)$u['userid'] . '</span></td>';
	echo '<td>' . vbdl_h($primary) . '</td>';
	echo '<td>' . vbdl_h(implode(', ', $labels)) . '</td>';
	echo '<td>' . (!empty($u['lastactivity']) ? vbdl_h(date('Y-m-d H:i', (int)$u['lastactivity'])) : '—') . '</td>';
	echo '<td>';
	foreach ($userVip as $g)
	{
		echo '<form method="post" action="admincp/vbdlmanager.php" style="display:inline-block;margin:0 4px 4px 0" onsubmit="return confirm(\'Remove this VIP group?\');">';
		vbdl_request_token_field();
		echo '<input type="hidden" name="do" value="vipusers" /><input type="hidden" name="action" value="remove" />';
		echo '<input type="hidden" name="userid" value="' . (int)$u['userid'] . '" />';
		echo '<input type="hidden" name="usergroupid" value="' . (int)$g . '" />';
		echo '<button class="vbdl-btn danger" type="submit">Remove #' . (int)$g . '</button></form>';
	}
	echo '</td></tr>';
}
echo '</table></div></div>';

vbdl_admin_footer();
