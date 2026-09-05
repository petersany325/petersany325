-- vbdlmanager: Access Grants + category access_mode
-- VIP usergroup alone does NOT unlock grant_required categories.

ALTER TABLE `{TABLE_PREFIX}vbdl_category`
  ADD COLUMN `access_mode` ENUM('free_open','grant_required') NOT NULL DEFAULT 'free_open' AFTER `active`;

ALTER TABLE `{TABLE_PREFIX}vbdl_file`
  ADD COLUMN `require_grant` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `access_type`;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_access_grant` (
  `grantid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type` ENUM('category','file') NOT NULL DEFAULT 'category',
  `targetid` INT UNSIGNED NOT NULL DEFAULT 0,
  `grantee_type` ENUM('user','usergroup') NOT NULL DEFAULT 'user',
  `granteeid` INT UNSIGNED NOT NULL DEFAULT 0,
  `can_view` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `can_download` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_upload` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `note` VARCHAR(255) NOT NULL DEFAULT '',
  `grantedby` INT UNSIGNED NOT NULL DEFAULT 0,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`grantid`),
  UNIQUE KEY `uniq_grant` (`target_type`,`targetid`,`grantee_type`,`granteeid`),
  KEY `grantee` (`grantee_type`,`granteeid`),
  KEY `target` (`target_type`,`targetid`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}vbdl_post_upload` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fileid` INT UNSIGNED NOT NULL DEFAULT 0,
  `nodeid` INT UNSIGNED NOT NULL DEFAULT 0,
  `userid` INT UNSIGNED NOT NULL DEFAULT 0,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fileid` (`fileid`),
  KEY `nodeid` (`nodeid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `{TABLE_PREFIX}vbdl_setting` (`varname`, `value`) VALUES
('post_upload_enabled', '1'),
('post_upload_default_categoryid', '0'),
('acl_vip_needs_grant', '1'),
('acl_paid_needs_grant', '1');

UPDATE `{TABLE_PREFIX}vbdl_category`
SET access_mode = 'grant_required'
WHERE title LIKE '%VIP%' OR title LIKE '%vip%' OR title LIKE '%Paid%' OR title LIKE '%SeDiv%VIP%';
