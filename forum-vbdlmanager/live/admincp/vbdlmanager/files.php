<?php
/**
 * Download Manager - Files admin
 */
define('THIS_SCRIPT', 'vbdl_admin_files');
chdir(dirname(__FILE__) . '/..');
require_once './global.php';
require_once './vbdlmanager/_init.php';

vbdl_admin_init();
vbdl_admin_require();

$repo = vbdl_Bootstrap::$repo;
$service = vbdl_Bootstrap::$service;
$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
}

if ($do === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$fileid = (int)(isset($_POST['fileid']) ? $_POST['fileid'] : 0);
	$meta = array(
		'title' => isset($_POST['title']) ? $_POST['title'] : '',
		'description' => isset($_POST['description']) ? $_POST['description'] : '',
		'categoryid' => (int)(isset($_POST['categoryid']) ? $_POST['categoryid'] : 0),
		'storageid' => (int)(isset($_POST['storageid']) ? $_POST['storageid'] : 0),
		'active' => !empty($_POST['active']) ? 1 : 0,
		'inherit_category_perms' => !empty($_POST['inherit_category_perms']) ? 1 : 0,
	);

	$perms = array();
	if (empty($meta['inherit_category_perms']) && !empty($_POST['perm']) && is_array($_POST['perm']))
	{
		foreach ($_POST['perm'] as $ugid => $p)
		{
			$perms[(int)$ugid] = array(
				'can_view' => !empty($p['can_view']),
				'can_download' => !empty($p['can_download']),
			);
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
			if (!$storage)
			{
				print_cp_message('No storage profile configured.');
				exit;
			}
			require_once dirname(__FILE__) . '/../../core/packages/vbdlmanager/library/Storage/StorageFactory.php';
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
				$oldDriver = vbdl_StorageFactory::fromRow($oldStorage);
				@$oldDriver->delete($existing['storage_key']);
			}
			$meta['filename'] = $original;
			$meta['storage_key'] = $key;
			$meta['mime'] = $mime;
			$meta['filesize'] = $size;
			$meta['storageid'] = $storageid;
		}

		$repo->saveFile($meta, $fileid);
		if (empty($meta['inherit_category_perms']))
		{
			$repo->saveEntityPerms($fileid, 0, $perms);
		}
		else
		{
			$repo->saveEntityPerms($fileid, 0, array());
		}
		print_cp_redirect('vbdlmanager/files.php?do=edit&fileid=' . $fileid, 1);
		exit;
	}

	$result = $service->uploadFile($vbulletin->userinfo, $meta, isset($_FILES['upload']) ? $_FILES['upload'] : array(), $perms);
	if (!$result['ok'])
	{
		print_cp_message(vbdl_h($result['message']));
		exit;
	}
	print_cp_redirect('vbdlmanager/files.php?do=edit&fileid=' . (int)$result['fileid'], 1);
	exit;
}

if ($do === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$fileid = (int)(isset($_POST['fileid']) ? $_POST['fileid'] : 0);
	$service->deleteFileFully($fileid);
	print_cp_redirect('vbdlmanager/files.php', 1);
	exit;
}

print_cp_header('Download Manager - Files');
vbdl_admin_nav();

