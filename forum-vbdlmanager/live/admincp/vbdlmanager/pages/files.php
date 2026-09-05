<?php
$repo = vbdl_Bootstrap::$repo;
$service = vbdl_Bootstrap::$service;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$fileid = (int)(isset($_POST['fileid']) ? $_POST['fileid'] : 0);
	$meta = array(
		'title' => isset($_POST['title']) ? $_POST['title'] : '',
		'description' => isset($_POST['description']) ? $_POST['description'] : '',
		'categoryid' => (int)(isset($_POST['categoryid']) ? $_POST['categoryid'] : 0),
		'storageid' => (int)(isset($_POST['storageid']) ? $_POST['storageid'] : 0),
		'active' => !empty($_POST['active']) ? 1 : 0,
		'inherit_category_perms' => !empty($_POST['inherit_category_perms']) ? 1 : 0,
		'access_type' => (isset($_POST['access_type']) && $_POST['access_type'] === 'paid') ? 'paid' : 'free',
	);
	$perms = array();
	if (empty($meta['inherit_category_perms']) && !empty($_POST['perm']) && is_array($_POST['perm']))
	{
		foreach ($_POST['perm'] as $ugid => $p)
		{
			$perms[(int)$ugid] = array('can_view' => !empty($p['can_view']), 'can_download' => !empty($p['can_download']));
		}
	}

	if ($fileid > 0)
	{
		$existing = $repo->getFile($fileid);
		if (!$existing)
		{
			print_cp_message('File not found.');
			exit;
		}
		if (!empty($_FILES['upload']['tmp_name']) && is_uploaded_file($_FILES['upload']['tmp_name']))
		{
			$original = $_FILES['upload']['name'];
			if (!$service->allowedExtension($original))
			{
				print_cp_message('File extension is not allowed.');
				exit;
			}
			$size = (int)$_FILES['upload']['size'];
			if ($size > $service->maxUploadBytes())
			{
				print_cp_message('File exceeds maximum upload size.');
				exit;
			}
			$storageid = !empty($meta['storageid']) ? (int)$meta['storageid'] : (int)$existing['storageid'];
			$storage = $repo->getStorage($storageid);
			require_once vbdl_admin_paths()['package'] . '/library/Storage/StorageFactory.php';
			$driver = vbdl_StorageFactory::fromRow($storage);
			$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
			$key = date('Y/m/') . bin2hex(random_bytes(8)) . ($ext ? '.' . $ext : '');
			$mime = !empty($_FILES['upload']['type']) ? $_FILES['upload']['type'] : 'application/octet-stream';
			if (!$driver->put($key, $_FILES['upload']['tmp_name'], $mime))
			{
				print_cp_message('Failed to store replacement file.');
				exit;
			}
			$oldStorage = $repo->getStorage((int)$existing['storageid']);
			if ($oldStorage && $existing['storage_key'] !== '')
			{
				@$oldDriver = vbdl_StorageFactory::fromRow($oldStorage);
				@$oldDriver->delete($existing['storage_key']);
			}
			$meta['filename'] = $original;
			$meta['storage_key'] = $key;
			$meta['mime'] = $mime;
			$meta['filesize'] = $size;
			$meta['storageid'] = $storageid;
		}
		$repo->saveFile($meta, $fileid);
		$repo->saveEntityPerms($fileid, 0, empty($meta['inherit_category_perms']) ? $perms : array());
		print_cp_redirect(vbdl_admin_url('files', 'action=edit&fileid=' . $fileid), 1);
		exit;
	}

	$result = $service->uploadFile($vbulletin->userinfo, $meta, isset($_FILES['upload']) ? $_FILES['upload'] : array(), $perms);
	if (!$result['ok'])
	{
		print_cp_message(vbdl_h($result['message']));
		exit;
	}
	print_cp_redirect(vbdl_admin_url('files', 'action=edit&fileid=' . (int)$result['fileid']), 1);
	exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$service->deleteFileFully((int)$_POST['fileid']);
	print_cp_redirect(vbdl_admin_url('files'), 1);
	exit;
}

