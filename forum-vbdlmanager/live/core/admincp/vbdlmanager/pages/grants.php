<?php
/**
 * AdminCP: Access Grants
 * VIP SeDiv membership alone does NOT unlock grant_required categories.
 */
$repo = vbdl_Bootstrap::$repo;
$groups = vbdl_list_usergroups();
$categories = $repo->listCategories(false);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	if ($action === 'delete' && !empty($_POST['grantid']))
	{
		$repo->deleteGrant((int)$_POST['grantid']);
		$msg = 'Grant deleted.';
	}
	else if ($action === 'save')
	{
		$granteeType = (isset($_POST['grantee_type']) && $_POST['grantee_type'] === 'usergroup') ? 'usergroup' : 'user';
		$granteeId = 0;
		if ($granteeType === 'user')
		{
			$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
			$granteeId = $repo->findUserIdByUsername($username);
			if ($granteeId < 1)
			{
				$msg = 'User not found: ' . $username;
			}
		}
		else
		{
			$granteeId = isset($_POST['usergroupid']) ? (int)$_POST['usergroupid'] : 0;
			if ($granteeId < 1)
			{
				$msg = 'Select a usergroup.';
			}
		}

		if ($msg === '')
		{
			$targetType = (isset($_POST['target_type']) && $_POST['target_type'] === 'file') ? 'file' : 'category';
			$targetId = isset($_POST['targetid']) ? (int)$_POST['targetid'] : 0;
			if ($targetType === 'file')
			{
				$targetId = isset($_POST['fileid']) ? (int)$_POST['fileid'] : $targetId;
			}
			if ($targetId < 1)
			{
				$msg = 'Select a valid category or enter a file id.';
			}
			else
			{
				global $vbulletin;
				$repo->saveGrant(array(
					'target_type' => $targetType,
					'targetid' => $targetId,
					'grantee_type' => $granteeType,
					'granteeid' => $granteeId,
					'can_view' => !empty($_POST['can_view']) ? 1 : 0,
					'can_download' => !empty($_POST['can_download']) ? 1 : 0,
					'can_upload' => !empty($_POST['can_upload']) ? 1 : 0,
					'note' => isset($_POST['note']) ? $_POST['note'] : '',
					'grantedby' => !empty($vbulletin->userinfo['userid']) ? (int)$vbulletin->userinfo['userid'] : 0,
					'expires' => 0,
				));
				$msg = 'Access grant saved.';
			}
		}
	}
}

$grants = $repo->listGrants(array(), 300);
vbdl_admin_header('Access Grants', 'grants');

echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Policy</div><div class="vbdl-panel-b">';
echo '<p class="vbdl-muted" style="margin:0">Set VIP categories to <strong>grant_required</strong>. '
	. 'Members of VIP SeDiv (or any VIP usergroup) still cannot download/upload there until you create a grant for that user or group. '
	. 'Enable <em>Upload</em> on a grant to show that category in the post editor Downloads menu.</p>';
echo '</div></div>';

if ($msg !== '')
{
	echo '<div class="vbdl-panel"><div class="vbdl-panel-b"><strong>' . vbdl_h($msg) . '</strong></div></div>';
}

echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Add / update grant</div><div class="vbdl-panel-b">';
echo '<form method="post" action="admincp/vbdlmanager.php">';
vbdl_request_token_field();
echo '<input type="hidden" name="do" value="grants" /><input type="hidden" name="action" value="save" />';
echo '<div class="vbdl-form-row"><label>Grantee type</label><select class="vbdl-select" name="grantee_type" id="vbdl-grantee-type">';
echo '<option value="user">User (username)</option><option value="usergroup">Usergroup</option></select></div>';
echo '<div class="vbdl-form-row" id="vbdl-user-row"><label>Username</label><input class="vbdl-input" name="username" placeholder="exact forum username" /></div>';
echo '<div class="vbdl-form-row" id="vbdl-group-row" style="display:none"><label>Usergroup</label><select class="vbdl-select" name="usergroupid">';
foreach ($groups as $g)
{
	echo '<option value="' . (int)$g['usergroupid'] . '">' . vbdl_h($g['title']) . ' (#' . (int)$g['usergroupid'] . ')</option>';
}
echo '</select></div>';
echo '<div class="vbdl-form-row"><label>Target</label><select class="vbdl-select" name="target_type" id="vbdl-target-type">';
echo '<option value="category">Category</option><option value="file">File ID</option></select></div>';
echo '<div class="vbdl-form-row" id="vbdl-cat-row"><label>Category</label><select class="vbdl-select" name="targetid">';
foreach ($categories as $c)
{
	$mode = !empty($c['access_mode']) ? $c['access_mode'] : 'free_open';
	echo '<option value="' . (int)$c['categoryid'] . '">' . vbdl_h($c['title']) . ' [' . vbdl_h($mode) . ']</option>';
}
echo '</select></div>';
echo '<div class="vbdl-form-row" id="vbdl-file-row" style="display:none"><label>File ID</label><input class="vbdl-input" name="fileid" type="number" min="1" placeholder="numeric fileid" /></div>';
echo '<div class="vbdl-form-row"><label>Permissions</label><div>';
echo '<label><input type="checkbox" name="can_view" value="1" checked /> View</label> &nbsp; ';
echo '<label><input type="checkbox" name="can_download" value="1" checked /> Download</label> &nbsp; ';
echo '<label><input type="checkbox" name="can_upload" value="1" /> Upload (post menu)</label></div></div>';
echo '<div class="vbdl-form-row"><label>Note</label><input class="vbdl-input" name="note" /></div>';
echo '<div class="vbdl-actions"><button class="vbdl-btn" type="submit">Save grant</button></div></form></div></div>';

echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Current grants</div><div class="vbdl-panel-b" style="overflow:auto">';
echo '<table class="vbdl-table"><tr><th>ID</th><th>Target</th><th>Grantee</th><th>V</th><th>D</th><th>U</th><th>Note</th><th></th></tr>';
if (empty($grants))
{
	echo '<tr><td colspan="8" class="vbdl-muted">No grants yet.</td></tr>';
}
foreach ($grants as $g)
{
	echo '<tr>';
	echo '<td>' . (int)$g['grantid'] . '</td>';
	echo '<td>' . vbdl_h($g['target_type']) . ' #' . (int)$g['targetid'] . '</td>';
	echo '<td>' . vbdl_h($g['grantee_type']) . ' #' . (int)$g['granteeid'] . '</td>';
	echo '<td>' . (!empty($g['can_view']) ? 'Y' : '-') . '</td>';
	echo '<td>' . (!empty($g['can_download']) ? 'Y' : '-') . '</td>';
	echo '<td>' . (!empty($g['can_upload']) ? 'Y' : '-') . '</td>';
	echo '<td>' . vbdl_h($g['note']) . '</td>';
	echo '<td><form method="post" action="admincp/vbdlmanager.php" style="display:inline">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="grants" /><input type="hidden" name="action" value="delete" />';
	echo '<input type="hidden" name="grantid" value="' . (int)$g['grantid'] . '" />';
	echo '<button class="vbdl-btn danger" type="submit">Delete</button></form></td>';
	echo '</tr>';
}
echo '</table></div></div>';
echo '<script>(function(){function sync(){var t=document.getElementById("vbdl-grantee-type").value;document.getElementById("vbdl-user-row").style.display=t==="user"?"":"none";document.getElementById("vbdl-group-row").style.display=t==="usergroup"?"":"none";var tt=document.getElementById("vbdl-target-type").value;document.getElementById("vbdl-cat-row").style.display=tt==="category"?"":"none";document.getElementById("vbdl-file-row").style.display=tt==="file"?"":"none";}document.getElementById("vbdl-grantee-type").addEventListener("change",sync);document.getElementById("vbdl-target-type").addEventListener("change",sync);sync();})();</script>';
vbdl_admin_footer();