if ($do === 'edit' || $do === 'add')
{
	$fileid = (int)(isset($_REQUEST['fileid']) ? $_REQUEST['fileid'] : 0);
	$file = $fileid ? $repo->getFile($fileid) : array(
		'title' => '', 'description' => '', 'categoryid' => 0, 'storageid' => 0,
		'active' => 1, 'inherit_category_perms' => 0, 'filename' => '',
	);
	$categories = $repo->listCategories();
	$storages = $repo->listStorage();
	$groups = vbdl_list_usergroups();
	$filePerms = $fileid ? $repo->getFilePerms($fileid) : array();

	echo '<h2>' . ($fileid ? 'Edit file' : 'Add file') . '</h2>';
	echo '<form method="post" enctype="multipart/form-data" action="vbdlmanager/files.php" id="vbdl-file-form">';
	vbdl_request_token_field();
	echo '<input type="hidden" name="do" value="save" />';
	echo '<input type="hidden" name="fileid" value="' . (int)$fileid . '" />';
	echo '<table class="tborder" cellpadding="4" cellspacing="0" border="0" width="100%">';
	echo '<tr><td class="alt1">Title</td><td class="alt1"><input type="text" name="title" size="60" value="' . vbdl_h($file['title']) . '" /></td></tr>';
	echo '<tr><td class="alt2">Description</td><td class="alt2"><textarea name="description" rows="5" cols="60">' . vbdl_h($file['description']) . '</textarea></td></tr>';
	echo '<tr><td class="alt1">Category</td><td class="alt1"><select name="categoryid"><option value="0">— None —</option>';
	foreach ($categories as $c)
	{
		$sel = ((int)$file['categoryid'] === (int)$c['categoryid']) ? ' selected' : '';
		echo '<option value="' . (int)$c['categoryid'] . '"' . $sel . '>' . vbdl_h($c['title']) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><td class="alt2">Storage profile</td><td class="alt2"><select name="storageid">';
	foreach ($storages as $s)
	{
		$sel = ((int)$file['storageid'] === (int)$s['storageid'] || (!$fileid && !empty($s['is_default']))) ? ' selected' : '';
		echo '<option value="' . (int)$s['storageid'] . '"' . $sel . '>' . vbdl_h($s['title']) . ' (' . vbdl_h($s['storage_type']) . ')</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><td class="alt1">Upload file' . ($fileid ? ' (optional replace)' : '') . '</td><td class="alt1"><input type="file" name="upload" />';
	if ($fileid)
	{
		echo '<div>Current: ' . vbdl_h($file['filename']) . ' (' . vbdl_h($service->formatBytes($file['filesize'])) . ')</div>';
		echo '<div>BBCode: <code>[download=' . (int)$fileid . ']' . vbdl_h($file['title']) . '[/download]</code></div>';
	}
	echo '</td></tr>';
	echo '<tr><td class="alt2">Active</td><td class="alt2"><input type="checkbox" name="active" value="1"' . (!empty($file['active']) ? ' checked' : '') . ' /></td></tr>';
	$inheritOn = !empty($file['inherit_category_perms']);
	echo '<tr><td class="alt1">Inherit category permissions</td><td class="alt1"><input type="checkbox" name="inherit_category_perms" id="vbdl-inherit-perms" value="1"' . ($inheritOn ? ' checked' : '') . ' />';
	echo '<div id="vbdl-inherit-notice" style="margin-top:6px;color:#b45309;' . ($inheritOn ? '' : 'display:none;') . '">Per-file access is ignored while Inherit is enabled.</div></td></tr>';
	echo '<tr><td class="thead" colspan="2">Per-file usergroup access (saved when inherit is off; checking a box turns Inherit off)</td></tr>';
	foreach ($groups as $g)
	{
		$ug = (int)$g['usergroupid'];
		$p = isset($filePerms[$ug]) ? $filePerms[$ug] : array('can_view' => 0, 'can_download' => 0);
		echo '<tr><td class="alt1">' . vbdl_h($g['title']) . '</td><td class="alt1">';
		echo '<label><input type="checkbox" class="vbdl-file-perm" name="perm[' . $ug . '][can_view]" value="1"' . (!empty($p['can_view']) ? ' checked' : '') . ' /> View</label> ';
		echo '<label><input type="checkbox" class="vbdl-file-perm" name="perm[' . $ug . '][can_download]" value="1"' . (!empty($p['can_download']) ? ' checked' : '') . ' /> Download</label>';
		echo '</td></tr>';
	}
	echo '<tr><td class="tfoot" colspan="2"><input type="submit" value="Save" /></td></tr>';
	echo '</table></form>';
	echo '<script>(function(){var i=document.getElementById("vbdl-inherit-perms"),n=document.getElementById("vbdl-inherit-notice");if(!i)return;function sync(){if(n)n.style.display=i.checked?"":"none";}i.addEventListener("change",sync);document.querySelectorAll(".vbdl-file-perm").forEach(function(cb){cb.addEventListener("change",function(){if(cb.checked){i.checked=false;sync();}});});sync();})();</script>';

	if ($fileid)
	{
		echo '<br /><form method="post" action="vbdlmanager/files.php" onsubmit="return confirm(\'Delete this file?\');">';
		vbdl_request_token_field();
		echo '<input type="hidden" name="do" value="delete" /><input type="hidden" name="fileid" value="' . $fileid . '" />';
		echo '<input type="submit" value="Delete file" />';
		echo '</form>';
	}
}
else
{
	$q = isset($_GET['q']) ? $_GET['q'] : '';
	$files = $repo->listFiles(array('q' => $q, 'limit' => 100));
	echo '<h2>Files</h2>';
	echo '<p><a href="vbdlmanager/files.php?do=add">Add file</a></p>';
	echo '<form method="get" action="vbdlmanager/files.php"><input type="text" name="q" value="' . vbdl_h($q) . '" placeholder="Search..." /> <input type="submit" value="Search" /></form>';
	echo '<br /><table class="tborder" cellpadding="4" cellspacing="0" border="0" width="100%">';
	echo '<tr><td class="thead">ID</td><td class="thead">Title</td><td class="thead">Category</td><td class="thead">Storage</td><td class="thead">Size</td><td class="thead">Downloads</td><td class="thead">Active</td><td class="thead">Actions</td></tr>';
	if (empty($files))
	{
		echo '<tr><td class="alt1" colspan="8">No files found.</td></tr>';
	}
	foreach ($files as $i => $f)
	{
		$class = ($i % 2) ? 'alt2' : 'alt1';
		echo '<tr>';
		echo '<td class="' . $class . '">' . (int)$f['fileid'] . '</td>';
		echo '<td class="' . $class . '">' . vbdl_h($f['title']) . '</td>';
		echo '<td class="' . $class . '">' . vbdl_h($f['category_title']) . '</td>';
		echo '<td class="' . $class . '">' . vbdl_h($f['storage_title']) . ' (' . vbdl_h($f['storage_type']) . ')</td>';
		echo '<td class="' . $class . '">' . vbdl_h($service->formatBytes($f['filesize'])) . '</td>';
		echo '<td class="' . $class . '">' . (int)$f['downloads_count'] . '</td>';
		echo '<td class="' . $class . '">' . (!empty($f['active']) ? 'Yes' : 'No') . '</td>';
		echo '<td class="' . $class . '"><a href="vbdlmanager/files.php?do=edit&fileid=' . (int)$f['fileid'] . '">Edit</a></td>';
		echo '</tr>';
	}
	echo '</table>';
}

print_cp_footer();
