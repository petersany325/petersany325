<?php
/**
 * Database repository for Download Manager.
 */

class vbdl_Repository
{
	/** @var vB_Database|object */
	protected $db;

	/** @var string */
	protected $prefix;

	public function __construct($db, $tablePrefix = '')
	{
		$this->db = $db;
		$this->prefix = (string)$tablePrefix;
	}

	public function t($name)
	{
		return $this->prefix . $name;
	}

	public function getSetting($name, $default = '')
	{
		$name = $this->db->escape_string($name);
		$row = $this->db->query_first("SELECT value FROM " . $this->t('vbdl_setting') . " WHERE varname = '{$name}'");
		return $row ? $row['value'] : $default;
	}

	public function setSetting($name, $value)
	{
		$nameEsc = $this->db->escape_string($name);
		$valueEsc = $this->db->escape_string((string)$value);
		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_setting') . " (varname, value)
			VALUES ('{$nameEsc}', '{$valueEsc}')
			ON DUPLICATE KEY UPDATE value = VALUES(value)
		");
	}

	public function getAllSettings()
	{
		$out = array();
		$res = $this->db->query_read("SELECT varname, value FROM " . $this->t('vbdl_setting'));
		while ($row = $this->db->fetch_array($res))
		{
			$out[$row['varname']] = $row['value'];
		}
		return $out;
	}

	public function getStorage($storageid)
	{
		$storageid = (int)$storageid;
		return $this->db->query_first("SELECT * FROM " . $this->t('vbdl_storage') . " WHERE storageid = {$storageid}");
	}

	public function getDefaultStorage()
	{
		$row = $this->db->query_first("SELECT * FROM " . $this->t('vbdl_storage') . " WHERE is_default = 1 AND active = 1 ORDER BY storageid ASC LIMIT 1");
		if ($row)
		{
			return $row;
		}
		return $this->db->query_first("SELECT * FROM " . $this->t('vbdl_storage') . " WHERE active = 1 ORDER BY storageid ASC LIMIT 1");
	}

	public function listStorage()
	{
		$out = array();
		$res = $this->db->query_read("SELECT * FROM " . $this->t('vbdl_storage') . " ORDER BY title ASC");
		while ($row = $this->db->fetch_array($res))
		{
			$out[] = $row;
		}
		return $out;
	}

	public function saveStorage(array $data, $storageid = 0)
	{
		$fields = array(
			'title' => $this->db->escape_string(isset($data['title']) ? $data['title'] : ''),
			'storage_type' => ($data['storage_type'] === 's3') ? 's3' : 'local',
			'is_default' => !empty($data['is_default']) ? 1 : 0,
			'local_path' => $this->db->escape_string(isset($data['local_path']) ? $data['local_path'] : ''),
			's3_endpoint' => $this->db->escape_string(isset($data['s3_endpoint']) ? $data['s3_endpoint'] : ''),
			's3_region' => $this->db->escape_string(isset($data['s3_region']) ? $data['s3_region'] : 'us-east-1'),
			's3_bucket' => $this->db->escape_string(isset($data['s3_bucket']) ? $data['s3_bucket'] : ''),
			's3_access_key' => $this->db->escape_string(isset($data['s3_access_key']) ? $data['s3_access_key'] : ''),
			's3_secret_key' => $this->db->escape_string(isset($data['s3_secret_key']) ? $data['s3_secret_key'] : ''),
			's3_path_style' => !empty($data['s3_path_style']) ? 1 : 0,
			's3_prefix' => $this->db->escape_string(isset($data['s3_prefix']) ? $data['s3_prefix'] : ''),
			'active' => !isset($data['active']) || $data['active'] ? 1 : 0,
			'dateline' => TIMENOW,
		);

		if (!empty($fields['is_default']))
		{
			$this->db->query_write("UPDATE " . $this->t('vbdl_storage') . " SET is_default = 0");
		}

		if ($storageid > 0)
		{
			$this->db->query_write("
				UPDATE " . $this->t('vbdl_storage') . " SET
					title = '{$fields['title']}',
					storage_type = '{$fields['storage_type']}',
					is_default = {$fields['is_default']},
					local_path = '{$fields['local_path']}',
					s3_endpoint = '{$fields['s3_endpoint']}',
					s3_region = '{$fields['s3_region']}',
					s3_bucket = '{$fields['s3_bucket']}',
					s3_access_key = '{$fields['s3_access_key']}',
					s3_secret_key = '{$fields['s3_secret_key']}',
					s3_path_style = {$fields['s3_path_style']},
					s3_prefix = '{$fields['s3_prefix']}',
					active = {$fields['active']}
				WHERE storageid = " . (int)$storageid
			);
			return (int)$storageid;
		}

		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_storage') . "
			(title, storage_type, is_default, local_path, s3_endpoint, s3_region, s3_bucket, s3_access_key, s3_secret_key, s3_path_style, s3_prefix, active, dateline)
			VALUES (
				'{$fields['title']}', '{$fields['storage_type']}', {$fields['is_default']}, '{$fields['local_path']}',
				'{$fields['s3_endpoint']}', '{$fields['s3_region']}', '{$fields['s3_bucket']}', '{$fields['s3_access_key']}',
				'{$fields['s3_secret_key']}', {$fields['s3_path_style']}, '{$fields['s3_prefix']}', {$fields['active']}, {$fields['dateline']}
			)
		");
		return (int)$this->db->insert_id();
	}

