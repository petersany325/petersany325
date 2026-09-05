<?php
$repo = vbdl_Bootstrap::$repo;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$categoryid = (int)(isset($_POST['categoryid']) ? $_POST['categoryid'] : 0);
	$categoryid = $repo->saveCategory(array(
		'title' => isset($_POST['title']) ? $_POST['title'] : '',
		'description' => isset($_POST['description']) ? $_POST['description'] : '',
		'displayorder' => (int)(isset($_POST['displayorder']) ? $_POST['displayorder'] : 0),
		'active' => !empty($_POST['active']) ? 1 : 0,
		'access_mode' => (isset($_POST['access_mode']) && $_POST['access_mode'] === 'grant_required') ? 'grant_required' : 'free_open',
	), $categoryid);
	$perms = array();
	if (!empty($_POST['perm']) && is_array($_POST['perm']))
	{
		foreach ($_POST['perm'] as $ugid => $p)
		{
			$perms[(int)$ugid] = array('can_view' => !empty($p['can_view']), 'can_download' => !empty($p['can_download']));
		}
	}
	$repo->saveEntityPerms(0, $categoryid, $perms);
	print_cp_redirect(vbdl_admin_url('categories'), 1);
	exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$repo->deleteCategory((int)$_POST['categoryid']);
	print_cp_redirect(vbdl_admin_url('categories'), 1);
	exit;
}

if ($action === 'edit' || $action === 'add')
{
	$categoryid = (int)(isset($_REQUEST['categoryid']) ? $_REQUEST['categoryid'] : 0);
	$cat = $categoryid ? $repo->getCategory($categoryid) : array('title' => '', 'description' => '', 'displayorder' => 0, 'active' => 1, 'access_mode' => 'free_open');
	$groups = vbdl_list_usergroups();
	$perms = $categoryid ? $repo->getCategoryPerms($categoryid) : array();
	$mode = !empty($cat['access_mode']) ? $cat['access_mode'] : 'free_open';
	vbdl_admin_header($categoryid ? 'Edit Category' : 'Add Category', 'categories');
	echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Category</div><div class="vbdl-panel-b"><form method="post" action="admincp/vbdlmanager.php">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="categories" /><input type="hidden" name="action" value="save" /><input type="hidden" name="categoryid" value="' . $categoryid . '" />';
	echo '<div class="vbdl-form-row"><label>Title</label><input class="vbdl-input" type="text" name="title" value="' . vbdl_h($cat['title']) . '" required /></div>';
	echo '<div class="vbdl-form-row"><label>Description</label><textarea class="vbdl-textarea" name="description">' . vbdl_h($cat['description']) . '</textarea></div>';
	echo '<div class="vbdl-form-row"><label>Display order</label><input class="vbdl-input" type="number" name="displayorder" value="' . (int)$cat['displayorder'] . '" /></div>';
	echo '<div class="vbdl-form-row"><label>Active</label><label><input type="checkbox" name="active" value="1"' . (!empty($cat['active']) ? ' checked' : '') . ' /> Enabled</label></div>';
	echo '<div class="vbdl-form-row"><label>Access mode</label><select class="vbdl-select" name="access_mode">';
	echo '<option value="free_open"' . ($mode !== 'grant_required' ? ' selected' : '') . '>Free / open (matrix + VIP rules)</option>';
	echo '<option value="grant_required"' . ($mode === 'grant_required' ? ' selected' : '') . '>Grant required (admin must grant — use for VIP SeDiv category)</option>';
	echo '</select><p class="vbdl-muted">Grant required: even VIP SeDiv members cannot download until Access Grants assigns them.</p></div>';
	echo '<div class="vbdl-form-row"><label>Default access matrix (free_open only)</label><div>';
	foreach ($groups as $g)
	{
		$ug = (int)$g['usergroupid'];
		$p = isset($perms[$ug]) ? $perms[$ug] : array();
		echo '<div><strong>' . vbdl_h($g['title']) . '</strong> ';
		echo '<label><input type="checkbox" name="perm[' . $ug . '][can_view]" value="1"' . (!empty($p['can_view']) ? ' checked' : '') . ' /> View</label> ';
		echo '<label><input type="checkbox" name="perm[' . $ug . '][can_download]" value="1"' . (!empty($p['can_download']) ? ' checked' : '') . ' /> Download</label></div>';
	}
	echo '</div></div><div class="vbdl-actions"><button class="vbdl-btn" type="submit">Save</button><a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('categories')) . '">Back</a></div></form></div></div>';
	vbdl_admin_footer();
	return;
}

$cats = $repo->listCategories();
vbdl_admin_header('Categories', 'categories');
echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Categories</div><div class="vbdl-panel-b">';
echo '<div class="vbdl-actions" style="margin-top:0"><a class="vbdl-btn" href="' . vbdl_h(vbdl_admin_url('categories', 'action=add')) . '">Add category</a></div>';
echo '<table class="vbdl-table" style="margin-top:14px"><tr><th>ID</th><th>Title</th><th>Mode</th><th>Order</th><th>Active</th><th></th></tr>';
if (empty($cats))
{
	echo '<tr><td colspan="6" class="vbdl-muted">No categories yet.</td></tr>';
}
foreach ($cats as $c)
{
	$mode = !empty($c['access_mode']) ? $c['access_mode'] : 'free_open';
	echo '<tr><td>' . (int)$c['categoryid'] . '</td><td>' . vbdl_h($c['title']) . '</td><td>' . vbdl_h($mode) . '</td><td>' . (int)$c['displayorder'] . '</td>';
	echo '<td>' . (!empty($c['active']) ? '<span class="vbdl-chip ok">Yes</span>' : '<span class="vbdl-chip no">No</span>') . '</td>';
	echo '<td><a href="' . vbdl_h(vbdl_admin_url('categories', 'action=edit&categoryid=' . (int)$c['categoryid'])) . '">Edit</a> ';
	echo '<form style="display:inline" method="post" action="admincp/vbdlmanager.php" onsubmit="return confirm(\'Delete category?\');">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="categories" /><input type="hidden" name="action" value="delete" /><input type="hidden" name="categoryid" value="' . (int)$c['categoryid'] . '" /><button class="vbdl-btn danger" type="submit">Delete</button></form></td></tr>';
}
echo '</table></div></div>';
vbdl_admin_footer();
