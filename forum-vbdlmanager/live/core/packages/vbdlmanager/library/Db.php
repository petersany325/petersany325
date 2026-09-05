<?php
/**
 * Resolve vBulletin DB connection across vB4/vB5/vB6.
 */

class vbdl_Db
{
	/**
	 * @return object|null Object with query_write / query_first / fetch_array / escape_string
	 */
	public static function connection()
	{
		global $db, $vbulletin;

		if (!empty($db) && is_object($db) && method_exists($db, 'query_write'))
		{
			return $db;
		}

		if (!empty($vbulletin) && is_object($vbulletin) && !empty($vbulletin->db) && is_object($vbulletin->db) && method_exists($vbulletin->db, 'query_write'))
		{
			return $vbulletin->db;
		}

		if (class_exists('vB', false) && is_callable(array('vB', 'get_registry')))
		{
			$reg = vB::get_registry();
			if (is_object($reg) && !empty($reg->db) && is_object($reg->db) && method_exists($reg->db, 'query_write'))
			{
				return $reg->db;
			}
		}

		if (class_exists('vB', false) && is_callable(array('vB', 'getDbAssertor')))
		{
			$assertor = vB::getDbAssertor();
			if (is_object($assertor))
			{
				if (method_exists($assertor, 'getDBConnection'))
				{
					$conn = $assertor->getDBConnection();
					if (is_object($conn) && method_exists($conn, 'query_write'))
					{
						return $conn;
					}
				}
				if (method_exists($assertor, 'query_write'))
				{
					return $assertor;
				}
			}
		}

		return null;
	}

	public static function tablePrefix()
	{
		global $table_prefix, $vbulletin;

		if (defined('TABLE_PREFIX'))
		{
			return TABLE_PREFIX;
		}

		if (isset($table_prefix) && $table_prefix !== null && $table_prefix !== '')
		{
			return (string)$table_prefix;
		}

		if (class_exists('vB', false))
		{
			try
			{
				$ref = new ReflectionClass('vB');
				if ($ref->hasConstant('TABLE_PREFIX'))
				{
					return (string)$ref->getConstant('TABLE_PREFIX');
				}
			}
			catch (Exception $e)
			{
			}

			if (is_callable(array('vB', 'getConfig')))
			{
				$config = vB::getConfig();
				if (isset($config['Database']['tableprefix']))
				{
					return (string)$config['Database']['tableprefix'];
				}
			}
		}

		if (!empty($vbulletin) && is_object($vbulletin) && isset($vbulletin->config['Database']['tableprefix']))
		{
			return (string)$vbulletin->config['Database']['tableprefix'];
		}

		return '';
	}