	public function deleteStorage($storageid)
	{
		$storageid = (int)$storageid;
		$count = $this->db->query_first("SELECT COUNT(*) AS c FROM " . $this->t('vbdl_file') . " WHERE storageid = {$storageid}");
		if ($count && (int)$count['c'] > 0)
		{
			return false;
		}
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_storage') . " WHERE storageid = {$storageid}");
		return true;
	}

	public function listCategories($activeOnly = false)
	{
		$where = $activeOnly ? ' WHERE active = 1' : '';
		$out = array();
		$res = $this->db->query_read("SELECT * FROM " . $this->t('vbdl_category') . $where . " ORDER BY displayorder ASC, title ASC");
		while ($row = $this->db->fetch_array($res))
		{
			$out[] = $row;
		}
		return $out;
	}

	public function getCategory($categoryid)
	{
		$categoryid = (int)$categoryid;
		return $this->db->query_first("SELECT * FROM " . $this->t('vbdl_category') . " WHERE categoryid = {$categoryid}");
	}

	public function saveCategory(array $data, $categoryid = 0)
	{
		$title = $this->db->escape_string(isset($data['title']) ? $data['title'] : '');
		$description = $this->db->escape_string(isset($data['description']) ? $data['description'] : '');
		$displayorder = (int)(isset($data['displayorder']) ? $data['displayorder'] : 0);
		$active = !isset($data['active']) || $data['active'] ? 1 : 0;
		$accessMode = (!empty($data['access_mode']) && $data['access_mode'] === 'grant_required') ? 'grant_required' : 'free_open';
		$dateline = TIMENOW;

		if ($categoryid > 0)
		{
			$this->db->query_write("
				UPDATE " . $this->t('vbdl_category') . " SET
					title = '{$title}', description = '{$description}', displayorder = {$displayorder},
					active = {$active}, access_mode = '{$accessMode}'
				WHERE categoryid = " . (int)$categoryid
			);
			return (int)$categoryid;
		}

		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_category') . " (title, description, displayorder, active, access_mode, dateline)
			VALUES ('{$title}', '{$description}', {$displayorder}, {$active}, '{$accessMode}', {$dateline})
		");
		return (int)$this->db->insert_id();
	}

	public function deleteCategory($categoryid)
	{
		$categoryid = (int)$categoryid;
		$this->db->query_write("UPDATE " . $this->t('vbdl_file') . " SET categoryid = 0 WHERE categoryid = {$categoryid}");
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_file_usergroup') . " WHERE categoryid = {$categoryid} AND fileid = 0");
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_category') . " WHERE categoryid = {$categoryid}");
	}

