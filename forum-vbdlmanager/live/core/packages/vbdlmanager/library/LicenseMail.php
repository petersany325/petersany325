<?php
/**
 * SeDiv VIP license email tracking + return (.src) into Message Center tickets.
 */
class vbdl_LicenseMail
{
	/** @var object */
	protected $db;
	/** @var string */
	protected $prefix;
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_Acl */
	protected $acl;
	/** @var string */
	protected $lastNodeError = '';

	public function __construct($db, $prefix, vbdl_Repository $repo, vbdl_Acl $acl)
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
		$this->ensureSeenTable();
	}

	/**
	 * Dedupe inbound RFC822 Message-IDs so the same reply is never applied twice.
	 */
	public function ensureSeenTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}vbdl_license_mail_seen (
			message_id VARCHAR(255) NOT NULL,
			token VARCHAR(64) NOT NULL DEFAULT '',
			kind VARCHAR(32) NOT NULL DEFAULT '',
			seen_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (message_id),
			KEY token (token),
			KEY seen_dateline (seen_dateline)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@$this->db->query($sql);
	}

	public function normalizeMessageId($messageId)
	{
		$id = trim((string)$messageId);
		$id = trim($id, "<> \t\r\n");
		$id = preg_replace('/\s+/', '', $id);
		if (strlen($id) > 240)
		{
			$id = substr($id, 0, 240);
		}
		return $id;
	}

	public function hasSeenMessageId($messageId)
	{
		$id = $this->normalizeMessageId($messageId);
		if ($id === '')
		{
			return false;
		}
		$esc = $this->db->real_escape_string($id);
		$res = @$this->db->query(
			'SELECT 1 FROM ' . $this->prefix . 'vbdl_license_mail_seen WHERE message_id=\'' . $esc . '\' LIMIT 1'
		);
		return ($res && $res->fetch_row());
	}

	public function markSeenMessageId($messageId, $token = '', $kind = '')
	{
		$id = $this->normalizeMessageId($messageId);
		if ($id === '')
		{
			return false;
		}
		$esc = $this->db->real_escape_string($id);
		$tokenEsc = $this->db->real_escape_string(preg_replace('/[^A-Za-z0-9\-]/', '', (string)$token));
		$kindEsc = $this->db->real_escape_string(preg_replace('/[^a-z0-9_\-]/i', '', (string)$kind));
		$now = time();
		return (bool)@$this->db->query(
			'INSERT IGNORE INTO ' . $this->prefix . 'vbdl_license_mail_seen (message_id, token, kind, seen_dateline) VALUES ('
			. '\'' . $esc . '\',\'' . $tokenEsc . '\',\'' . $kindEsc . '\',' . $now . ')'
		);
	}

	/**
	 * Persist outbound Message-ID / tracking headers into ticket meta JSON.
	 */
	public function storeOutboundMeta($token, array $extra)
	{
		$token = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$token);
		if ($token === '')
		{
			return false;
		}
		$esc = $this->db->real_escape_string($token);
		$res = $this->db->query(
			'SELECT meta FROM ' . $this->prefix . 'vbdl_license_mail WHERE token=\'' . $esc . '\' LIMIT 1'
		);
		$meta = array();
		if ($res && ($row = $res->fetch_assoc()) && !empty($row['meta']))
		{
			$decoded = json_decode((string)$row['meta'], true);
			if (is_array($decoded))
			{
				$meta = $decoded;
			}
		}
		foreach ($extra as $k => $v)
		{
			$meta[$k] = $v;
		}
		$json = $this->db->real_escape_string(json_encode($meta));
		return (bool)$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_mail SET meta=\'' . $json . '\' WHERE token=\'' . $esc . '\''
		);
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
		$row['src_available'] = (
			!empty($row['status']) && $row['status'] === 'returned'
			&& empty($row['src_purged'])
			&& !empty($row['return_filedataid'])
		) ? 1 : 0;
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
	 * Attach a binary file (.lic / .src / receipt) under a PM ticket so MC lists it.
	 * Prefers native vBulletin content_attach API; falls back to direct schema write.
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

		$native = $this->attachFileToTicketNative($parentId, $starterId, $ownerUserid, $filename, $bytes);
		if (!empty($native['ok']))
		{
			return $native;
		}

		return $this->attachFileToTicketLegacy($parentId, $starterId, $ownerUserid, $filename, $bytes);
	}

	/**
	 * Native vBulletin attach path (content_attach library/API).
	 */
	protected function attachFileToTicketNative($parentId, $starterId, $ownerUserid, $filename, $bytes)
	{
		try
		{
			if (!class_exists('vB_Library') && !class_exists('vB_Api'))
			{
				return array('error' => 'vB library unavailable');
			}
			$tmp = tempnam(sys_get_temp_dir(), 'vbdl');
			if ($tmp === false)
			{
				return array('error' => 'temp file failed');
			}
			file_put_contents($tmp, $bytes);
			$fileinfo = array(
				'name' => $filename,
				'size' => strlen($bytes),
				'tmp_name' => $tmp,
				'error' => 0,
				'type' => 'application/octet-stream',
			);
			$result = null;
			if (class_exists('vB_Library'))
			{
				$lib = vB_Library::instance('content_attach');
				if ($lib && method_exists($lib, 'add'))
				{
					$data = array(
						'parentid' => (int)$parentId,
						'userid' => (int)$ownerUserid,
						'filedata' => $fileinfo,
						'filename' => $filename,
					);
					$result = $lib->add($data, array('bypassPerms' => true, 'skipFloodCheck' => true));
				}
				elseif ($lib && method_exists($lib, 'uploadAttachment'))
				{
					$result = $lib->uploadAttachment((int)$parentId, $fileinfo, array('bypassPerms' => true));
				}
			}
			if ($result === null && class_exists('vB_Api'))
			{
				$api = vB_Api::instanceInternal('content_attach');
				if ($api && method_exists($api, 'add'))
				{
					$result = $api->add(array(
						'parentid' => (int)$parentId,
						'filedata' => $fileinfo,
						'filename' => $filename,
					), array('bypassPerms' => true));
				}
			}
			@unlink($tmp);
			if ($result === null)
			{
				return array('error' => 'content_attach API missing');
			}
			if (is_array($result) && !empty($result['errors']))
			{
				return array('error' => 'content_attach failed: ' . json_encode($result['errors']));
			}
			$nodeid = 0;
			$filedataid = 0;
			if (is_numeric($result))
			{
				$nodeid = (int)$result;
			}
			elseif (is_array($result))
			{
				$nodeid = !empty($result['nodeid']) ? (int)$result['nodeid'] : 0;
				$filedataid = !empty($result['filedataid']) ? (int)$result['filedataid'] : 0;
			}
			if ($nodeid < 1 && $filedataid < 1)
			{
				return array('error' => 'content_attach returned empty');
			}
			if ($filedataid < 1 && $nodeid > 0)
			{
				$res = $this->db->query('SELECT filedataid FROM ' . $this->prefix . 'attach WHERE nodeid=' . $nodeid . ' LIMIT 1');
				if ($res && ($row = $res->fetch_assoc()))
				{
					$filedataid = (int)$row['filedataid'];
				}
			}
			$this->clearNodeCaches(array($parentId, $nodeid));
			return array(
				'ok' => true,
				'filedataid' => $filedataid,
				'attach_nodeid' => $nodeid,
				'via' => 'vbulletin_native',
			);
		}
		catch (Throwable $e)
		{
			return array('error' => 'native attach exception: ' . $e->getMessage());
		}
	}

	/**
	 * Legacy direct filedata/node/attach writer (fallback).
	 */
	protected function attachFileToTicketLegacy($parentId, $starterId, $ownerUserid, $filename, $bytes)
	{
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
			'via' => 'legacy',
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
		$all = $this->extractAllTokensFromText($text);
		return $all ? $all[0] : '';
	}

	/**
	 * All VBDL-LIC tokens in a message (order preserved, unique).
	 * Replies that quote several tickets must not stop at the first (already returned) token.
	 */
	public function extractAllTokensFromText($text)
	{
		$out = array();
		if (!preg_match_all('/\b(VBDL-LIC-[A-Z0-9\-]+)\b/i', (string)$text, $mm))
		{
			return $out;
		}
		foreach ($mm[1] as $tok)
		{
			$tok = strtoupper($tok);
			if (!in_array($tok, $out, true))
			{
				$out[] = $tok;
			}
		}
		return $out;
	}

	/**
	 * Match a reply subject (e.g. Re: Subject license SeHGST imager) to a still-sent ticket.
	 * Only returns a row when exactly ONE open "sent" ticket matches — never guess among same-day duplicates.
	 */
	public function findSentBySubject($subject)
	{
		$subject = trim(preg_replace('/^(?:Re|Fw|Fwd|AW|SV|Antw)\s*:\s*/i', '', (string)$subject));
		// Prefer explicit token in subject when present (high confidence).
		if (preg_match('/\b(VBDL-LIC-[A-Z0-9\-]+)\b/i', $subject, $tm))
		{
			$byTok = $this->findByToken(strtoupper($tm[1]));
			if ($byTok && !empty($byTok['status']) && $byTok['status'] === 'sent')
			{
				return $byTok;
			}
		}
		$subject = trim(preg_replace('/\s*\[VBDL-LIC-[A-Z0-9\-]+\]\s*/i', ' ', $subject));
		$subject = trim(preg_replace('/\s+/', ' ', $subject));
		if ($subject === '')
		{
			return null;
		}

		// Prefer exact known product subjects.
		$base = '';
		foreach ($this->licenseTypes() as $t)
		{
			if (stripos($subject, $t['subject']) !== false)
			{
				$base = $t['subject'];
				break;
			}
		}
		if ($base === '')
		{
			$base = $subject;
		}

		$esc = $this->db->real_escape_string($base);
		$res = $this->db->query(
			'SELECT * FROM ' . $this->prefix . 'vbdl_license_mail '
			. 'WHERE status=\'sent\' AND ('
			. 'subject=\'' . $esc . '\' OR subject LIKE \'' . $esc . ' [%\' OR subject LIKE \'%' . $esc . '%\''
			. ') ORDER BY id DESC LIMIT 3'
		);
		$rows = array();
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$rows[] = $row;
			}
		}
		// Ambiguous: multiple open same-product tickets — refuse subject fallback.
		if (count($rows) === 1)
		{
			return $rows[0];
		}
		return null;
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

		$tokenEsc = $this->db->real_escape_string($record['token']);
		$retName = $this->db->real_escape_string($filename);
		$retFd = (int)$posted['filedataid'];
		$retNode = (int)$posted['attach_nodeid'];
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_mail SET status=\'returned\', return_filename=\'' . $retName . '\', '
			. 'return_filedataid=' . $retFd . ', return_nodeid=' . $retNode . ', returned_dateline=' . $now
			. ' WHERE token=\'' . $tokenEsc . '\''
		);

		return array(
			'ok' => true,
			'token' => $record['token'],
			'filename' => $filename,
			'filedataid' => $retFd,
			'attach_nodeid' => $retNode,
			'message_nodeid' => (int)$posted['text_nodeid'],
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
		// vB filehash column is MD5 (32 chars), not SHA1.
		$hash = md5($bytes);
		$size = strlen($bytes);
		$ext = 'src';

		$filedataid = 0;
		// Large .src files: metadata in DB + bytes on filesystem (userid digit path).
		if ($size <= 512000)
		{
			$hex = bin2hex($bytes);
			$sqlFd = 'INSERT INTO ' . $p . 'filedata (userid, dateline, filehash, filesize, extension, filedata) VALUES ('
				. (int)$userid . ',' . (int)$now . ',\'' . $this->db->real_escape_string($hash) . '\','
				. (int)$size . ',\'' . $this->db->real_escape_string($ext) . '\',UNHEX(\'' . $hex . '\'))';
			if ($this->db->query($sqlFd))
			{
				$filedataid = (int)$this->db->insert_id;
			}
		}
		if ($filedataid < 1)
		{
			$sqlFd = 'INSERT INTO ' . $p . 'filedata (userid, dateline, filehash, filesize, extension, filedata) VALUES ('
				. (int)$userid . ',' . (int)$now . ',\'' . $this->db->real_escape_string($hash) . '\','
				. (int)$size . ',\'' . $this->db->real_escape_string($ext) . '\',\'\')';
			if (!$this->db->query($sqlFd))
			{
				return array('error' => 'filedata insert failed: ' . $this->db->error);
			}
			$filedataid = (int)$this->db->insert_id;
			if ($this->writeAttachFile($filedataid, $userid, $bytes) === '')
			{
				return array('error' => 'Could not write .src attachment to filesystem');
			}
		}
		// vB fetchNodeAttachments joins only filedata with refcount > 0.
		$this->db->query('UPDATE ' . $p . 'filedata SET refcount=GREATEST(refcount,1) WHERE filedataid=' . (int)$filedataid);

		// contenttypeid for Text + Attach
		$textType = $this->contentTypeId('Text');
		$attachType = $this->contentTypeId('Attach');
		if ($textType < 1 || $attachType < 1)
		{
			return array('error' => 'contenttype Text/Attach missing');
		}

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
		$rawtext = 'Activated SeDiv license (.src) received for user ' . $customer . ".\n"
			. 'Tracking: ' . $record['token'] . "\n"
			. 'File: ' . $filename;

		// Load parent routeid / author defaults (vB6 node schema).
		$parent = null;
		$resP = $this->db->query('SELECT routeid, userid, authorname FROM ' . $p . 'node WHERE nodeid=' . (int)$parentId . ' LIMIT 1');
		if ($resP)
		{
			$parent = $resP->fetch_assoc();
		}
		$routeid = $parent && !empty($parent['routeid']) ? (int)$parent['routeid'] : 63;
		$staffAuthor = $this->usernameById($userid);
		// Match normal MC .lic attaches: owned by ticket author, inlist=0, protected=1, null titles.
		$attachUserid = $customerId > 0 ? $customerId : $userid;
		$attachAuthor = $customer !== '' ? $customer : $this->usernameById($attachUserid);

		// 2) Text note under the PM ticket (vB6 uses `created`, not createdate)
		$title = 'Activated license (.src)';
		$textNode = $this->insertNode(array(
			'routeid' => $routeid,
			'userid' => $userid,
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

		// 3) Attach .src directly under the PM node (same placement as user-uploaded .lic).
		$attachNode = $this->insertNode(array(
			'routeid' => $routeid,
			'userid' => $attachUserid,
			'authorname' => $attachAuthor,
			'parentid' => (int)$parentId,
			'starter' => (int)$starterId,
			'contenttypeid' => $attachType,
			'created' => $now,
			'lastcontent' => $now,
			'lastcontentid' => 0,
			'lastcontentauthor' => $attachAuthor,
			'lastauthorid' => $attachUserid,
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
		$this->ensureClosure((int)$attachNode, (int)$parentId, $now);
		$this->db->query(
			'INSERT INTO ' . $p . 'attach (nodeid, filedataid, filename, counter, settings) VALUES ('
			. $attachNode . ',' . $filedataid . ',\'' . $this->db->real_escape_string($filename) . '\',0,\'\')'
		);
		$this->db->query(
			'UPDATE ' . $p . 'filedata SET refcount=GREATEST(refcount,1), userid=' . (int)$attachUserid
			. ' WHERE filedataid=' . (int)$filedataid
		);

		// Update parent lastcontent + hasphoto so MC lists attachments.
		$this->db->query(
			'UPDATE ' . $p . 'node SET lastcontent=' . $now . ', lastcontentid=' . (int)$attachNode
			. ', lastcontentauthor=\'' . $this->db->real_escape_string($staffAuthor) . '\''
			. ', lastauthorid=' . $userid
			. ', hasphoto=1'
			. ', totalcount=totalcount+1, textcount=textcount+1'
			. ' WHERE nodeid=' . (int)$parentId
		);

		$this->clearNodeCaches(array((int)$parentId, (int)$textNode, (int)$attachNode));

		return array(
			'filedataid' => $filedataid,
			'text_nodeid' => $textNode,
			'attach_nodeid' => $attachNode,
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
