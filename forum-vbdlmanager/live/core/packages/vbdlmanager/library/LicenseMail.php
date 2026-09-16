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
			to_email VARCHAR(191) NOT NULL DEFAULT '',
			subject VARCHAR(255) NOT NULL DEFAULT '',
			status ENUM('sent','returned','error') NOT NULL DEFAULT 'sent',
			return_filename VARCHAR(255) NOT NULL DEFAULT '',
			return_filedataid INT UNSIGNED NOT NULL DEFAULT 0,
			return_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			sent_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			returned_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			meta MEDIUMTEXT,
			PRIMARY KEY (id),
			UNIQUE KEY token (token),
			KEY message_nodeid (message_nodeid),
			KEY customer_userid (customer_userid),
			KEY status (status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@$this->db->query($sql);
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

	public function vipOnly()
	{
		return (string)$this->repo->getSetting('license_vip_only', '1') !== '0';
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

	public function listForUser($userid, $limit = 30)
	{
		$userid = (int)$userid;
		$limit = max(1, min(100, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT token, message_nodeid, starter_nodeid, lic_filename, status, return_filename, return_filedataid, '
			. 'sent_dateline, returned_dateline FROM ' . $this->prefix . 'vbdl_license_mail '
			. 'WHERE customer_userid=' . $userid . ' ORDER BY id DESC LIMIT ' . $limit
		);
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Create a Message Center starter ticket for a VIP self-service license request.
	 * Returns message_nodeid / starter_nodeid (same for starter).
	 */
	public function createVipLicenseTicket($userid, $username, $token, $licFilename)
	{
		$userid = (int)$userid;
		$username = (string)$username;
		$token = (string)$token;
		$licFilename = (string)$licFilename;
		$now = time();
		$p = $this->prefix;

		// Prefer vB private message API when available.
		try
		{
			if (class_exists('vB_Api', false))
			{
				$api = vB_Api::instanceInternal('content_privatemessage');
				if ($api && method_exists($api, 'add'))
				{
					$title = 'Active License SeDiv — ' . $token;
					$text = "Active License SeDiv request created.\n"
						. "Tracking token: {$token}\n"
						. "License file: {$licFilename}\n\n"
						. "When activation returns, the .src file will be attached in this ticket.";
					$data = array(
						'title' => $title,
						'rawtext' => $text,
						'msgtext' => $text,
						'sentto' => array($userid),
						'recipients' => $username,
						'parentid' => 0,
						'userid' => $userid,
					);
					$result = $api->add($data, array('bypassPerms' => true));
					$nodeid = 0;
					if (is_array($result))
					{
						if (!empty($result['nodeid']))
						{
							$nodeid = (int)$result['nodeid'];
						}
						elseif (!empty($result[0]))
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
						return array('message_nodeid' => $nodeid, 'starter_nodeid' => $starter);
					}
				}
			}
		}
		catch (Throwable $e)
		{
		}

		// SQL fallback: create a visible text node owned by the VIP user (MC-compatible best effort).
		$textType = $this->contentTypeId('Text');
		if ($textType < 1)
		{
			return array('error' => 'Cannot create Message Center ticket (contenttype missing)');
		}
		$title = 'Active License SeDiv — ' . $token;
		$rawtext = "Active License SeDiv request created.\nTracking token: {$token}\nLicense file: {$licFilename}\n";
		$nodeid = $this->insertNode(array(
			'userid' => $userid,
			'authorname' => $username !== '' ? $username : $this->usernameById($userid),
			'parentid' => 0,
			'starter' => 0,
			'contenttypeid' => $textType,
			'title' => $title,
			'createdate' => $now,
			'lastcontent' => $now,
			'lastcontentid' => 0,
			'lastauthorid' => $userid,
			'publishdate' => $now,
			'showpublished' => 1,
			'showopen' => 1,
			'approved' => 1,
			'showapproved' => 1,
		));
		if ($nodeid < 1)
		{
			return array('error' => 'Failed creating Message Center ticket');
		}
		$this->db->query('UPDATE ' . $p . 'node SET starter=' . $nodeid . ', lastcontentid=' . $nodeid . ' WHERE nodeid=' . $nodeid);
		$this->db->query(
			'INSERT INTO ' . $p . 'text (nodeid, rawtext, htmltitle) VALUES ('
			. $nodeid . ',\'' . $this->db->real_escape_string($rawtext) . '\',\''
			. $this->db->real_escape_string($title) . '\')'
		);
		return array('message_nodeid' => $nodeid, 'starter_nodeid' => $nodeid);
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
		$to = $this->db->real_escape_string((string)$data['to_email']);
		$subject = $this->db->real_escape_string((string)$data['subject']);
		$tokenEsc = $this->db->real_escape_string($token);
		$now = time();
		$sql = 'INSERT INTO ' . $this->prefix . 'vbdl_license_mail
			(token, message_nodeid, starter_nodeid, customer_userid, customer_username, customer_email,
			 staff_userid, filedataid, lic_filename, to_email, subject, status, sent_dateline)
			VALUES (\'' . $tokenEsc . '\',' . $messageNode . ',' . $starter . ',' . $custId
			. ',\'' . $custUser . '\',\'' . $custEmail . '\',' . $staffId . ',' . $filedataid
			. ',\'' . $licName . '\',\'' . $to . '\',\'' . $subject . '\',\'sent\',' . $now . ')';
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