	/**
	 * Create product tables if missing (safe to call repeatedly).
	 * @param object|null $db
	 * @param string|null $tp
	 */
	public static function ensureSchema($db = null, $tp = null)
	{
		if ($db === null)
		{
			$db = self::connection();
		}
		if ($tp === null)
		{
			$tp = self::tablePrefix();
		}
		if ($db === null || !method_exists($db, 'query_write'))
		{
			return false;
		}

		if (method_exists($db, 'hide_errors'))
		{
			$db->hide_errors();
		}

		$queries = array(
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_storage (
			  storageid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  title VARCHAR(191) NOT NULL DEFAULT '',
			  storage_type ENUM('local','s3') NOT NULL DEFAULT 'local',
			  is_default TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  local_path VARCHAR(512) NOT NULL DEFAULT '',
			  s3_endpoint VARCHAR(255) NOT NULL DEFAULT '',
			  s3_region VARCHAR(64) NOT NULL DEFAULT 'us-east-1',
			  s3_bucket VARCHAR(191) NOT NULL DEFAULT '',
			  s3_access_key VARCHAR(255) NOT NULL DEFAULT '',
			  s3_secret_key VARCHAR(255) NOT NULL DEFAULT '',
			  s3_path_style TINYINT UNSIGNED NOT NULL DEFAULT 1,
			  s3_prefix VARCHAR(191) NOT NULL DEFAULT '',
			  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
			  dateline INT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (storageid),
			  KEY storage_type (storage_type),
			  KEY active (active)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_category (
			  categoryid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  title VARCHAR(191) NOT NULL DEFAULT '',
			  description TEXT,
			  displayorder INT NOT NULL DEFAULT 0,
			  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
			  dateline INT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (categoryid),
			  KEY displayorder (displayorder),
			  KEY active (active)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_file (
			  fileid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  categoryid INT UNSIGNED NOT NULL DEFAULT 0,
			  storageid INT UNSIGNED NOT NULL DEFAULT 0,
			  title VARCHAR(255) NOT NULL DEFAULT '',
			  description MEDIUMTEXT,
			  filename VARCHAR(255) NOT NULL DEFAULT '',
			  storage_key VARCHAR(512) NOT NULL DEFAULT '',
			  mime VARCHAR(127) NOT NULL DEFAULT 'application/octet-stream',
			  filesize BIGINT UNSIGNED NOT NULL DEFAULT 0,
			  downloads_count INT UNSIGNED NOT NULL DEFAULT 0,
			  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
			  inherit_category_perms TINYINT UNSIGNED NOT NULL DEFAULT 1,
			  createdby INT UNSIGNED NOT NULL DEFAULT 0,
			  dateline INT UNSIGNED NOT NULL DEFAULT 0,
			  lastupdate INT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (fileid),
			  KEY categoryid (categoryid),
			  KEY storageid (storageid),
			  KEY active (active),
			  KEY dateline (dateline)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_file_usergroup (
			  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  fileid INT UNSIGNED NOT NULL DEFAULT 0,
			  categoryid INT UNSIGNED NOT NULL DEFAULT 0,
			  usergroupid INT UNSIGNED NOT NULL DEFAULT 0,
			  can_view TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  can_download TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (id),
			  UNIQUE KEY file_ug (fileid, usergroupid),
			  KEY category_ug (categoryid, usergroupid),
			  KEY usergroupid (usergroupid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_usergroup_perm (
			  usergroupid INT UNSIGNED NOT NULL,
			  can_view_section TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  can_download TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  can_upload TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  can_manage_own TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  admin_bypass TINYINT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (usergroupid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_log (
			  logid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  fileid INT UNSIGNED NOT NULL DEFAULT 0,
			  userid INT UNSIGNED NOT NULL DEFAULT 0,
			  ipaddress VARCHAR(45) NOT NULL DEFAULT '',
			  useragent VARCHAR(255) NOT NULL DEFAULT '',
			  result ENUM('ok','denied','error') NOT NULL DEFAULT 'ok',
			  message VARCHAR(255) NOT NULL DEFAULT '',
			  dateline INT UNSIGNED NOT NULL DEFAULT 0,
			  PRIMARY KEY (logid),
			  KEY fileid (fileid),
			  KEY userid (userid),
			  KEY result (result),
			  KEY dateline (dateline)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS {$tp}vbdl_setting (
			  varname VARCHAR(100) NOT NULL,
			  value MEDIUMTEXT,
			  PRIMARY KEY (varname)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"INSERT IGNORE INTO {$tp}vbdl_setting (varname, value) VALUES
			('max_upload_bytes', '52428800'),
			('allowed_extensions', 'zip,rar,7z,pdf,txt,doc,docx,xls,xlsx,png,jpg,jpeg,gif,mp4,mp3'),
			('signed_url_ttl', '300'),
			('download_mode', 'proxy'),
			('guest_downloads', '1'),
			('rate_limit_per_hour', '60'),
			('downloads_per_page', '20')",
			"INSERT IGNORE INTO {$tp}vbdl_setting (varname, value) VALUES
			('vip_contact_telegram', ''),
			('vip_contact_whatsapp', ''),
			('vip_telegram_button_label', 'Telegram'),
			('vip_whatsapp_button_label', 'WhatsApp')",
			"INSERT IGNORE INTO {$tp}vbdl_setting (varname, value) VALUES
			('public_free_library', '1'),
			('limited_access_title', 'Your download access is limited'),
			('limited_access_message', 'You can browse and download Free files.\nFor VIP / Paid files, contact the administrator.'),
			('vip_upload_usergroupids', '14,15'),
			('newtopic_upload_choice', '1'),
			('telegram_bot_enabled', '0'),
			('telegram_bot_token', ''),
			('telegram_bot_username', ''),
			('telegram_admin_chat_id', '')",
			"INSERT IGNORE INTO {$tp}vbdl_usergroup_perm
			(usergroupid, can_view_section, can_download, can_upload, can_manage_own, admin_bypass)
			VALUES (6, 1, 1, 1, 1, 1)",
			"INSERT IGNORE INTO {$tp}vbdl_usergroup_perm
			(usergroupid, can_view_section, can_download, can_upload, can_manage_own, admin_bypass)
			VALUES (2, 1, 1, 0, 0, 0)",
		);

		foreach ($queries as $sql)
		{
			$db->query_write($sql);
		}

		if (method_exists($db, 'show_errors'))
		{
			$db->show_errors();
		}

		return true;
	}

	public static function dropSchema($db = null, $tp = null)
	{
		if ($db === null)
		{
			$db = self::connection();
		}
		if ($tp === null)
		{
			$tp = self::tablePrefix();
		}
		if ($db === null || !method_exists($db, 'query_write'))
		{
			return false;
		}
		if (method_exists($db, 'hide_errors'))
		{
			$db->hide_errors();
		}
		foreach (array('vbdl_log', 'vbdl_file_usergroup', 'vbdl_usergroup_perm', 'vbdl_file', 'vbdl_category', 'vbdl_storage', 'vbdl_setting') as $table)
		{
			$db->query_write("DROP TABLE IF EXISTS {$tp}{$table}");
		}
		if (method_exists($db, 'show_errors'))
		{
			$db->show_errors();
		}
		return true;
	}
}