if ($action === 'edit' || $action === 'add')
{
	$fileid = (int)(isset($_REQUEST['fileid']) ? $_REQUEST['fileid'] : 0);
	// New files default inherit OFF so per-file checkboxes actually persist on first save.
	$file = $fileid ? $repo->getFile($fileid) : array('title' => '', 'description' => '', 'categoryid' => 0, 'storageid' => 0, 'active' => 1, 'inherit_category_perms' => 0, 'access_type' => 'free', 'filename' => '', 'filesize' => 0);
	if (empty($file['access_type']))
	{
		$file['access_type'] = 'free';
	}
	$categories = $repo->listCategories();
	$storages = $repo->listStorage();
	$groups = vbdl_list_usergroups();
	$filePerms = $fileid ? $repo->getFilePerms($fileid) : array();
	$inheritOn = !empty($file['inherit_category_perms']);

	vbdl_admin_header($fileid ? 'Edit File' : 'Add File', 'files');
	echo '<div class="vbdl-panel"><div class="vbdl-panel-h">' . ($fileid ? 'Edit file #' . $fileid : 'Upload new file') . '</div><div class="vbdl-panel-b">';
	echo '<form method="post" enctype="multipart/form-data" action="admincp/vbdlmanager.php" id="vbdl-file-form">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="files" /><input type="hidden" name="action" value="save" /><input type="hidden" name="fileid" value="' . $fileid . '" />';
	echo '<div class="vbdl-form-row"><label>Title</label><input class="vbdl-input" type="text" name="title" value="' . vbdl_h($file['title']) . '" required /></div>';
	echo '<div class="vbdl-form-row"><label>Description</label><textarea class="vbdl-textarea" name="description">' . vbdl_h($file['description']) . '</textarea></div>';
	echo '<div class="vbdl-form-row"><label>Category</label><select class="vbdl-select" name="categoryid"><option value="0">None</option>';
	foreach ($categories as $c)
	{
		$sel = ((int)$file['categoryid'] === (int)$c['categoryid']) ? ' selected' : '';
		echo '<option value="' . (int)$c['categoryid'] . '"' . $sel . '>' . vbdl_h($c['title']) . '</option>';
	}
	echo '</select></div>';
	echo '<div class="vbdl-form-row"><label>Storage profile</label><select class="vbdl-select" name="storageid">';
	foreach ($storages as $s)
	{
		$sel = ((int)$file['storageid'] === (int)$s['storageid'] || (!$fileid && !empty($s['is_default']))) ? ' selected' : '';
		echo '<option value="' . (int)$s['storageid'] . '"' . $sel . '>' . vbdl_h($s['title']) . ' (' . vbdl_h($s['storage_type']) . ')</option>';
	}
	echo '</select></div>';
	echo '<div class="vbdl-form-row"><label>File' . ($fileid ? ' (optional replace)' : '') . '</label><div><input type="file" name="upload" ' . ($fileid ? '' : 'required ') . '/>';
	if ($fileid)
	{
		echo '<div class="vbdl-muted">Current: ' . vbdl_h($file['filename']) . ' (' . vbdl_h($service->formatBytes($file['filesize'])) . ')</div>';
		echo '<div class="vbdl-muted">BBCode: <code>[download=' . $fileid . ']' . vbdl_h($file['title']) . '[/download]</code></div>';
	}
	echo '</div></div>';
	$accessType = (!empty($file['access_type']) && $file['access_type'] === 'paid') ? 'paid' : 'free';
	echo '<div class="vbdl-form-row"><label>Access type</label><div>';
	echo '<select class="vbdl-select" name="access_type">';
	echo '<option value="free"' . ($accessType === 'free' ? ' selected' : '') . '>Free — allowed usergroups can download</option>';
	echo '<option value="paid"' . ($accessType === 'paid' ? ' selected' : '') . '>Paid / VIP — only VIP usergroups can download; others see contact-admin message</option>';
	echo '</select>';
	echo '<p class="vbdl-muted">Configure VIP groups and the English contact message under Download Manager → Settings.</p>';
	echo '</div></div>';
	echo '<div class="vbdl-form-row"><label>Flags</label><div>';
	echo '<label><input type="checkbox" name="active" value="1"' . (!empty($file['active']) ? ' checked' : '') . ' /> Active</label><br />';
	echo '<label><input type="checkbox" name="inherit_category_perms" id="vbdl-inherit-perms" value="1"' . ($inheritOn ? ' checked' : '') . ' /> Inherit category permissions</label>';
	echo '<p class="vbdl-muted" id="vbdl-inherit-notice" style="margin:8px 0 0;color:#b45309;' . ($inheritOn ? '' : 'display:none;') . '">Per-file access is ignored while Inherit is enabled.</p>';
	echo '</div></div>';
	echo '<div class="vbdl-form-row"><label>Per-file access</label><div id="vbdl-file-perms">';
	foreach ($groups as $g)
	{
		$ug = (int)$g['usergroupid'];
		$p = isset($filePerms[$ug]) ? $filePerms[$ug] : array();
		echo '<div><strong>' . vbdl_h($g['title']) . '</strong> ';
		echo '<label><input type="checkbox" class="vbdl-file-perm" name="perm[' . $ug . '][can_view]" value="1"' . (!empty($p['can_view']) ? ' checked' : '') . ' /> View</label> ';
		echo '<label><input type="checkbox" class="vbdl-file-perm" name="perm[' . $ug . '][can_download]" value="1"' . (!empty($p['can_download']) ? ' checked' : '') . ' /> Download</label></div>';
	}
	echo '<p class="vbdl-muted">Saved only when Inherit is off. Checking any box below turns Inherit off automatically.</p></div></div>';
	echo '<div class="vbdl-actions"><button class="vbdl-btn" type="submit">Save</button><a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('files')) . '">Back</a></div>';
	echo '</form>';
	echo '<script>(function(){var i=document.getElementById("vbdl-inherit-perms"),n=document.getElementById("vbdl-inherit-notice"),box=document.getElementById("vbdl-file-perms");if(!i||!box)return;function sync(){if(n)n.style.display=i.checked?"block":"none";}i.addEventListener("change",sync);box.addEventListener("change",function(e){var t=e.target;if(t&&t.classList&&t.classList.contains("vbdl-file-perm")&&t.checked){i.checked=false;sync();}});sync();})();</script>';
	if ($fileid)
	{
		echo '<form method="post" action="admincp/vbdlmanager.php" onsubmit="return confirm(\'Delete this file?\');" style="margin-top:16px">';
		vbdl_request_token_field();
		echo '<input type="hidden" name="do" value="files" /><input type="hidden" name="action" value="delete" /><input type="hidden" name="fileid" value="' . $fileid . '" />';
		echo '<button class="vbdl-btn danger" type="submit">Delete file</button></form>';
	}
	echo '</div></div>';
	vbdl_admin_footer();
	return;
}