	public function getFile($fileid)
	{
		$fileid = (int)$fileid;
		return $this->db->query_first("SELECT * FROM " . $this->t('vbdl_file') . " WHERE fileid = {$fileid}");
	}

	public function listFiles($filters = array())
	{
		$where = array('1=1');
		if (!empty($filters['active_only']))
		{
			$where[] = 'f.active = 1';
		}
		if (array_key_exists('categoryid', $filters) && $filters['categoryid'] !== '' && $filters['categoryid'] !== null)
		{
			$where[] = 'f.categoryid = ' . (int)$filters['categoryid'];
		}
		if (!empty($filters['q']))
		{
			$q = $this->db->escape_string($filters['q']);
			$where[] = "(f.title LIKE '%{$q}%' OR f.filename LIKE '%{$q}%')";
		}
		$limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
		$offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
		$sql = "
			SELECT f.*, c.title AS category_title, s.title AS storage_title, s.storage_type
			FROM " . $this->t('vbdl_file') . " AS f
			LEFT JOIN " . $this->t('vbdl_category') . " AS c ON c.categoryid = f.categoryid
			LEFT JOIN " . $this->t('vbdl_storage') . " AS s ON s.storageid = f.storageid
			WHERE " . implode(' AND ', $where) . "
			ORDER BY f.dateline DESC
			LIMIT {$offset}, {$limit}
		";
		$out = array();
		$res = $this->db->query_read($sql);
		while ($row = $this->db->fetch_array($res))
		{
			$out[] = $row;
		}
		return $out;
	}

	public function countFiles($filters = array())
	{
		$where = array('1=1');
		if (!empty($filters['active_only']))
		{
			$where[] = 'active = 1';
		}
		if (array_key_exists('categoryid', $filters) && $filters['categoryid'] !== '' && $filters['categoryid'] !== null)
		{
			$where[] = 'categoryid = ' . (int)$filters['categoryid'];
		}
		$row = $this->db->query_first("SELECT COUNT(*) AS c FROM " . $this->t('vbdl_file') . " WHERE " . implode(' AND ', $where));
		return $row ? (int)$row['c'] : 0;
	}

