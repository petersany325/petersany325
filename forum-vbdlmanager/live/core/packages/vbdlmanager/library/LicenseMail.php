<?php
/**
 * SeDiv VIP license email tracking + return (.src) into Message Center tickets.
 */
class vbdl_LicenseMail
{
	/** @var mysqli */
	protected $db;
	/** @var string */
	protected $prefix;
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_Acl */
	protected $acl;
	/** @var string */
	protected $lastNodeError = '';

	public function __construct(mysqli $db, $prefix, vbdl_Repository $repo, vbdl_Acl $acl)
	{
		$this->db = $db;
		$this->prefix = (string)$prefix;
		$this->repo = $repo;
		$this->acl = $acl;
		$this->ensureTable();
	}

	public function ensureTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}vbdl_license_mail (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			message_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			starter_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			customer_userid INT UNSIGNED NOT NULL DEFAULT 0,
			customer_username VARCHAR(191) NOT NULL DEFAULT '',
			customer_email VARCHAR(191) NOT NULL DEFAULT '',
			staff_userid INT UNSIGNED NOT NULL DEFAULT 0,
			filedataid INT UNSIGNED NOT NULL DEFAULT 0,
			lic_filename VARCHAR(255) NOT NULL DEFAULT '',
			license_type VARCHAR(64) NOT NULL DEFAULT '',
			to_email VARCHAR(191) NOT NULL DEFAULT '',
			subject VARCHAR(255) NOT NULL DEFAULT '',
			status ENUM('sent','returned','rejected','error') NOT NULL DEFAULT 'sent',
			return_filename VARCHAR(255) NOT NULL DEFAULT '',
			return_filedataid INT UNSIGNED NOT NULL DEFAULT 0,
			return_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			reply_text MEDIUMTEXT,
			src_purged TINYINT UNSIGNED NOT NULL DEFAULT 0,
			src_purged_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			download_count INT UNSIGNED NOT NULL DEFAULT 0,
			last_download_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			sent_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			returned_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			meta MEDIUMTEXT,
			PRIMARY KEY (id),
			UNIQUE KEY token (token),
			KEY message_nodeid (message_nodeid),
			KEY customer_userid (customer_userid),
			KEY status (status),
			KEY license_type (license_type)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@$this->db->query($sql);
		$this->migrateColumns();
	}

	protected function migrateColumns()
	{
		$p = $this->prefix;
		$cols = array();
		$res = @$this->db->query('SHOW COLUMNS FROM ' . $p . 'vbdl_license_mail');
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$cols[strtolower($row['Field'])] = true;
			}
		}
		$alters = array(
			'license_type' => "ADD COLUMN license_type VARCHAR(64) NOT NULL DEFAULT '' AFTER lic_filename",
			'reply_text' => "ADD COLUMN reply_text MEDIUMTEXT NULL AFTER return_nodeid",
			'src_purged' => "ADD COLUMN src_purged TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER reply_text",
			'src_purged_dateline' => "ADD COLUMN src_purged_dateline INT UNSIGNED NOT NULL DEFAULT 0 AFTER src_purged",
			'download_count' => "ADD COLUMN download_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER src_purged_dateline",
			'last_download_dateline' => "ADD COLUMN last_download_dateline INT UNSIGNED NOT NULL DEFAULT 0 AFTER download_count",
		);
		foreach ($alters as $col => $ddl)
		{
			if (empty($cols[$col]))
			{
				@$this->db->query('ALTER TABLE ' . $p . 'vbdl_license_mail ' . $ddl);
			}
		}
		// Allow rejected status on existing installs.
		@$this->db->query(
			"ALTER TABLE {$p}vbdl_license_mail MODIFY status ENUM('sent','returned','rejected','error') NOT NULL DEFAULT 'sent'"
		);
	}

	/**
	 * Fixed license products + exact email subjects for sedivlic recognition.
	 */
	public function licenseTypes()
	{
		return array(
			array(
				'id' => 'sediv_imager',
				'group' => 'imager',
				'label' => 'All license SeDiv imager',
				'subject' => 'Subject license SeDiv imager',
			),
			array(
				'id' => 'sehitachi_imager',
				'group' => 'imager',
				'label' => 'All license SeHitachi imager',
				'subject' => 'Subject license SeHitachi imager',
			),
			array(
				'id' => 'sedivx_imager',
				'group' => 'imager',
				'label' => 'All license SeDivX imager',
				'subject' => 'Subject license SeDivX imager',
			),
			array(
				'id' => 'sehgst_imager',
				'group' => 'imager',
				'label' => 'All license SeHGST imager',
				'subject' => 'Subject license SeHGST imager',
			),
			array(
				'id' => 'sediv_repairs',
				'group' => 'repairs',
				'label' => 'All license SeDiv repairs',
				'subject' => 'Subject license SeDiv repairs',
			),
			array(
				'id' => 'sehitachi_repairs',
				'group' => 'repairs',
				'label' => 'All license SeHitachi repairs',
				'subject' => 'Subject license SeHitachi repairs',
			),
		);
	}

	public function getLicenseType($typeId)
	{
		$typeId = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$typeId));
		foreach ($this->licenseTypes() as $t)
		{
			if ($t['id'] === $typeId)
			{
				return $t;
			}
		}
		return null;
	}

	public function sedivEmail()
	{
		$e = trim((string)$this->repo->getSetting('license_sediv_email', 'sedivlic@list.ru'));
		return $e !== '' ? $e : 'sedivlic@list.ru';
	}

	public function sedivSubject()
	{
		$s = trim((string)$this->repo->getSetting('license_sediv_subject', 'Active SeDiv 2026'));
		return $s !== '' ? $s : 'Active SeDiv 2026';
	}

	public function srcRetentionDays()
	{
		$d = (int)$this->repo->getSetting('license_src_retention_days', '7');
		return $d > 0 ? $d : 7;
	}

	public function vipOnly()
	{
		return (string)$this->repo->getSetting('license_vip_only', '1') !== '0';
	}

	/**
	 * Support/admin userid that receives every VIP license ticket (default: 1).
	 */
	public function supportUserid()
	{
		$id = (int)$this->repo->getSetting('license_support_userid', '1');
		return $id > 0 ? $id : 1;
	}

	public function customerEmail($userid)
	{
		$userid = (int)$userid;
		if ($userid < 1)
		{
			return '';
		}
		$res = $this->db->query('SELECT email FROM ' . $this->prefix . 'user WHERE userid=' . $userid . ' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return trim((string)$row['email']);
		}
		return '';
	}

	public function customerUserinfo($userid)
	{
		$userid = (int)$userid;
		$res = $this->db->query('SELECT userid, username, email, usergroupid, membergroupids FROM ' . $this->prefix . 'user WHERE userid=' . $userid . ' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return $row;
		}
		return null;
	}

	public function isCustomerVip($userid)
	{
		$u = $this->customerUserinfo($userid);
		if (!$u)
		{
			return false;
		}
		return $this->acl->isVip($u);
	}

	public function listSelectColumns()
	{
		return 'token, message_nodeid, starter_nodeid, customer_userid, customer_username, lic_filename, '
			. 'license_type, subject, status, return_filename, return_filedataid, return_nodeid, '
			. 'reply_text, src_purged, src_purged_dateline, download_count, last_download_dateline, '
			. 'sent_dateline, returned_dateline';
	}

	public function listForUser($userid, $limit = 30)
	{
		$userid = (int)$userid;
		$limit = max(1, min(100, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT ' . $this->listSelectColumns() . ' FROM ' . $this->prefix . 'vbdl_license_mail '
			. 'WHERE customer_userid=' . $userid . ' ORDER BY id DESC LIMIT ' . $limit
		);
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$out[] = $this->enrichListRow($row);
			}
		}
		return $out;
	}

	/**
	 * Staff overview: all VIP license tickets (send + return review).
	 */
	public function listAll($limit = 50)
	{
		$limit = max(1, min(200, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT ' . $this->listSelectColumns() . ' FROM ' . $this->prefix . 'vbdl_license_mail '
			. 'ORDER BY id DESC LIMIT ' . $limit
		);
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$out[] = $this->enrichListRow($row);
			}
		}
		return $out;
	}

	protected function enrichListRow(array $row)
	{
		$type = $this->getLicenseType(isset($row['license_type']) ? $row['license_type'] : '');
		$row['license_label'] = $type ? $type['label'] : (isset($row['license_type']) ? (string)$row['license_type'] : '');
		$mirrorOk = $this->licenseMirrorExists(isset($row['token']) ? $row['token'] : '', 'src');
		$row['src_available'] = (
			!empty($row['status']) && $row['status'] === 'returned'
			&& empty($row['src_purged'])
			&& (!empty($row['return_filedataid']) || $mirrorOk)
		) ? 1 : 0;
		$row['src_download_url'] = !empty($row['src_available'])
			? ('/vbdlmanager/pm_lic_download.php?token=' . rawurlencode((string)$row['token']) . '&kind=src')
			: '';
		// Keep download_count in sync with Message Center attach counter when present.
		if (!empty($row['return_nodeid']))
		{
			$this->syncDownloadCountFromAttach($row);
		}
		return $row;
	}

	protected function syncDownloadCountFromAttach(array &$row)
	{
		$nodeid = (int)$row['return_nodeid'];
		if ($nodeid < 1)
		{
			return;
		}
		$res = $this->db->query(
			'SELECT counter FROM ' . $this->prefix . 'attach WHERE nodeid=' . $nodeid . ' LIMIT 1'
		);
		if (!$res || !($a = $res->fetch_assoc()))
		{
			return;
		}
		$counter = (int)$a['counter'];
		$stored = (int)$row['download_count'];
		if ($counter > $stored)
		{
			$tokenEsc = $this->db->real_escape_string((string)$row['token']);
			$this->db->query(
				'UPDATE ' . $this->prefix . 'vbdl_license_mail SET download_count=' . $counter
				. ', last_download_dateline=' . time()
				. ' WHERE token=\'' . $tokenEsc . '\' AND download_count < ' . $counter
			);
			$row['download_count'] = $counter;
		}
	}

	/**
	 * Create a Message Center ticket for a VIP license request.
	 * VIP is the author; support/admin is a participant so staff can review the full send/return flow.
	 */
	public function createVipLicenseTicket($userid, $username, $token, $licFilename, $typeLabel = '', $emailSubject = '')
	{
		$userid = (int)$userid;
		$username = (string)$username;
		$token = (string)$token;
		$licFilename = (string)$licFilename;
		$typeLabel = trim((string)$typeLabel);
		$emailSubject = trim((string)$emailSubject);
		if ($typeLabel === '')
		{
			$typeLabel = 'Active License SeDiv';
		}
		$p = $this->prefix;
		$supportId = $this->supportUserid();
		if ($supportId === $userid)
		{
			// VIP is also support — still open a normal self ticket.
			$supportId = $userid;
		}
		$supportName = $this->usernameById($supportId);
		$data = null;

		try
		{
			if (!class_exists('vB_Api') && !class_exists('vB_Library'))
			{
				return array('error' => 'Cannot create Message Center ticket for VIP + support');
			}
			$title = $typeLabel . ' - ' . $token;
			$text = $typeLabel . " request\n"
				. "================================\n"
				. "VIP user: {$username} (userid {$userid})\n"
				. "Tracking token: {$token}\n"
				. "License type: {$typeLabel}\n"
				. "License file: {$licFilename}\n";
			if ($emailSubject !== '')
			{
				$text .= "Email subject: {$emailSubject}\n";
			}
			$text .= "\nThis ticket is for license send + activated .src return only.\n"
				. "Support can review every step here.";
			$recipients = ($supportId !== $userid) ? $supportName : $username;
			$sentto = ($supportId !== $userid) ? array($supportId) : array($userid);
			$data = array(
				'title' => $title,
				'rawtext' => $text,
				'msgtext' => $text,
				'sentto' => $sentto,
				'recipients' => $recipients,
				'parentid' => 0,
				'userid' => $userid,
			);
			// VIP may send several license types in a row — skip PM flood check.
			$opts = array(
				'bypassPerms' => true,
				'skipFloodCheck' => true,
			);
			$result = null;
			if (class_exists('vB_Library'))
			{
				$lib = vB_Library::instance('content_privatemessage');
				if ($lib && method_exists($lib, 'addMessageNoFlood'))
				{
					$result = $lib->addMessageNoFlood($data, $opts);
				}
				elseif ($lib && method_exists($lib, 'add'))
				{
					$result = $lib->add($data, $opts);
				}
			}
			if ($result === null && class_exists('vB_Api'))
			{
				$api = vB_Api::instanceInternal('content_privatemessage');
				if ($api && method_exists($api, 'add'))
				{
					$result = $api->add($data, $opts);
				}
			}
			if ($result === null)
			{
				return array('error' => 'Cannot create Message Center ticket for VIP + support');
			}
			// Library addMessageNoFlood may return nodeid int; API returns array.
			if (is_array($result) && !empty($result['errors']))
			{
				$err = $result['errors'];
				$msg = is_array($err) ? json_encode($err) : (string)$err;
				return array('error' => 'PM ticket failed: ' . strip_tags($msg));
			}
			$nodeid = 0;
			if (is_array($result))
			{
				if (!empty($result['nodeid']))
				{
					$nodeid = (int)$result['nodeid'];
				}
				elseif (!empty($result[0]) && is_numeric($result[0]))
				{
					$nodeid = (int)$result[0];
				}
			}
			elseif (is_numeric($result))
			{
				$nodeid = (int)$result;
			}
			if ($nodeid > 0)
			{
				$starter = $nodeid;
				$res = $this->db->query('SELECT starter FROM ' . $p . 'node WHERE nodeid=' . $nodeid . ' LIMIT 1');
				if ($res && ($row = $res->fetch_assoc()) && !empty($row['starter']))
				{
					$starter = (int)$row['starter'];
				}
				$this->ensureParticipant($nodeid, $userid);
				if ($supportId !== $userid)
				{
					$this->ensureParticipant($nodeid, $supportId);
				}
				$this->ensureStaffParticipants($nodeid);
				return array(
					'message_nodeid' => $nodeid,
					'starter_nodeid' => $starter,
					'support_userid' => $supportId,
				);
			}
			return array('error' => 'PM API returned no nodeid');
		}
		catch (Throwable $e)
		{
			$msg = trim(preg_replace('/\s+/', ' ', strip_tags($e->getMessage())));
			// Still retry once via no-flood library if flood somehow threw.
			if (stripos($msg, 'pmfloodcheck') !== false && class_exists('vB_Library') && is_array($data))
			{
				try
				{
					$lib = vB_Library::instance('content_privatemessage');
					if ($lib && method_exists($lib, 'addMessageNoFlood'))
					{
						$result = $lib->addMessageNoFlood($data, array('bypassPerms' => true, 'skipFloodCheck' => true));
						$nodeid = is_numeric($result) ? (int)$result : (is_array($result) && !empty($result['nodeid']) ? (int)$result['nodeid'] : 0);
						if ($nodeid > 0)
						{
							$starter = $nodeid;
							$res = $this->db->query('SELECT starter FROM ' . $p . 'node WHERE nodeid=' . $nodeid . ' LIMIT 1');
							if ($res && ($row = $res->fetch_assoc()) && !empty($row['starter']))
							{
								$starter = (int)$row['starter'];
							}
							$this->ensureParticipant($nodeid, $userid);
							if ($supportId !== $userid)
							{
								$this->ensureParticipant($nodeid, $supportId);
							}
							$this->ensureStaffParticipants($nodeid);
							return array(
								'message_nodeid' => $nodeid,
								'starter_nodeid' => $starter,
								'support_userid' => $supportId,
							);
						}
					}
				}
				catch (Throwable $e2)
				{
					$msg = trim(preg_replace('/\s+/', ' ', strip_tags($e2->getMessage())));
				}
			}
			return array('error' => 'PM ticket failed: ' . $msg);
		}

		return array('error' => 'Cannot create Message Center ticket for VIP + support');
	}

	/**
	 * Ensure a user can see the PM ticket in their Inbox (messages folder).
	 * VIP senders otherwise only get Sent Items and miss the ticket in Inbox.
	 */
	protected function ensureParticipant($nodeid, $userid)
	{
		$nodeid = (int)$nodeid;
		$userid = (int)$userid;
		if ($nodeid < 1 || $userid < 1)
		{
			return;
		}
		$p = $this->prefix;
		$inboxId = $this->messagesFolderId($userid);
		if ($inboxId < 1)
		{
			return;
		}
		$res = $this->db->query(
			'SELECT folderid FROM ' . $p . 'sentto WHERE nodeid=' . $nodeid . ' AND userid=' . $userid
		);
		$hasInbox = false;
		$any = false;
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$any = true;
				if ((int)$row['folderid'] === $inboxId)
				{
					$hasInbox = true;
				}
			}
		}
		if ($hasInbox)
		{
			return;
		}
		if ($any)
		{
			// Prefer Inbox over Sent Items for VIP visibility.
			$this->db->query(
				'UPDATE ' . $p . 'sentto SET folderid=' . (int)$inboxId . ', deleted=0'
				. ' WHERE nodeid=' . $nodeid . ' AND userid=' . $userid
				. ' LIMIT 1'
			);
			return;
		}
		$this->db->query(
			'INSERT IGNORE INTO ' . $p . 'sentto (nodeid, userid, folderid, deleted, msgread) VALUES ('
			. $nodeid . ',' . $userid . ',' . (int)$inboxId . ',0,0)'
		);
	}

	/**
	 * Add administrators / license staff so they can open the ticket and download attaches.
	 * Without this, only VIP + support_userid are participants → other admins get Invalid File Specified.
	 */
	public function ensureStaffParticipants($nodeid)
	{
		$nodeid = (int)$nodeid;
		if ($nodeid < 1)
		{
			return;
		}
		$ids = array(1, $this->supportUserid());
		$p = $this->prefix;
		$gids = array(6, 5);
		$raw = trim((string)$this->repo->getSetting('license_email_usergroupids', '6'));
		foreach (explode(',', $raw) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$gids[] = $g;
			}
		}
		$gids = array_values(array_unique($gids));
		$gList = implode(',', array_map('intval', $gids));
		$res = @$this->db->query(
			'SELECT userid FROM ' . $p . 'user WHERE usergroupid IN (' . $gList . ') ORDER BY userid ASC LIMIT 40'
		);
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$ids[] = (int)$row['userid'];
			}
		}
		foreach ($gids as $gid)
		{
			$res2 = @$this->db->query(
				'SELECT userid FROM ' . $p . 'user WHERE FIND_IN_SET(' . (int)$gid . ', membergroupids) ORDER BY userid ASC LIMIT 20'
			);
			if ($res2)
			{
				while ($row = $res2->fetch_assoc())
				{
					$ids[] = (int)$row['userid'];
				}
			}
		}
		foreach (array_unique($ids) as $uid)
		{
			if ((int)$uid > 0)
			{
				$this->ensureParticipant($nodeid, (int)$uid);
			}
		}
	}

	/**
	 * Heal staff/admin ACL on an existing license ticket (all related PM nodes).
	 * Fixes "Invalid File Specified" when the .src is on disk but admin is not in sentto.
	 *
	 * @param string|array $tokenOrRecord
	 * @return array
	 */
	public function healTicketStaffAccess($tokenOrRecord)
	{
		$rec = is_array($tokenOrRecord) ? $tokenOrRecord : $this->findByToken((string)$tokenOrRecord);
		if (!$rec || empty($rec['token']))
		{
			return array('error' => 'Unknown tracking token');
		}
		$p = $this->prefix;
		$nodes = array();
		foreach (array('message_nodeid', 'starter_nodeid', 'return_nodeid') as $k)
		{
			if (!empty($rec[$k]) && (int)$rec[$k] > 0)
			{
				$nodes[] = (int)$rec[$k];
			}
		}
		$starter = !empty($rec['starter_nodeid']) ? (int)$rec['starter_nodeid'] : (int)$rec['message_nodeid'];
		if ($starter > 0)
		{
			$res = @$this->db->query(
				'SELECT DISTINCT s.nodeid FROM ' . $p . 'sentto s '
				. 'INNER JOIN ' . $p . 'node n ON n.nodeid=s.nodeid '
				. 'WHERE s.nodeid=' . $starter
				. ' OR n.nodeid=' . $starter
				. ' OR n.starter=' . $starter
				. ' OR n.parentid=' . $starter
				. ' LIMIT 200'
			);
			if ($res)
			{
				while ($row = $res->fetch_assoc())
				{
					$nodes[] = (int)$row['nodeid'];
				}
			}
			if (!empty($rec['return_nodeid']))
			{
				$rid = (int)$rec['return_nodeid'];
				$res2 = @$this->db->query(
					'SELECT nodeid, parentid, starter FROM ' . $p . 'node WHERE nodeid=' . $rid . ' LIMIT 1'
				);
				if ($res2 && ($nr = $res2->fetch_assoc()))
				{
					$nodes[] = (int)$nr['nodeid'];
					if (!empty($nr['parentid']))
					{
						$nodes[] = (int)$nr['parentid'];
					}
					if (!empty($nr['starter']))
					{
						$nodes[] = (int)$nr['starter'];
					}
				}
			}
		}
		$nodes = array_values(array_unique(array_filter(array_map('intval', $nodes))));
		foreach ($nodes as $nid)
		{
			$this->ensureStaffParticipants($nid);
		}
		return array(
			'ok' => true,
			'token' => (string)$rec['token'],
			'nodes_healed' => $nodes,
			'node_count' => count($nodes),
		);
	}

	protected function messagesFolderId($userid)
	{
		$userid = (int)$userid;
		$p = $this->prefix;
		$res = $this->db->query(
			'SELECT folderid FROM ' . $p . 'messagefolder WHERE userid=' . $userid
			. ' AND titlephrase=\'messages\' LIMIT 1'
		);
		if ($res && ($row = $res->fetch_assoc()))
		{
			return (int)$row['folderid'];
		}
		// Fallback: any non-system custom/inbox-like folder
		$res = $this->db->query(
			'SELECT folderid FROM ' . $p . 'messagefolder WHERE userid=' . $userid . ' ORDER BY folderid ASC LIMIT 1'
		);
		if ($res && ($row = $res->fetch_assoc()))
		{
			return (int)$row['folderid'];
		}
		return 0;
	}

	/**
	 * Attach a binary file (.lic / .src) under a PM ticket so MC lists it.
	 */
	public function attachFileToTicket($parentId, $starterId, $ownerUserid, $filename, $bytes)
	{
		$parentId = (int)$parentId;
		$starterId = (int)$starterId;
		$ownerUserid = (int)$ownerUserid;
		$filename = preg_replace('/[^\w.\-()+@]+/', '_', (string)$filename);
		if ($parentId < 1 || $ownerUserid < 1 || $bytes === '' || $bytes === null)
		{
			return array('error' => 'Invalid attach parameters');
		}
		if ($starterId < 1)
		{
			$starterId = $parentId;
		}
		$this->ensureAttachmentTypes();
		$p = $this->prefix;
		$now = time();
		$hash = md5($bytes);
		$size = strlen($bytes);
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ($ext === '')
		{
			$ext = 'bin';
		}
		$attachType = $this->contentTypeId('Attach');
		if ($attachType < 1)
		{
			return array('error' => 'contenttype Attach missing');
		}

		$filedataid = 0;
		if ($size <= 512000)
		{
			$hex = bin2hex($bytes);
			$sqlFd = 'INSERT INTO ' . $p . 'filedata (userid, dateline, filehash, filesize, extension, filedata, refcount) VALUES ('
				. $ownerUserid . ',' . $now . ',\'' . $this->db->real_escape_string($hash) . '\','
				. $size . ',\'' . $this->db->real_escape_string($ext) . '\',UNHEX(\'' . $hex . '\'),1)';
			if ($this->db->query($sqlFd))
			{
				$filedataid = (int)$this->db->insert_id;
			}
		}
		if ($filedataid < 1)
		{
			$sqlFd = 'INSERT INTO ' . $p . 'filedata (userid, dateline, filehash, filesize, extension, filedata, refcount) VALUES ('
				. $ownerUserid . ',' . $now . ',\'' . $this->db->real_escape_string($hash) . '\','
				. $size . ',\'' . $this->db->real_escape_string($ext) . '\',\'\',1)';
			if (!$this->db->query($sqlFd))
			{
				return array('error' => 'filedata insert failed: ' . $this->db->error);
			}
			$filedataid = (int)$this->db->insert_id;
			if ($this->writeAttachFile($filedataid, $ownerUserid, $bytes) === '')
			{
				return array('error' => 'Could not write attachment to filesystem');
			}
		}
		$this->db->query('UPDATE ' . $p . 'filedata SET refcount=GREATEST(refcount,1) WHERE filedataid=' . $filedataid);

		$parent = null;
		$resP = $this->db->query('SELECT routeid FROM ' . $p . 'node WHERE nodeid=' . $parentId . ' LIMIT 1');
		if ($resP)
		{
			$parent = $resP->fetch_assoc();
		}
		$routeid = $parent && !empty($parent['routeid']) ? (int)$parent['routeid'] : 63;
		$author = $this->usernameById($ownerUserid);

		$attachNode = $this->insertNode(array(
			'routeid' => $routeid,
			'userid' => $ownerUserid,
			'authorname' => $author,
			'parentid' => $parentId,
			'starter' => $starterId,
			'contenttypeid' => $attachType,
			'created' => $now,
			'lastcontent' => $now,
			'lastcontentid' => 0,
			'lastcontentauthor' => $author,
			'lastauthorid' => $ownerUserid,
			'lastprefixid' => '',
			'publishdate' => $now,
			'showpublished' => 1,
			'showopen' => 1,
			'open' => 1,
			'approved' => 1,
			'showapproved' => 1,
			'ipaddress' => '',
			'CRC32' => (string)sprintf('%u', crc32($filename)),
			'prefixid' => '',
			'inlist' => 0,
			'protected' => 1,
			'nodeoptions' => 138,
			'hasphoto' => 0,
		));
		if ($attachNode < 1)
		{
			return array('error' => 'Failed creating attach node: ' . $this->lastNodeError);
		}
		$this->ensureClosure($attachNode, $parentId, $now);
		$this->db->query(
			'INSERT INTO ' . $p . 'attach (nodeid, filedataid, filename, counter, settings, visible) VALUES ('
			. $attachNode . ',' . $filedataid . ',\'' . $this->db->real_escape_string($filename) . '\',0,\'\',1)'
		);
		$this->db->query(
			'UPDATE ' . $p . 'node SET lastcontent=' . $now . ', lastcontentid=' . $attachNode
			. ', lastcontentauthor=\'' . $this->db->real_escape_string($author) . '\''
			. ', lastauthorid=' . $ownerUserid
			. ', hasphoto=1'
			. ' WHERE nodeid=' . $parentId
		);
		$this->clearNodeCaches(array($parentId, $attachNode));
		return array(
			'ok' => true,
			'filedataid' => $filedataid,
			'attach_nodeid' => $attachNode,
		);
	}

	public function makeToken()
	{
		if (function_exists('random_bytes'))
		{
			$hex = bin2hex(random_bytes(6));
		}
		else
		{
			$hex = bin2hex(openssl_random_pseudo_bytes(6));
		}
		return 'VBDL-LIC-' . strtoupper($hex);
	}

	public function createSentRecord(array $data)
	{
		$token = isset($data['token']) ? (string)$data['token'] : $this->makeToken();
		$messageNode = (int)$data['message_nodeid'];
		$starter = (int)(isset($data['starter_nodeid']) ? $data['starter_nodeid'] : $messageNode);
		$custId = (int)$data['customer_userid'];
		$custUser = $this->db->real_escape_string((string)$data['customer_username']);
		$custEmail = $this->db->real_escape_string((string)$data['customer_email']);
		$staffId = (int)$data['staff_userid'];
		$filedataid = (int)$data['filedataid'];
		$licName = $this->db->real_escape_string((string)$data['lic_filename']);
		$licType = $this->db->real_escape_string(isset($data['license_type']) ? (string)$data['license_type'] : '');
		$to = $this->db->real_escape_string((string)$data['to_email']);
		$subject = $this->db->real_escape_string((string)$data['subject']);
		$tokenEsc = $this->db->real_escape_string($token);
		$now = time();
		$sql = 'INSERT INTO ' . $this->prefix . 'vbdl_license_mail
			(token, message_nodeid, starter_nodeid, customer_userid, customer_username, customer_email,
			 staff_userid, filedataid, lic_filename, license_type, to_email, subject, status, sent_dateline)
			VALUES (\'' . $tokenEsc . '\',' . $messageNode . ',' . $starter . ',' . $custId
			. ',\'' . $custUser . '\',\'' . $custEmail . '\',' . $staffId . ',' . $filedataid
			. ',\'' . $licName . '\',\'' . $licType . '\',\'' . $to . '\',\'' . $subject . '\',\'sent\',' . $now . ')';
		if (!$this->db->query($sql))
		{
			return array('error' => 'DB insert failed: ' . $this->db->error);
		}
		return array('token' => $token, 'id' => (int)$this->db->insert_id);
	}

	public function findByToken($token)
	{
		$token = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$token);
		if ($token === '')
		{
			return null;
		}
		$esc = $this->db->real_escape_string($token);
		$res = $this->db->query('SELECT * FROM ' . $this->prefix . 'vbdl_license_mail WHERE token=\'' . $esc . '\' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return $row;
		}
		return null;
	}

	public function extractTokenFromText($text)
	{
		if (preg_match('/\b(VBDL-LIC-[A-Z0-9\-]+)\b/i', (string)$text, $m))
		{
			return strtoupper($m[1]);
		}
		return '';
	}

	/**
	 * Post a Message Center reply with .src attachment for the tracked ticket.
	 */
	public function returnSrcToTicket(array $record, $filename, $bytes, $staffUserid = 0)
	{
		$filename = preg_replace('/[^\w.\-()+@]+/', '_', (string)$filename);
		if (!preg_match('/\.src$/i', $filename))
		{
			$filename .= '.src';
		}
		if ($bytes === '' || $bytes === null)
		{
			return array('error' => 'Empty .src file');
		}

		$parentId = (int)$record['message_nodeid'];
		if ($parentId < 1)
		{
			return array('error' => 'Missing message node');
		}

		// Prefer posting under the message that held the .lic; fallback starter.
		$starter = (int)$record['starter_nodeid'];
		if ($starter < 1)
		{
			$starter = $parentId;
		}

		$userid = (int)$staffUserid;
		if ($userid < 1)
		{
			$userid = (int)$record['staff_userid'];
		}
		if ($userid < 1)
		{
			$userid = 1;
		}

		$posted = $this->postPmReplyWithAttach($parentId, $starter, $userid, $filename, $bytes, $record);
		if (!empty($posted['error']))
		{
			return $posted;
		}

		// Reliable mirror for downloads (bypasses broken MC filedata/fetch when needed).
		$this->storeLicenseMirror($record['token'], 'src', $filename, $bytes);

		$tokenEsc = $this->db->real_escape_string($record['token']);
		$retName = $this->db->real_escape_string($filename);
		$retFd = (int)$posted['filedataid'];
		$retNode = (int)$posted['attach_nodeid'];
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_mail SET status=\'returned\', return_filename=\'' . $retName . '\', '
			. 'return_filedataid=' . $retFd . ', return_nodeid=' . $retNode . ', returned_dateline=' . $now
			. ', src_purged=0, src_purged_dateline=0'
			. ' WHERE token=\'' . $tokenEsc . '\''
		);

		// Make sure all admins/staff can open the ticket + download attaches in Message Center.
		$healed = $this->healTicketStaffAccess(array_merge($record, array(
			'return_nodeid' => $retNode,
			'message_nodeid' => $parentId,
			'starter_nodeid' => $starter,
		)));
		if (!empty($posted['text_nodeid']))
		{
			$this->ensureStaffParticipants((int)$posted['text_nodeid']);
		}
		$this->ensureStaffParticipants($retNode);

		return array(
			'ok' => true,
			'token' => $record['token'],
			'filename' => $filename,
			'filedataid' => $retFd,
			'attach_nodeid' => $retNode,
			'message_nodeid' => (int)$posted['text_nodeid'],
			'staff_healed' => !empty($healed['ok']) ? 1 : 0,
		);
	}

	/**
	 * Activation failed / text-only reply from license inbox → post into ticket as rejected.
	 */
	public function rejectReplyToTicket(array $record, $replyText, $staffUserid = 0)
	{
		$replyText = trim((string)$replyText);
		$replyText = preg_replace('/\r\n?/', "\n", $replyText);
		// Drop quoted mail noise / huge blobs
		if (strlen($replyText) > 20000)
		{
			$replyText = substr($replyText, 0, 20000) . "\n…";
		}
		if ($replyText === '')
		{
			return array('error' => 'Empty reply text');
		}
		if (!empty($record['status']) && ($record['status'] === 'returned' || $record['status'] === 'rejected'))
		{
			return array('ok' => true, 'skipped' => 1, 'reason' => 'already_' . $record['status']);
		}

		$parentId = (int)$record['message_nodeid'];
		$starter = (int)$record['starter_nodeid'];
		if ($parentId < 1)
		{
			return array('error' => 'Missing message node');
		}
		if ($starter < 1)
		{
			$starter = $parentId;
		}
		$userid = (int)$staffUserid;
		if ($userid < 1)
		{
			$userid = (int)$record['staff_userid'];
		}
		if ($userid < 1)
		{
			$userid = 1;
		}

		$title = 'License reply (not activated)';
		$rawtext = "License activation reply (no .src attachment)\n"
			. "Tracking: " . $record['token'] . "\n\n"
			. $replyText;
		$textNode = $this->postPmTextReply($parentId, $starter, $userid, $title, $rawtext);
		if ($textNode < 1)
		{
			return array('error' => 'Failed posting reply text: ' . $this->lastNodeError);
		}

		$tokenEsc = $this->db->real_escape_string($record['token']);
		$replyEsc = $this->db->real_escape_string($replyText);
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_mail SET status=\'rejected\', reply_text=\'' . $replyEsc . '\', '
			. 'returned_dateline=' . $now . ', return_nodeid=' . (int)$textNode
			. ' WHERE token=\'' . $tokenEsc . '\''
		);

		return array(
			'ok' => true,
			'token' => $record['token'],
			'status' => 'rejected',
			'text_nodeid' => $textNode,
			'reply_preview' => substr($replyText, 0, 200),
		);
	}

	/**
	 * After retention window, delete .src binary but keep filename + download report.
	 */
	public function purgeExpiredSrcFiles($limit = 50)
	{
		$days = $this->srcRetentionDays();
		$cutoff = time() - ($days * 86400);
		$limit = max(1, min(200, (int)$limit));
		$p = $this->prefix;
		$res = $this->db->query(
			'SELECT token, return_filedataid, return_nodeid, return_filename, download_count, returned_dateline '
			. 'FROM ' . $p . 'vbdl_license_mail '
			. 'WHERE status=\'returned\' AND src_purged=0 AND return_filedataid>0 '
			. 'AND returned_dateline>0 AND returned_dateline<' . (int)$cutoff
			. ' ORDER BY returned_dateline ASC LIMIT ' . $limit
		);
		$purged = array();
		if (!$res)
		{
			return $purged;
		}
		while ($row = $res->fetch_assoc())
		{
			$this->syncDownloadCountFromAttach($row);
			$filedataid = (int)$row['return_filedataid'];
			$nodeid = (int)$row['return_nodeid'];
			if ($filedataid > 0)
			{
				// Wipe blob; keep filedata row so attach metadata/filename still resolve if needed.
				$this->db->query(
					'UPDATE ' . $p . 'filedata SET filedata=\'\', filesize=0 WHERE filedataid=' . $filedataid
				);
				$this->deleteAttachFileFromDisk($filedataid);
			}
			if ($nodeid > 0)
			{
				// Soft-hide attach visibility while keeping counter/filename on the row.
				$this->db->query(
					'UPDATE ' . $p . 'attach SET visible=0 WHERE nodeid=' . $nodeid
				);
			}
			$this->deleteLicenseMirror((string)$row['token'], 'src');
			$tokenEsc = $this->db->real_escape_string((string)$row['token']);
			$now = time();
			$this->db->query(
				'UPDATE ' . $p . 'vbdl_license_mail SET src_purged=1, src_purged_dateline=' . $now
				. ', return_filedataid=0'
				. ' WHERE token=\'' . $tokenEsc . '\''
			);
			$purged[] = array(
				'token' => $row['token'],
				'filename' => $row['return_filename'],
				'downloads' => (int)$row['download_count'],
				'returned_dateline' => (int)$row['returned_dateline'],
			);
		}
		return $purged;
	}

	protected function deleteAttachFileFromDisk($filedataid)
	{
		$filedataid = (int)$filedataid;
		if ($filedataid < 1)
		{
			return;
		}
		$forumRoot = realpath(dirname(__FILE__) . '/../../../../..');
		if ($forumRoot === false)
		{
			$forumRoot = '/home/hddrecov/public_html/forum';
		}
		$roots = array(
			$forumRoot . '/core/attachment',
			$forumRoot . '/attachment',
		);
		$config = array();
		$cfg = $forumRoot . '/core/includes/config.php';
		if (is_file($cfg))
		{
			include $cfg;
		}
		if (!empty($config['Misc']['attachmentpath']))
		{
			$ap = rtrim((string)$config['Misc']['attachmentpath'], '/');
			if ($ap !== '' && isset($ap[0]) && $ap[0] !== '/')
			{
				$ap = rtrim($forumRoot, '/') . '/' . ltrim($ap, './');
			}
			array_unshift($roots, $ap);
		}
		foreach (array_unique($roots) as $root)
		{
			if ($root === '' || !is_dir($root))
			{
				continue;
			}
			$matches = glob($root . '/*/' . $filedataid . '.attach');
			if (!$matches)
			{
				$matches = glob($root . '/*/*/' . $filedataid . '.attach');
			}
			if (!$matches)
			{
				$direct = $root . '/' . $filedataid . '.attach';
				if (is_file($direct))
				{
					$matches = array($direct);
				}
			}
			if (is_array($matches))
			{
				foreach ($matches as $path)
				{
					@unlink($path);
				}
			}
		}
	}

	public function postTextToTicket($parentId, $starterId, $userid, $title, $rawtext)
	{
		return $this->postPmTextReply((int)$parentId, (int)$starterId, (int)$userid, (string)$title, (string)$rawtext);
	}

	protected function postPmTextReply($parentId, $starterId, $userid, $title, $rawtext)
	{
		$parentId = (int)$parentId;
		$starterId = (int)$starterId;
		$userid = (int)$userid;
		$p = $this->prefix;
		$now = time();
		$textType = $this->contentTypeId('Text');
		if ($textType < 1 || $parentId < 1)
		{
			return 0;
		}
		$parent = null;
		$resP = $this->db->query('SELECT routeid FROM ' . $p . 'node WHERE nodeid=' . $parentId . ' LIMIT 1');
		if ($resP)
		{
			$parent = $resP->fetch_assoc();
		}
		$routeid = $parent && !empty($parent['routeid']) ? (int)$parent['routeid'] : 63;
		$author = $this->usernameById($userid);
		$textNode = $this->insertNode(array(
			'routeid' => $routeid,
			'userid' => $userid,
			'authorname' => $author,
			'parentid' => $parentId,
			'starter' => $starterId > 0 ? $starterId : $parentId,
			'contenttypeid' => $textType,
			'title' => $title,
			'htmltitle' => $title,
			'urlident' => 'license-reply-text',
			'created' => $now,
			'lastcontent' => $now,
			'lastcontentid' => 0,
			'lastcontentauthor' => $author,
			'lastauthorid' => $userid,
			'lastprefixid' => '',
			'publishdate' => $now,
			'showpublished' => 1,
			'showopen' => 1,
			'open' => 1,
			'approved' => 1,
			'showapproved' => 1,
			'ipaddress' => '',
			'CRC32' => (string)sprintf('%u', crc32($rawtext)),
			'prefixid' => '',
			'inlist' => 1,
			'protected' => 1,
			'nodeoptions' => 138,
		));
		if ($textNode < 1)
		{
			return 0;
		}
		$this->ensureClosure($textNode, $parentId, $now);
		$this->db->query(
			'INSERT INTO ' . $p . 'text (nodeid, rawtext, htmltitle) VALUES ('
			. $textNode . ',\'' . $this->db->real_escape_string($rawtext) . '\',\''
			. $this->db->real_escape_string($title) . '\')'
		);
		$this->db->query(
			'UPDATE ' . $p . 'node SET lastcontent=' . $now . ', lastcontentid=' . $textNode
			. ', lastcontentauthor=\'' . $this->db->real_escape_string($author) . '\''
			. ', lastauthorid=' . $userid
			. ', totalcount=totalcount+1, textcount=textcount+1'
			. ' WHERE nodeid=' . $parentId
		);
		$this->clearNodeCaches(array($parentId, $textNode));
		return $textNode;
	}

	protected function postPmReplyWithAttach($parentId, $starterId, $userid, $filename, $bytes, array $record)
	{
		$p = $this->prefix;
		$now = time();
		$this->ensureAttachmentTypes();

		$customer = (string)$record['customer_username'];
		$customerId = (int)$record['customer_userid'];
		if ($customerId < 1)
		{
			$resC = $this->db->query('SELECT userid, authorname FROM ' . $p . 'node WHERE nodeid=' . (int)$parentId . ' LIMIT 1');
			if ($resC && ($rowC = $resC->fetch_assoc()))
			{
				$customerId = (int)$rowC['userid'];
				if ($customer === '' && !empty($rowC['authorname']))
				{
					$customer = (string)$rowC['authorname'];
				}
			}
		}
		// Attach owner MUST match filedata.userid AND filesystem path segment.
		$attachUserid = $customerId > 0 ? $customerId : (int)$userid;
		$attachAuthor = $customer !== '' ? $customer : $this->usernameById($attachUserid);
		$staffAuthor = $this->usernameById((int)$userid);

		$textType = $this->contentTypeId('Text');
		$attachType = $this->contentTypeId('Attach');
		if ($textType < 1 || $attachType < 1)
		{
			return array('error' => 'contenttype Text/Attach missing');
		}

		$parent = null;
		$resP = $this->db->query('SELECT routeid FROM ' . $p . 'node WHERE nodeid=' . (int)$parentId . ' LIMIT 1');
		if ($resP)
		{
			$parent = $resP->fetch_assoc();
		}
		$routeid = $parent && !empty($parent['routeid']) ? (int)$parent['routeid'] : 63;

		$rawtext = 'Activated SeDiv license (.src) received for user ' . $attachAuthor . ".\n"
			. 'Tracking: ' . $record['token'] . "\n"
			. 'File: ' . $filename . "\n"
			. 'Download: /vbdlmanager/pm_lic_download.php?token=' . rawurlencode((string)$record['token']) . '&kind=src';

		$title = 'Activated license (.src)';
		$textNode = $this->insertNode(array(
			'routeid' => $routeid,
			'userid' => (int)$userid,
			'authorname' => $staffAuthor,
			'parentid' => (int)$parentId,
			'starter' => (int)$starterId,
			'contenttypeid' => $textType,
			'title' => $title,
			'htmltitle' => $title,
			'urlident' => 'activated-license-src',
			'created' => $now,
			'lastcontent' => $now,
			'lastcontentid' => 0,
			'lastcontentauthor' => $staffAuthor,
			'lastauthorid' => (int)$userid,
			'lastprefixid' => '',
			'publishdate' => $now,
			'showpublished' => 1,
			'showopen' => 1,
			'open' => 1,
			'approved' => 1,
			'showapproved' => 1,
			'ipaddress' => '',
			'CRC32' => (string)sprintf('%u', crc32($rawtext)),
			'prefixid' => '',
			'inlist' => 1,
			'protected' => 1,
			'nodeoptions' => 138,
		));
		if ($textNode < 1)
		{
			$textNode = (int)$parentId;
		}
		else
		{
			$this->ensureClosure((int)$textNode, (int)$parentId, $now);
			$this->db->query(
				'INSERT INTO ' . $p . 'text (nodeid, rawtext, htmltitle) VALUES ('
				. $textNode . ',\'' . $this->db->real_escape_string($rawtext) . '\',\''
				. $this->db->real_escape_string($title) . '\')'
			);
		}

		// Prefer shared attach helper (consistent userid + disk path + visible=1).
		$attached = $this->attachFileToTicket((int)$parentId, (int)$starterId, $attachUserid, $filename, $bytes);
		if (!empty($attached['error']))
		{
			return $attached;
		}

		$this->db->query(
			'UPDATE ' . $p . 'node SET lastcontent=' . $now
			. ', lastcontentauthor=\'' . $this->db->real_escape_string($staffAuthor) . '\''
			. ', lastauthorid=' . (int)$userid
			. ', hasphoto=1'
			. ', totalcount=totalcount+1, textcount=textcount+1'
			. ' WHERE nodeid=' . (int)$parentId
		);
		$this->clearNodeCaches(array((int)$parentId, (int)$textNode, (int)$attached['attach_nodeid']));

		return array(
			'filedataid' => (int)$attached['filedataid'],
			'text_nodeid' => (int)$textNode,
			'attach_nodeid' => (int)$attached['attach_nodeid'],
		);
	}

	protected function postPmReplyWithAttachNoBlob($parentId, $starterId, $userid, $filename, $bytes, array $record)
	{
		return array('error' => 'filedata blob insert unavailable on this host');
	}

	protected function writeAttachFile($filedataid, $userid, $bytes)
	{
		$filedataid = (int)$filedataid;
		$userid = (int)$userid;
		$roots = array();
		global $vbulletin;
		if (!empty($vbulletin->config['Misc']['attachmentpath']))
		{
			$roots[] = rtrim((string)$vbulletin->config['Misc']['attachmentpath'], '/');
		}
		// LicenseMail.php → library → vbdlmanager → packages → core → forum root
		$forumRoot = realpath(dirname(__FILE__) . '/../../../../..');
		if ($forumRoot === false)
		{
			$forumRoot = '/home/hddrecov/public_html/forum';
		}
		$config = array();
		$cfg = $forumRoot . '/core/includes/config.php';
		if (is_file($cfg))
		{
			include $cfg;
		}
		if (!empty($config['Misc']['attachmentpath']))
		{
			$ap = rtrim((string)$config['Misc']['attachmentpath'], '/');
			if ($ap !== '' && isset($ap[0]) && $ap[0] !== '/')
			{
				$ap = rtrim($forumRoot, '/') . '/' . ltrim($ap, './');
			}
			array_unshift($roots, $ap);
		}
		$roots[] = $forumRoot . '/core/attachment';
		$roots[] = $forumRoot . '/attachment';
		$userSeg = ($userid > 0) ? implode('/', str_split((string)$userid)) : '0';
		foreach (array_unique($roots) as $root)
		{
			if ($root === '')
			{
				continue;
			}
			$dir = $root . '/' . $userSeg;
			if (!is_dir($dir))
			{
				@mkdir($dir, 0755, true);
			}
			$path = $dir . '/' . $filedataid . '.attach';
			$n = @file_put_contents($path, $bytes);
			if ($n !== false && $n > 0)
			{
				@chmod($path, 0644);
				return $path;
			}
		}
		return '';
	}

	/**
	 * Ensure .src / .lic are allowed attachment extensions (vB "Invalid File Specified" otherwise).
	 */
	public function ensureAttachmentTypes()
	{
		static $done = false;
		if ($done)
		{
			return;
		}
		$done = true;
		$p = $this->prefix;
		foreach (array('src' => 'application/octet-stream', 'lic' => 'application/octet-stream', 'txt' => 'text/plain') as $ext => $mime)
		{
			$extEsc = $this->db->real_escape_string($ext);
			$mimeEsc = $this->db->real_escape_string($mime);
			$res = @$this->db->query('SELECT extension FROM ' . $p . 'attachmenttype WHERE extension=\'' . $extEsc . '\' LIMIT 1');
			if ($res && $res->fetch_row())
			{
				continue;
			}
			// vB4/5/6 schemas vary; try common column sets.
			@$this->db->query(
				'INSERT INTO ' . $p . 'attachmenttype (extension, mimetype, size, width, height, enabled) VALUES ('
				. '\'' . $extEsc . '\',\'' . $mimeEsc . '\',10485760,0,0,1)'
			);
			if ($this->db->error)
			{
				@$this->db->query(
					'INSERT INTO ' . $p . 'attachmenttype (extension, mimetype) VALUES (\'' . $extEsc . '\',\'' . $mimeEsc . '\')'
				);
			}
		}
	}

	public function licenseMirrorDir($token = '')
	{
		$forumRoot = realpath(dirname(__FILE__) . '/../../../../..');
		if ($forumRoot === false)
		{
			$forumRoot = '/home/hddrecov/public_html/forum';
		}
		$base = $forumRoot . '/vbdlmanager/storage/license';
		if (!is_dir($base))
		{
			@mkdir($base, 0755, true);
		}
		$ht = $base . '/.htaccess';
		if (!is_file($ht))
		{
			@file_put_contents($ht, "Require all denied\nDeny from all\n");
		}
		$token = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$token);
		if ($token === '')
		{
			return $base;
		}
		$dir = $base . '/' . $token;
		if (!is_dir($dir))
		{
			@mkdir($dir, 0755, true);
		}
		return $dir;
	}

	public function storeLicenseMirror($token, $kind, $filename, $bytes)
	{
		$dir = $this->licenseMirrorDir($token);
		$kind = ($kind === 'lic') ? 'lic' : 'src';
		$ext = ($kind === 'lic') ? 'lic' : 'src';
		$safe = preg_replace('/[^\w.\-()+@]+/', '_', (string)$filename);
		if ($safe === '' || !preg_match('/\.' . $ext . '$/i', $safe))
		{
			$safe = ($kind === 'lic' ? 'License' : 'Source') . '.' . $ext;
		}
		$path = $dir . '/' . $safe;
		$n = @file_put_contents($path, $bytes);
		if ($n === false || $n < 1)
		{
			return '';
		}
		@chmod($path, 0640);
		@file_put_contents($dir . '/' . $kind . '.name', $safe);
		return $path;
	}

	public function licenseMirrorExists($token, $kind = 'src')
	{
		$dir = $this->licenseMirrorDir($token);
		$kind = ($kind === 'lic') ? 'lic' : 'src';
		$nameFile = $dir . '/' . $kind . '.name';
		if (is_file($nameFile))
		{
			$name = trim((string)@file_get_contents($nameFile));
			if ($name !== '' && is_file($dir . '/' . $name) && filesize($dir . '/' . $name) > 0)
			{
				return true;
			}
		}
		$matches = glob($dir . '/*.' . $kind);
		return !empty($matches);
	}

	public function deleteLicenseMirror($token, $kind = 'src')
	{
		$dir = $this->licenseMirrorDir($token);
		$kind = ($kind === 'lic') ? 'lic' : 'src';
		$nameFile = $dir . '/' . $kind . '.name';
		if (is_file($nameFile))
		{
			$name = trim((string)@file_get_contents($nameFile));
			if ($name !== '' && is_file($dir . '/' . $name))
			{
				@unlink($dir . '/' . $name);
			}
			@unlink($nameFile);
		}
		foreach (glob($dir . '/*.' . $kind) ?: array() as $f)
		{
			@unlink($f);
		}
	}

	/**
	 * Load returned .src (or sent .lic) bytes for authorized download.
	 * Tries mirror → DB blob → filesystem paths (including legacy staff/customer mismatch).
	 */
	public function loadLicenseBytes(array $record, $kind = 'src')
	{
		$kind = ($kind === 'lic') ? 'lic' : 'src';
		$p = $this->prefix;
		$filename = $kind === 'src'
			? (isset($record['return_filename']) ? (string)$record['return_filename'] : 'Source.src')
			: (isset($record['lic_filename']) ? (string)$record['lic_filename'] : 'License.lic');
		if ($kind === 'src' && !empty($record['src_purged']) && !$this->licenseMirrorExists($record['token'], 'src'))
		{
			return array('error' => 'This .src was purged after the retention period. Ask support to re-upload it.');
		}

		// 1) Mirror store (preferred)
		$dir = $this->licenseMirrorDir(isset($record['token']) ? $record['token'] : '');
		$nameFile = $dir . '/' . $kind . '.name';
		$candidates = array();
		if (is_file($nameFile))
		{
			$n = trim((string)@file_get_contents($nameFile));
			if ($n !== '')
			{
				$candidates[] = $dir . '/' . $n;
			}
		}
		foreach (glob($dir . '/*.' . $kind) ?: array() as $f)
		{
			$candidates[] = $f;
		}
		foreach ($candidates as $path)
		{
			if (is_file($path) && filesize($path) > 0)
			{
				$bytes = @file_get_contents($path);
				if ($bytes !== false && $bytes !== '')
				{
					return array('ok' => true, 'bytes' => $bytes, 'filename' => basename($path), 'via' => 'mirror');
				}
			}
		}

		$filedataid = $kind === 'src'
			? (int)(isset($record['return_filedataid']) ? $record['return_filedataid'] : 0)
			: (int)(isset($record['filedataid']) ? $record['filedataid'] : 0);
		if ($filedataid < 1 && $kind === 'src' && !empty($record['return_nodeid']))
		{
			$res = $this->db->query('SELECT filedataid, filename FROM ' . $p . 'attach WHERE nodeid=' . (int)$record['return_nodeid'] . ' LIMIT 1');
			if ($res && ($row = $res->fetch_assoc()))
			{
				$filedataid = (int)$row['filedataid'];
				if (!empty($row['filename']))
				{
					$filename = (string)$row['filename'];
				}
			}
		}
		if ($filedataid < 1)
		{
			return array('error' => 'File data not found for this ticket');
		}

		$res = $this->db->query(
			'SELECT filedataid, userid, filesize, extension, filedata, LENGTH(filedata) AS bloblen FROM ' . $p . 'filedata WHERE filedataid=' . $filedataid . ' LIMIT 1'
		);
		if (!$res || !($fd = $res->fetch_assoc()))
		{
			return array('error' => 'filedata row missing');
		}
		if (!empty($fd['filedata']) && (int)$fd['bloblen'] > 0)
		{
			return array('ok' => true, 'bytes' => $fd['filedata'], 'filename' => $filename, 'via' => 'db_blob');
		}

		// Filesystem: try declared userid + customer + staff (legacy mismatch fix).
		$userids = array((int)$fd['userid']);
		if (!empty($record['customer_userid']))
		{
			$userids[] = (int)$record['customer_userid'];
		}
		if (!empty($record['staff_userid']))
		{
			$userids[] = (int)$record['staff_userid'];
		}
		$userids[] = 1;
		$userids = array_values(array_unique(array_filter($userids)));
		foreach ($userids as $uid)
		{
			$path = $this->findAttachFileOnDisk($filedataid, $uid);
			if ($path !== '')
			{
				$bytes = @file_get_contents($path);
				if ($bytes !== false && $bytes !== '')
				{
					// Heal mirror for next time.
					$this->storeLicenseMirror(isset($record['token']) ? $record['token'] : '', $kind, $filename, $bytes);
					return array('ok' => true, 'bytes' => $bytes, 'filename' => $filename, 'via' => 'disk:' . $path);
				}
			}
		}
		return array('error' => 'Attachment bytes missing on disk (Invalid File Specified). Re-upload the .src via Active License SeDiv.');
	}

	public function bumpDownloadCount(array $record)
	{
		$tokenEsc = $this->db->real_escape_string((string)$record['token']);
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_mail SET download_count=download_count+1, last_download_dateline=' . $now
			. ' WHERE token=\'' . $tokenEsc . '\''
		);
		if (!empty($record['return_nodeid']))
		{
			$this->db->query(
				'UPDATE ' . $this->prefix . 'attach SET counter=counter+1 WHERE nodeid=' . (int)$record['return_nodeid']
			);
		}
	}

	protected function findAttachFileOnDisk($filedataid, $userid)
	{
		$filedataid = (int)$filedataid;
		$userid = (int)$userid;
		$forumRoot = realpath(dirname(__FILE__) . '/../../../../..');
		if ($forumRoot === false)
		{
			$forumRoot = '/home/hddrecov/public_html/forum';
		}
		$roots = array($forumRoot . '/core/attachment', $forumRoot . '/attachment');
		$config = array();
		$cfg = $forumRoot . '/core/includes/config.php';
		if (is_file($cfg))
		{
			include $cfg;
		}
		if (!empty($config['Misc']['attachmentpath']))
		{
			$ap = rtrim((string)$config['Misc']['attachmentpath'], '/');
			if ($ap !== '' && isset($ap[0]) && $ap[0] !== '/')
			{
				$ap = rtrim($forumRoot, '/') . '/' . ltrim($ap, './');
			}
			array_unshift($roots, $ap);
		}
		$userSeg = ($userid > 0) ? implode('/', str_split((string)$userid)) : '0';
		foreach (array_unique($roots) as $root)
		{
			if ($root === '' || !is_dir($root))
			{
				continue;
			}
			$path = $root . '/' . $userSeg . '/' . $filedataid . '.attach';
			if (is_file($path) && filesize($path) > 0)
			{
				return $path;
			}
			$matches = glob($root . '/*/' . $filedataid . '.attach') ?: array();
			$matches = array_merge($matches, glob($root . '/*/*/' . $filedataid . '.attach') ?: array());
			foreach ($matches as $m)
			{
				if (is_file($m) && filesize($m) > 0)
				{
					return $m;
				}
			}
		}
		return '';
	}

	protected function contentTypeId($class)
	{
		$class = $this->db->real_escape_string($class);
		$res = $this->db->query('SELECT contenttypeid FROM ' . $this->prefix . 'contenttype WHERE class=\'' . $class . '\' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return (int)$row['contenttypeid'];
		}
		return 0;
	}

	protected function usernameById($userid)
	{
		$userid = (int)$userid;
		$res = $this->db->query('SELECT username FROM ' . $this->prefix . 'user WHERE userid=' . $userid . ' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return (string)$row['username'];
		}
		return 'System';
	}

	protected function insertNode(array $f)
	{
		$cols = array();
		$vals = array();
		foreach ($f as $k => $v)
		{
			$cols[] = '`' . preg_replace('/[^a-z0-9_]/i', '', $k) . '`';
			if (is_int($v))
			{
				$vals[] = (string)(int)$v;
			}
			else
			{
				$vals[] = '\'' . $this->db->real_escape_string((string)$v) . '\'';
			}
		}
		$sql = 'INSERT INTO ' . $this->prefix . 'node (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
		if (!$this->db->query($sql))
		{
			$this->lastNodeError = $this->db->error;
			return 0;
		}
		return (int)$this->db->insert_id;
	}

	/**
	 * Mirror vB node closure rows so Message Center can discover children.
	 */
	protected function ensureClosure($nodeid, $parentid, $publishdate = 0)
	{
		$nodeid = (int)$nodeid;
		$parentid = (int)$parentid;
		$publishdate = (int)$publishdate;
		if ($nodeid < 1)
		{
			return;
		}
		if ($publishdate < 1)
		{
			$publishdate = time();
		}
		$p = $this->prefix;
		$this->db->query(
			'INSERT IGNORE INTO ' . $p . 'closure (parent, child, depth, displayorder, publishdate) VALUES ('
			. $nodeid . ',' . $nodeid . ',0,0,' . $publishdate . ')'
		);
		if ($parentid > 0)
		{
			$this->db->query(
				'INSERT IGNORE INTO ' . $p . 'closure (parent, child, depth, displayorder, publishdate) '
				. 'SELECT parent, ' . $nodeid . ', depth+1, 0, ' . $publishdate
				. ' FROM ' . $p . 'closure WHERE child=' . $parentid
			);
		}
	}

	protected function clearNodeCaches(array $nodeids)
	{
		$nodeids = array_values(array_unique(array_filter(array_map('intval', $nodeids))));
		if (!$nodeids)
		{
			return;
		}
		try
		{
			if (class_exists('vB_Cache', false))
			{
				$events = array();
				foreach ($nodeids as $id)
				{
					$events[] = 'nodeChg_' . $id;
				}
				vB_Cache::allCacheEvent($events);
			}
			if (class_exists('vB_Library', false))
			{
				$lib = vB_Library::instance('node');
				if ($lib && method_exists($lib, 'clearCacheEvents'))
				{
					$lib->clearCacheEvents($nodeids);
				}
				if ($lib && method_exists($lib, 'clearChildCache'))
				{
					$lib->clearChildCache($nodeids[0]);
				}
			}
		}
		catch (Throwable $e)
		{
		}
	}
}