$q = isset($_GET['q']) ? $_GET['q'] : '';
$filterCategoryId = isset($_GET['categoryid']) ? (int)$_GET['categoryid'] : 0;
$categories = $repo->listCategories(false);
$countsByCat = $repo->countFilesByCategory(false);
$listFilters = array('q' => $q, 'limit' => 200);
if ($filterCategoryId > 0)
{
	$listFilters['categoryid'] = $filterCategoryId;
}
$files = $repo->listFiles($listFilters);
vbdl_admin_header('Files', 'files');
echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Library by category</div><div class="vbdl-panel-b">';
echo '<div class="vbdl-actions" style="margin-top:0;flex-wrap:wrap"><a class="vbdl-btn" href="' . vbdl_h(vbdl_admin_url('files', 'action=add')) . '">Add file</a>';
echo '<form method="get" action="admincp/vbdlmanager.php" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap"><input type="hidden" name="do" value="files" />';
echo '<select class="vbdl-select" name="categoryid"><option value="0">All categories</option>';
foreach ($categories as $c)
{
	$cid = (int)$c['categoryid'];
	$cnt = isset($countsByCat[$cid]) ? (int)$countsByCat[$cid] : 0;
	$sel = ($filterCategoryId === $cid) ? ' selected' : '';
	echo '<option value="' . $cid . '"' . $sel . '>' . vbdl_h($c['title']) . ' (' . $cnt . ')</option>';
}
echo '</select>';
echo '<input class="vbdl-input" style="max-width:240px" type="text" name="q" value="' . vbdl_h($q) . '" placeholder="Search..." /><button class="vbdl-btn secondary" type="submit">Filter</button></form></div>';
if ($filterCategoryId > 0)
{
	$ctitle = '';
	foreach ($categories as $c)
	{
		if ((int)$c['categoryid'] === $filterCategoryId) { $ctitle = $c['title']; break; }
	}
	echo '<p class="vbdl-muted">Showing files in category: <strong>' . vbdl_h($ctitle) . '</strong></p>';
}
echo '<table class="vbdl-table" style="margin-top:14px"><tr><th>ID</th><th>Title</th><th>Access</th><th>Category</th><th>Storage</th><th>Size</th><th>Downloads</th><th>Download URL</th><th>Active</th><th></th></tr>';
if (empty($files))
{
	echo '<tr><td colspan="10" class="vbdl-muted">No files found.</td></tr>';
}
foreach ($files as $f)
{
	$isPaid = (!empty($f['access_type']) && $f['access_type'] === 'paid');
	$dlUrl = method_exists($service, 'downloadUrl') ? $service->downloadUrl((int)$f['fileid'], true) : ('https://forum.hdd-land.com/vbdlmanager/download.php?fileid=' . (int)$f['fileid']);
	$dlCount = isset($f['downloads_count']) ? (int)$f['downloads_count'] : 0;
	echo '<tr>';
	echo '<td>' . (int)$f['fileid'] . '</td>';
	echo '<td>' . vbdl_h($f['title']) . '</td>';
	echo '<td>' . ($isPaid ? '<span class="vbdl-chip no">Paid / VIP</span>' : '<span class="vbdl-chip ok">Free</span>') . '</td>';
	echo '<td>' . vbdl_h(isset($f['category_title']) ? $f['category_title'] : '') . '</td>';
	echo '<td>' . vbdl_h(isset($f['storage_title']) ? $f['storage_title'] : '') . ' <span class="vbdl-chip">' . vbdl_h(isset($f['storage_type']) ? $f['storage_type'] : '') . '</span></td>';
	echo '<td>' . vbdl_h($service->formatBytes($f['filesize'])) . '</td>';
	echo '<td>' . $dlCount . '</td>';
	echo '<td style="max-width:240px;word-break:break-all"><code>' . vbdl_h($dlUrl) . '</code></td>';
	echo '<td>' . (!empty($f['active']) ? '<span class="vbdl-chip ok">Yes</span>' : '<span class="vbdl-chip no">No</span>') . '</td>';
	echo '<td><a href="' . vbdl_h(vbdl_admin_url('files', 'action=edit&fileid=' . (int)$f['fileid'])) . '">Edit</a></td>';
	echo '</tr>';
}
echo '</table></div></div>';
vbdl_admin_footer();