	/**
	 * @param bool $activeOnly
	 * @return array categoryid => count
	 */
	public function countFilesByCategory($activeOnly = true)
	{
		$where = $activeOnly ? ' WHERE active = 1' : '';
		$out = array();
		$res = $this->db->query_read("
			SELECT categoryid, COUNT(*) AS c
			FROM " . $this->t('vbdl_file') . $where . "
			GROUP BY categoryid
		");
		while ($row = $this->db->fetch_array($res))
		{
			$out[(int)$row['categoryid']] = (int)$row['c'];
		}
		return $out;
	}

	public function saveFile(array $data, $fileid = 0)
	{
		$title = $this->db->escape_string(isset($data['title']) ? $data['title'] : '');
		$description = $this->db->escape_string(isset($data['description']) ? $data['description'] : '');
		$filename = $this->db->escape_string(isset($data['filename']) ? $data['filename'] : '');
		$storage_key = $this->db->escape_string(isset($data['storage_key']) ? $data['storage_key'] : '');
		$mime = $this->db->escape_string(isset($data['mime']) ? $data['mime'] : 'application/octet-stream');
		$filesize = (int)(isset($data['filesize']) ? $data['filesize'] : 0);
		$categoryid = (int)(isset($data['categoryid']) ? $data['categoryid'] : 0);
		$storageid = (int)(isset($data['storageid']) ? $data['storageid'] : 0);
		$active = !isset($data['active']) || $data['active'] ? 1 : 0;
		$inherit = !isset($data['inherit_category_perms']) || $data['inherit_category_perms'] ? 1 : 0;
		$accessType = (!empty($data['access_type']) && $data['access_type'] === 'paid') ? 'paid' : 'free';
		$requireGrant = !empty($data['require_grant']) ? 1 : 0;
		$createdby = (int)(isset($data['createdby']) ? $data['createdby'] : 0);
		$now = TIMENOW;

		if ($fileid > 0)
		{
			$setFile = $filename !== '' ? ", filename = '{$filename}'" : '';
			$setKey = $storage_key !== '' ? ", storage_key = '{$storage_key}'" : '';
			$setMime = isset($data['mime']) ? ", mime = '{$mime}'" : '';
			$setSize = isset($data['filesize']) ? ", filesize = {$filesize}" : '';
			$setStorage = isset($data['storageid']) ? ", storageid = {$storageid}" : '';
			$setAccess = isset($data['access_type']) ? ", access_type = '{$accessType}'" : '';
			$setGrant = isset($data['require_grant']) ? ", require_grant = {$requireGrant}" : '';
			$this->db->query_write("
				UPDATE " . $this->t('vbdl_file') . " SET
					title = '{$title}',
					description = '{$description}',
					categoryid = {$categoryid},
					active = {$active},
					inherit_category_perms = {$inherit},
					lastupdate = {$now}
					{$setFile}{$setKey}{$setMime}{$setSize}{$setStorage}{$setAccess}{$setGrant}
				WHERE fileid = " . (int)$fileid
			);
			return (int)$fileid;
		}

		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_file') . "
			(categoryid, storageid, title, description, filename, storage_key, mime, filesize, downloads_count, active, inherit_category_perms, access_type, require_grant, createdby, dateline, lastupdate)
			VALUES (
				{$categoryid}, {$storageid}, '{$title}', '{$description}', '{$filename}', '{$storage_key}', '{$mime}',
				{$filesize}, 0, {$active}, {$inherit}, '{$accessType}', {$requireGrant}, {$createdby}, {$now}, {$now}
			)
		");
		return (int)$this->db->insert_id();
	}

	public function deleteFile($fileid)
	{
		$fileid = (int)$fileid;
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_file_usergroup') . " WHERE fileid = {$fileid}");
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_file') . " WHERE fileid = {$fileid}");
	}

	public function incrementDownloads($fileid)
	{
		$fileid = (int)$fileid;
		$this->db->query_write("UPDATE " . $this->t('vbdl_file') . " SET downloads_count = downloads_count + 1 WHERE fileid = {$fileid}");
	}

	public function getFilePerms($fileid)
	{
		$fileid = (int)$fileid;
		$out = array();
		$res = $this->db->query_read("SELECT * FROM " . $this->t('vbdl_file_usergroup') . " WHERE fileid = {$fileid}");
		while ($row = $this->db->fetch_array($res))
		{
			$out[(int)$row['usergroupid']] = $row;
		}
		return $out;
	}

	public function getCategoryPerms($categoryid)
	{
		$categoryid = (int)$categoryid;
		$out = array();
		$res = $this->db->query_read("SELECT * FROM " . $this->t('vbdl_file_usergroup') . " WHERE categoryid = {$categoryid} AND fileid = 0");
		while ($row = $this->db->fetch_array($res))
		{
			$out[(int)$row['usergroupid']] = $row;
		}
		return $out;
	}

	public function saveEntityPerms($fileid, $categoryid, array $perms)
	{
		$fileid = (int)$fileid;
		$categoryid = (int)$categoryid;
		if ($fileid > 0)
		{
			$this->db->query_write("DELETE FROM " . $this->t('vbdl_file_usergroup') . " WHERE fileid = {$fileid}");
		}
		else
		{
			$this->db->query_write("DELETE FROM " . $this->t('vbdl_file_usergroup') . " WHERE categoryid = {$categoryid} AND fileid = 0");
		}

		foreach ($perms as $ugid => $p)
		{
			$ugid = (int)$ugid;
			$can_view = !empty($p['can_view']) ? 1 : 0;
			$can_download = !empty($p['can_download']) ? 1 : 0;
			if (!$can_view && !$can_download)
			{
				continue;
			}
			$this->db->query_write("
				INSERT INTO " . $this->t('vbdl_file_usergroup') . "
				(fileid, categoryid, usergroupid, can_view, can_download)
				VALUES ({$fileid}, {$categoryid}, {$ugid}, {$can_view}, {$can_download})
			");
		}
	}

	public function getUsergroupPerms()
	{
		$out = array();
		$res = $this->db->query_read("SELECT * FROM " . $this->t('vbdl_usergroup_perm'));
		while ($row = $this->db->fetch_array($res))
		{
			$out[(int)$row['usergroupid']] = $row;
		}
		return $out;
	}

	public function saveUsergroupPerms(array $matrix)
	{
		foreach ($matrix as $ugid => $p)
		{
			$ugid = (int)$ugid;
			$can_view_section = !empty($p['can_view_section']) ? 1 : 0;
			$can_download = !empty($p['can_download']) ? 1 : 0;
			$can_upload = !empty($p['can_upload']) ? 1 : 0;
			$can_manage_own = !empty($p['can_manage_own']) ? 1 : 0;
			$admin_bypass = !empty($p['admin_bypass']) ? 1 : 0;
			$this->db->query_write("
				INSERT INTO " . $this->t('vbdl_usergroup_perm') . "
				(usergroupid, can_view_section, can_download, can_upload, can_manage_own, admin_bypass)
				VALUES ({$ugid}, {$can_view_section}, {$can_download}, {$can_upload}, {$can_manage_own}, {$admin_bypass})
				ON DUPLICATE KEY UPDATE
					can_view_section = VALUES(can_view_section),
					can_download = VALUES(can_download),
					can_upload = VALUES(can_upload),
					can_manage_own = VALUES(can_manage_own),
					admin_bypass = VALUES(admin_bypass)
			");
		}
	}

	public function addLog($fileid, $userid, $ip, $ua, $result, $message = '')
	{
		$fileid = (int)$fileid;
		$userid = (int)$userid;
		$ip = $this->db->escape_string(substr((string)$ip, 0, 45));
		$ua = $this->db->escape_string(substr((string)$ua, 0, 255));
		$result = in_array($result, array('ok', 'denied', 'error'), true) ? $result : 'error';
		$message = $this->db->escape_string(substr((string)$message, 0, 255));
		$now = defined('TIMENOW') ? TIMENOW : time();
		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_log') . "
			(fileid, userid, ipaddress, useragent, result, message, dateline)
			VALUES ({$fileid}, {$userid}, '{$ip}', '{$ua}', '{$result}', '{$message}', {$now})
		");
	}

	public function listLogs($filters = array(), $limit = 100, $offset = 0)
	{
		$where = array('1=1');
		if (!empty($filters['fileid']))
		{
			$where[] = 'l.fileid = ' . (int)$filters['fileid'];
		}
		if (!empty($filters['userid']))
		{
			$where[] = 'l.userid = ' . (int)$filters['userid'];
		}
		if (!empty($filters['result']))
		{
			$r = $this->db->escape_string($filters['result']);
			$where[] = "l.result = '{$r}'";
		}
		if (!empty($filters['ip']))
		{
			$ip = $this->db->escape_string($filters['ip']);
			$where[] = "l.ipaddress LIKE '%{$ip}%'";
		}
		if (!empty($filters['date_from']))
		{
			$where[] = 'l.dateline >= ' . (int)$filters['date_from'];
		}
		if (!empty($filters['date_to']))
		{
			$where[] = 'l.dateline <= ' . (int)$filters['date_to'];
		}
		$limit = (int)$limit;
		$offset = (int)$offset;
		$sql = "
			SELECT l.*, f.title AS file_title, u.username
			FROM " . $this->t('vbdl_log') . " AS l
			LEFT JOIN " . $this->t('vbdl_file') . " AS f ON f.fileid = l.fileid
			LEFT JOIN " . $this->t('user') . " AS u ON u.userid = l.userid
			WHERE " . implode(' AND ', $where) . "
			ORDER BY l.logid DESC
			LIMIT {$offset}, {$limit}
		";
		$out = array();
		$res = $this->db->query_read($sql);
		while ($row = $this->db->fetch_array($res))
		{
			$out[] = $row;
		}
		return $out;
	}

	public function dashboardStats()
	{
		$files = $this->db->query_first("SELECT COUNT(*) AS c, COALESCE(SUM(filesize),0) AS bytes, COALESCE(SUM(downloads_count),0) AS downloads FROM " . $this->t('vbdl_file'));
		$denied = $this->db->query_first("SELECT COUNT(*) AS c FROM " . $this->t('vbdl_log') . " WHERE result = 'denied' AND dateline > " . (time() - 86400));
		$top = array();
		$res = $this->db->query_read("SELECT fileid, title, downloads_count FROM " . $this->t('vbdl_file') . " ORDER BY downloads_count DESC LIMIT 10");
		while ($row = $this->db->fetch_array($res))
		{
			$top[] = $row;
		}
		$recentDenied = $this->listLogs(array('result' => 'denied'), 10, 0);
		return array(
			'file_count' => $files ? (int)$files['c'] : 0,
			'total_bytes' => $files ? (int)$files['bytes'] : 0,
			'total_downloads' => $files ? (int)$files['downloads'] : 0,
			'denied_24h' => $denied ? (int)$denied['c'] : 0,
			'top_files' => $top,
			'recent_denied' => $recentDenied,
		);
	}

	public function countDownloadsLastHour($userid, $ip)
	{
		$userid = (int)$userid;
		$ip = $this->db->escape_string($ip);
		$since = time() - 3600;
		$row = $this->db->query_first("
			SELECT COUNT(*) AS c FROM " . $this->t('vbdl_log') . "
			WHERE result = 'ok' AND dateline >= {$since}
			AND (userid = {$userid} OR ipaddress = '{$ip}')
		");
		return $row ? (int)$row['c'] : 0;
	}

	/* ===================== Access Grants ===================== */

	public function hasActiveGrant($targetType, $targetId, $userid, array $groupIds, $action = 'can_download')
	{
		$targetType = ($targetType === 'file') ? 'file' : 'category';
		$targetId = (int)$targetId;
		$userid = (int)$userid;
		$action = in_array($action, array('can_view', 'can_download', 'can_upload'), true) ? $action : 'can_download';
		$now = time();

		$or = array();
		if ($userid > 0)
		{
			$or[] = "(grantee_type = 'user' AND granteeid = {$userid})";
		}
		$gids = array();
		foreach ($groupIds as $g)
		{
			$g = (int)$g;
			if ($g > 0)
			{
				$gids[] = $g;
			}
		}
		if ($gids)
		{
			$or[] = "(grantee_type = 'usergroup' AND granteeid IN (" . implode(',', $gids) . "))";
		}
		if (!$or)
		{
			return false;
		}

		$row = $this->db->query_first("
			SELECT grantid FROM " . $this->t('vbdl_access_grant') . "
			WHERE target_type = '{$targetType}' AND targetid = {$targetId}
			  AND {$action} = 1
			  AND (expires = 0 OR expires > {$now})
			  AND (" . implode(' OR ', $or) . ")
			LIMIT 1
		");
		return !empty($row);
	}

	public function userHasAnyUploadGrant($userid, array $groupIds)
	{
		$userid = (int)$userid;
		$now = time();
		$or = array();
		if ($userid > 0)
		{
			$or[] = "(grantee_type = 'user' AND granteeid = {$userid})";
		}
		$gids = array();
		foreach ($groupIds as $g)
		{
			$g = (int)$g;
			if ($g > 0)
			{
				$gids[] = $g;
			}
		}
		if ($gids)
		{
			$or[] = "(grantee_type = 'usergroup' AND granteeid IN (" . implode(',', $gids) . "))";
		}
		if (!$or)
		{
			return false;
		}
		$row = $this->db->query_first("
			SELECT grantid FROM " . $this->t('vbdl_access_grant') . "
			WHERE can_upload = 1 AND (expires = 0 OR expires > {$now})
			  AND (" . implode(' OR ', $or) . ")
			LIMIT 1
		");
		return !empty($row);
	}

	public function listGrants($filters = array(), $limit = 200)
	{
		$where = array('1=1');
		if (!empty($filters['target_type']))
		{
			$tt = $filters['target_type'] === 'file' ? 'file' : 'category';
			$where[] = "target_type = '{$tt}'";
		}
		if (!empty($filters['targetid']))
		{
			$where[] = 'targetid = ' . (int)$filters['targetid'];
		}
		if (!empty($filters['grantee_type']))
		{
			$gt = $filters['grantee_type'] === 'usergroup' ? 'usergroup' : 'user';
			$where[] = "grantee_type = '{$gt}'";
		}
		if (!empty($filters['granteeid']))
		{
			$where[] = 'granteeid = ' . (int)$filters['granteeid'];
		}
		$limit = max(1, min(500, (int)$limit));
		$out = array();
		$res = $this->db->query_read("
			SELECT * FROM " . $this->t('vbdl_access_grant') . "
			WHERE " . implode(' AND ', $where) . "
			ORDER BY dateline DESC
			LIMIT {$limit}
		");
		while ($row = $this->db->fetch_array($res))
		{
			$out[] = $row;
		}
		return $out;
	}

	public function saveGrant(array $data)
	{
		$targetType = (!empty($data['target_type']) && $data['target_type'] === 'file') ? 'file' : 'category';
		$granteeType = (!empty($data['grantee_type']) && $data['grantee_type'] === 'usergroup') ? 'usergroup' : 'user';
		$targetId = (int)(isset($data['targetid']) ? $data['targetid'] : 0);
		$granteeId = (int)(isset($data['granteeid']) ? $data['granteeid'] : 0);
		if ($targetId < 1 || $granteeId < 1)
		{
			return 0;
		}
		$canView = !empty($data['can_view']) ? 1 : 0;
		$canDownload = !empty($data['can_download']) ? 1 : 0;
		$canUpload = !empty($data['can_upload']) ? 1 : 0;
		$note = $this->db->escape_string(isset($data['note']) ? $data['note'] : '');
		$grantedby = (int)(isset($data['grantedby']) ? $data['grantedby'] : 0);
		$expires = (int)(isset($data['expires']) ? $data['expires'] : 0);
		$dateline = time();

		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_access_grant') . "
			(target_type, targetid, grantee_type, granteeid, can_view, can_download, can_upload, note, grantedby, dateline, expires)
			VALUES (
				'{$targetType}', {$targetId}, '{$granteeType}', {$granteeId},
				{$canView}, {$canDownload}, {$canUpload}, '{$note}', {$grantedby}, {$dateline}, {$expires}
			)
			ON DUPLICATE KEY UPDATE
				can_view = VALUES(can_view),
				can_download = VALUES(can_download),
				can_upload = VALUES(can_upload),
				note = VALUES(note),
				grantedby = VALUES(grantedby),
				dateline = VALUES(dateline),
				expires = VALUES(expires)
		");
		return (int)$this->db->insert_id();
	}

	public function deleteGrant($grantid)
	{
		$grantid = (int)$grantid;
		$this->db->query_write("DELETE FROM " . $this->t('vbdl_access_grant') . " WHERE grantid = {$grantid}");
	}

	public function findUserIdByUsername($username)
	{
		$username = $this->db->escape_string(trim((string)$username));
		if ($username === '')
		{
			return 0;
		}
		$row = $this->db->query_first("SELECT userid FROM " . $this->t('user') . " WHERE username = '{$username}'");
		return $row ? (int)$row['userid'] : 0;
	}

	public function linkPostUpload($fileid, $nodeid, $userid)
	{
		$fileid = (int)$fileid;
		$nodeid = (int)$nodeid;
		$userid = (int)$userid;
		$now = time();
		$this->db->query_write("
			INSERT INTO " . $this->t('vbdl_post_upload') . " (fileid, nodeid, userid, dateline)
			VALUES ({$fileid}, {$nodeid}, {$userid}, {$now})
		");
	}
}
