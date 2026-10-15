CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_storage` (
  `storageid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL DEFAULT '',
  `storage_type` ENUM('local','s3') NOT NULL DEFAULT 'local',
  `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `local_path` VARCHAR(512) NOT NULL DEFAULT '',
  `s3_endpoint` VARCHAR(255) NOT NULL DEFAULT '',
  `s3_region` VARCHAR(64) NOT NULL DEFAULT 'us-east-1',
  `s3_bucket` VARCHAR(191) NOT NULL DEFAULT '',
  `s3_access_key` VARCHAR(255) NOT NULL DEFAULT '',
  `s3_secret_key` VARCHAR(255) NOT NULL DEFAULT '',
  `s3_path_style` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `s3_prefix` VARCHAR(191) NOT NULL DEFAULT '',
  `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`storageid`),
  KEY `storage_type` (`storage_type`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_category` (
  `categoryid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL DEFAULT '',
  `description` TEXT,
  `displayorder` INT NOT NULL DEFAULT 0,
  `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`categoryid`),
  KEY `displayorder` (`displayorder`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_file` (
  `fileid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categoryid` INT UNSIGNED NOT NULL DEFAULT 0,
  `storageid` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `description` MEDIUMTEXT,
  `filename` VARCHAR(255) NOT NULL DEFAULT '',
  `storage_key` VARCHAR(512) NOT NULL DEFAULT '',
  `mime` VARCHAR(127) NOT NULL DEFAULT 'application/octet-stream',
  `filesize` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `inherit_category_perms` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `access_type` ENUM('free','paid') NOT NULL DEFAULT 'free',
  `createdby` INT UNSIGNED NOT NULL DEFAULT 0,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  `lastupdate` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`fileid`),
  KEY `categoryid` (`categoryid`),
  KEY `storageid` (`storageid`),
  KEY `active` (`active`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_file_usergroup` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fileid` INT UNSIGNED NOT NULL DEFAULT 0,
  `categoryid` INT UNSIGNED NOT NULL DEFAULT 0,
  `usergroupid` INT UNSIGNED NOT NULL DEFAULT 0,
  `can_view` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_download` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_ug` (`fileid`,`usergroupid`),
  KEY `category_ug` (`categoryid`,`usergroupid`),
  KEY `usergroupid` (`usergroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_usergroup_perm` (
  `usergroupid` INT UNSIGNED NOT NULL,
  `can_view_section` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_download` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_upload` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_manage_own` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `admin_bypass` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`usergroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_log` (
  `logid` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fileid` INT UNSIGNED NOT NULL DEFAULT 0,
  `userid` INT UNSIGNED NOT NULL DEFAULT 0,
  `ipaddress` VARCHAR(45) NOT NULL DEFAULT '',
  `useragent` VARCHAR(255) NOT NULL DEFAULT '',
  `result` ENUM('ok','denied','error') NOT NULL DEFAULT 'ok',
  `message` VARCHAR(255) NOT NULL DEFAULT '',
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`logid`),
  KEY `fileid` (`fileid`),
  KEY `userid` (`userid`),
  KEY `result` (`result`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_setting` (
  `varname` VARCHAR(100) NOT NULL,
  `value` MEDIUMTEXT,
  PRIMARY KEY (`varname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `{TABLE_PREFIX}vbdl_setting` (`varname`, `value`) VALUES
('max_upload_bytes', '52428800'),
('allowed_extensions', 'zip,rar,7z,pdf,txt,doc,docx,xls,xlsx,png,jpg,jpeg,gif,mp4,mp3'),
('signed_url_ttl', '300'),
('download_mode', 'proxy'),
('guest_downloads', '0'),
('rate_limit_per_hour', '60'),
('downloads_per_page', '20'),
('vip_usergroupids', ''),
('vip_badge_label', 'VIP'),
('vip_contact_title', 'VIP Membership Required'),
('vip_contact_message', 'This is a paid VIP download.\nTo purchase VIP access, please contact the administrator.'),
('vip_contact_email', 'info@hdd-land.com'),
('vip_contact_url', 'https://forum.hdd-land.com/contact-us'),
('vip_contact_button_label', 'Contact Administrator');
