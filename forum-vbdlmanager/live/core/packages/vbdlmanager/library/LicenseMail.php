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
		$hash = sha1($bytes);
		$size = strlen($bytes);
		$ext = 'src';

		// 1) filedata via UNHEX (portable across hosts)
		$hex = bin2hex($bytes);
		$sqlFd = 'INSERT INTO ' . $p . 'filedata (userid, dateline, filehash, filesize, extension, filedata) VALUES ('
			. (int)$userid . ',' . (int)$now . ',\'' . $this->db->real_escape_string($hash) . '\','
			. (int)$size . ',\'' . $this->db->real_escape_string($ext) . '\',UNHEX(\'' . $hex . '\'))';
		if (!$this->db->query($sqlFd))
		{
			return array('error' => 'filedata insert failed: ' . $this->db->error);
		}
		$filedataid = (int)$this->db->insert_id;

		// contenttypeid for Text + Attach
		$textType = $this->contentTypeId('Text');
		$attachType = $this->contentTypeId('Attach');
		if ($textType < 1 || $attachType < 1)
		{
			return array('error' => 'contenttype Text/Attach missing');
		}

		$customer = (string)$record['customer_username'];
		$rawtext = 'Activated SeDiv license (.src) received for user ' . $customer . ".\n"
			. 'Tracking: ' . $record['token'] . "\n"
			. 'File: ' . $filename;

		// 2) text reply node under parent message
		$title = 'Activated license (.src)';
		$textNode = $this->insertNode(array(
			'userid' => $userid,
			'authorname' => $this->usernameById($userid),
			'parentid' => (int)$parentId,
			'starter' => (int)$starterId,
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
		if ($textNode < 1)
		{
			return array('error' => 'Failed creating reply node');
		}
		$this->db->query(
			'INSERT INTO ' . $p . 'text (nodeid, rawtext, htmltitle) VALUES ('
			. $textNode . ',\'' . $this->db->real_escape_string($rawtext) . '\',\''
			. $this->db->real_escape_string($title) . '\')'
		);

		// 3) attach node under text reply
		$attachNode = $this->insertNode(array(
			'userid' => $userid,
			'authorname' => $this->usernameById($userid),
			'parentid' => $textNode,
			'starter' => (int)$starterId,
			'contenttypeid' => $attachType,
			'title' => $filename,
			'createdate' => $now,
			'lastcontent' => $now,
			'publishdate' => $now,
			'showpublished' => 1,
			'showopen' => 1,
			'approved' => 1,
			'showapproved' => 1,
		));
		if ($attachNode < 1)
		{
			return array('error' => 'Failed creating attach node');
		}
		$this->db->query(
			'INSERT INTO ' . $p . 'attach (nodeid, filedataid, filename, counter, settings) VALUES ('
			. $attachNode . ',' . $filedataid . ',\'' . $this->db->real_escape_string($filename) . '\',0,\'\')'
		);

		// Update parent lastcontent pointers lightly
		$this->db->query(
			'UPDATE ' . $p . 'node SET lastcontent=' . $now . ', lastcontentid=' . $textNode
			. ', lastauthor=\'' . $this->db->real_escape_string($this->usernameById($userid)) . '\''
			. ', lastauthorid=' . $userid . ' WHERE nodeid=' . (int)$parentId
		);

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
			return 0;
		}
		return (int)$this->db->insert_id;
	}
}
