<?php
/**
 * License purchase request: payment receipt → activator email → .txt license return → admin VIP add.
 */
class vbdl_LicenseRequest
{
	/** @var mysqli */
	protected $db;
	/** @var string */
	protected $prefix;
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_Acl */
	protected $acl;
	/** @var vbdl_LicenseMail */
	protected $mail;

	public function __construct(mysqli $db, $prefix, vbdl_Repository $repo, vbdl_Acl $acl, vbdl_LicenseMail $mail)
	{
		$this->db = $db;
		$this->prefix = (string)$prefix;
		$this->repo = $repo;
		$this->acl = $acl;
		$this->mail = $mail;
		$this->ensureTable();
	}

	public function ensureTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}vbdl_license_request (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			message_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			starter_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			customer_userid INT UNSIGNED NOT NULL DEFAULT 0,
			customer_username VARCHAR(191) NOT NULL DEFAULT '',
			customer_email VARCHAR(191) NOT NULL DEFAULT '',
			staff_userid INT UNSIGNED NOT NULL DEFAULT 0,
			receipt_filename VARCHAR(255) NOT NULL DEFAULT '',
			receipt_filedataid INT UNSIGNED NOT NULL DEFAULT 0,
			to_email VARCHAR(191) NOT NULL DEFAULT '',
			subject VARCHAR(255) NOT NULL DEFAULT '',
			status ENUM('sent','approved','vip_added','rejected','error') NOT NULL DEFAULT 'sent',
			license_filename VARCHAR(255) NOT NULL DEFAULT '',
			license_filedataid INT UNSIGNED NOT NULL DEFAULT 0,
			license_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			reply_text MEDIUMTEXT,
			note MEDIUMTEXT,
			vip_usergroupid INT UNSIGNED NOT NULL DEFAULT 0,
			vip_added_by INT UNSIGNED NOT NULL DEFAULT 0,
			vip_added_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			sent_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			approved_dateline INT UNSIGNED NOT NULL DEFAULT 0,
			meta MEDIUMTEXT,
			PRIMARY KEY (id),
			UNIQUE KEY token (token),
			KEY message_nodeid (message_nodeid),
			KEY customer_userid (customer_userid),
			KEY status (status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@$this->db->query($sql);
	}

	public function requestEmail()
	{
		$e = trim((string)$this->repo->getSetting('license_request_email', 'info@hdd-land.com'));
		return $e !== '' ? $e : 'info@hdd-land.com';
	}

	public function requestSubject()
	{
		$s = trim((string)$this->repo->getSetting('license_request_subject', 'License purchase request'));
		return $s !== '' ? $s : 'License purchase request';
	}

	public function supportUserid()
	{
		return $this->mail->supportUserid();
	}

	/**
	 * Primary VIP SeDiv group to assign after admin approval.
	 */
	public function targetVipGroupId()
	{
		$forced = (int)$this->repo->getSetting('license_request_vip_usergroupid', '0');
		if ($forced > 0)
		{
			return $forced;
		}
		$ids = $this->acl->vipGroupIds();
		if (!$ids)
		{
			// Hard fallback used elsewhere in this product.
			return 14;
		}
		return (int)$ids[0];
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
		return 'VBDL-REQ-' . strtoupper($hex);
	}

	public function extractTokenFromText($text)
	{
		if (preg_match('/\b(VBDL-REQ-[A-Z0-9\-]+)\b/i', (string)$text, $m))
		{
			return strtoupper($m[1]);
		}
		return '';
	}

	public function findByToken($token)
	{
		$token = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$token);
		if ($token === '')
		{
			return null;
		}
		$esc = $this->db->real_escape_string($token);
		$res = $this->db->query('SELECT * FROM ' . $this->prefix . 'vbdl_license_request WHERE token=\'' . $esc . '\' LIMIT 1');
		if ($res && ($row = $res->fetch_assoc()))
		{
			return $row;
		}
		return null;
	}

	public function listForUser($userid, $limit = 30)
	{
		$userid = (int)$userid;
		$limit = max(1, min(100, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT * FROM ' . $this->prefix . 'vbdl_license_request WHERE customer_userid=' . $userid
			. ' ORDER BY id DESC LIMIT ' . $limit
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

	public function listAll($limit = 80)
	{
		$limit = max(1, min(200, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT * FROM ' . $this->prefix . 'vbdl_license_request ORDER BY id DESC LIMIT ' . $limit
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
	 * Admin queue: approved licenses waiting to be added to VIP SeDiv.
	 */
	public function listPendingVip($limit = 50)
	{
		$limit = max(1, min(200, (int)$limit));
		$out = array();
		$res = $this->db->query(
			'SELECT * FROM ' . $this->prefix . 'vbdl_license_request WHERE status=\'approved\' '
			. 'ORDER BY approved_dateline ASC, id ASC LIMIT ' . $limit
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

	public function submitReceipt($userid, $username, $email, $filename, $bytes, $note = '')
	{
		$userid = (int)$userid;
		$username = (string)$username;
		$email = (string)$email;
		$filename = preg_replace('/[^\w.\-()+@]+/', '_', (string)$filename);
		$note = trim((string)$note);
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'), true))
		{
			return array('error' => 'Upload a payment receipt image (jpg/png/webp/gif) or PDF');
		}
		if ($bytes === '' || $bytes === null)
		{
			return array('error' => 'Empty receipt file');
		}
		if (strlen($bytes) > 8 * 1024 * 1024)
		{
			return array('error' => 'Receipt file too large (max 8MB)');
		}

		$token = $this->makeToken();
		$ticket = $this->mail->createVipLicenseTicket(
			$userid,
			$username,
			$token,
			$filename,
			'License purchase request',
			$this->requestSubject()
		);
		if (!empty($ticket['error']))
		{
			return $ticket;
		}
		$messageNode = (int)$ticket['message_nodeid'];
		$starterNode = (int)$ticket['starter_nodeid'];
		$supportId = !empty($ticket['support_userid']) ? (int)$ticket['support_userid'] : $this->supportUserid();

		$attached = $this->mail->attachFileToTicket($messageNode, $starterNode, $userid, $filename, $bytes);
		if (!empty($attached['error']))
		{
			return array('error' => 'Ticket created but receipt attach failed: ' . $attached['error']);
		}

		$to = $this->requestEmail();
		$subject = $this->requestSubject() . ' [' . $token . ']';
		$noteEsc = $this->db->real_escape_string($note);
		$tokenEsc = $this->db->real_escape_string($token);
		$userEsc = $this->db->real_escape_string($username);
		$emailEsc = $this->db->real_escape_string($email);
		$fileEsc = $this->db->real_escape_string($filename);
		$toEsc = $this->db->real_escape_string($to);
		$subjEsc = $this->db->real_escape_string($subject);
		$now = time();
		$sql = 'INSERT INTO ' . $this->prefix . 'vbdl_license_request
			(token, message_nodeid, starter_nodeid, customer_userid, customer_username, customer_email,
			 staff_userid, receipt_filename, receipt_filedataid, to_email, subject, status, note, sent_dateline)
			VALUES (\'' . $tokenEsc . '\',' . $messageNode . ',' . $starterNode . ',' . $userid
			. ',\'' . $userEsc . '\',\'' . $emailEsc . '\',' . $supportId
			. ',\'' . $fileEsc . '\',' . (int)$attached['filedataid']
			. ',\'' . $toEsc . '\',\'' . $subjEsc . '\',\'sent\',\'' . $noteEsc . '\',' . $now . ')';
		if (!$this->db->query($sql))
		{
			return array('error' => 'DB insert failed: ' . $this->db->error);
		}

		$body = "License purchase request\n";
		$body .= "========================\n\n";
		$body .= "Tracking token: {$token}\n";
		$body .= "(Keep this token in your reply subject or body)\n\n";
		$body .= "Customer username: {$username}\n";
		$body .= "Customer email: " . ($email !== '' ? $email : '(not set)') . "\n";
		$body .= "Customer userid: {$userid}\n";
		$body .= "Receipt file: {$filename}\n";
		$body .= "Message Center ticket: https://forum.hdd-land.com/messagecenter/view/"
			. ($starterNode > 0 ? $starterNode : $messageNode) . "\n";
		if ($note !== '')
		{
			$body .= "\nCustomer note:\n{$note}\n";
		}
		$body .= "\nPlease reply with the license as a .txt attachment (and optional text).\n";
		$body .= "The system will post it into the same ticket and mark the request approved.\n";

		return array(
			'ok' => true,
			'token' => $token,
			'message_nodeid' => $messageNode,
			'starter_nodeid' => $starterNode,
			'support_userid' => $supportId,
			'to_email' => $to,
			'subject' => $subject,
			'filename' => $filename,
			'bytes' => $bytes,
			'body' => $body,
			'message_url' => '/messagecenter/view/' . ($starterNode > 0 ? $starterNode : $messageNode),
		);
	}

	/**
	 * Activator replied with license .txt (+ optional text) → post into ticket, mark approved, notify user.
	 */
	public function approveWithLicense(array $record, $filename, $bytes, $replyText = '', $staffUserid = 0)
	{
		$filename = preg_replace('/[^\w.\-()+@]+/', '_', (string)$filename);
		if (!preg_match('/\.txt$/i', $filename))
		{
			$filename .= '.txt';
		}
		if ($bytes === '' || $bytes === null)
		{
			return array('error' => 'Empty license .txt file');
		}
		if (!empty($record['status']) && in_array($record['status'], array('approved', 'vip_added'), true))
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

		$attached = $this->mail->attachFileToTicket($parentId, $starter, (int)$record['customer_userid'] > 0 ? (int)$record['customer_userid'] : $userid, $filename, $bytes);
		if (!empty($attached['error']))
		{
			return $attached;
		}

		$replyText = trim(preg_replace('/\r\n?/', "\n", (string)$replyText));
		$notice = "تأیید شد ✅\n"
			. "لایسنس شما دریافت و داخل همین تیکت قرار گرفت.\n"
			. "می‌توانید از فایل لایسنس (.txt) برای ساخت/اکتیو در منوی active license sediv استفاده کنید.\n\n"
			. "Approved — your license file is attached to this ticket.\n"
			. "Use it with Active License SeDiv after an admin adds you to VIP SeDiv.";
		if ($replyText !== '')
		{
			$notice .= "\n\n--- Activator message ---\n" . substr($replyText, 0, 8000);
		}
		$textNode = $this->mail->postTextToTicket($parentId, $starter, $userid, 'License approved', $notice);

		$tokenEsc = $this->db->real_escape_string($record['token']);
		$nameEsc = $this->db->real_escape_string($filename);
		$replyEsc = $this->db->real_escape_string($replyText);
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_request SET status=\'approved\', '
			. 'license_filename=\'' . $nameEsc . '\', license_filedataid=' . (int)$attached['filedataid']
			. ', license_nodeid=' . (int)$attached['attach_nodeid']
			. ', reply_text=\'' . $replyEsc . '\', approved_dateline=' . $now
			. ' WHERE token=\'' . $tokenEsc . '\''
		);

		return array(
			'ok' => true,
			'token' => $record['token'],
			'status' => 'approved',
			'filename' => $filename,
			'filedataid' => (int)$attached['filedataid'],
			'attach_nodeid' => (int)$attached['attach_nodeid'],
			'text_nodeid' => (int)$textNode,
		);
	}

	/**
	 * Admin: add customer to VIP SeDiv secondary group and close the report.
	 */
	public function addCustomerToVip(array $record, $adminUserid)
	{
		$adminUserid = (int)$adminUserid;
		$userid = (int)$record['customer_userid'];
		if ($userid < 1)
		{
			return array('error' => 'Missing customer userid');
		}
		if (!empty($record['status']) && $record['status'] === 'vip_added')
		{
			return array('ok' => true, 'skipped' => 1, 'reason' => 'already_vip_added');
		}
		if (empty($record['status']) || $record['status'] !== 'approved')
		{
			return array('error' => 'Request must be approved before VIP add');
		}

		$ugid = $this->targetVipGroupId();
		$res = $this->db->query(
			'SELECT userid, username, usergroupid, membergroupids FROM ' . $this->prefix . 'user WHERE userid=' . $userid . ' LIMIT 1'
		);
		if (!$res || !($user = $res->fetch_assoc()))
		{
			return array('error' => 'User not found');
		}

		$already = ((int)$user['usergroupid'] === $ugid);
		$ids = array();
		foreach (explode(',', (string)$user['membergroupids']) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$ids[] = $g;
			}
		}
		if (in_array($ugid, $ids, true))
		{
			$already = true;
		}
		if (!$already)
		{
			$ids[] = $ugid;
			$ids = array_values(array_unique($ids));
			$csv = $this->db->real_escape_string(implode(',', $ids));
			$this->db->query(
				'UPDATE ' . $this->prefix . 'user SET membergroupids=\'' . $csv . '\' WHERE userid=' . $userid
			);
		}

		$tokenEsc = $this->db->real_escape_string($record['token']);
		$now = time();
		$this->db->query(
			'UPDATE ' . $this->prefix . 'vbdl_license_request SET status=\'vip_added\', '
			. 'vip_usergroupid=' . (int)$ugid . ', vip_added_by=' . $adminUserid
			. ', vip_added_dateline=' . $now
			. ' WHERE token=\'' . $tokenEsc . '\''
		);

		// Notify in ticket
		$parentId = (int)$record['message_nodeid'];
		$starter = (int)$record['starter_nodeid'];
		if ($parentId > 0)
		{
			$msg = "Admin added this user to VIP SeDiv (usergroup #{$ugid}).\n"
				. "User can now use Active License SeDiv.";
			$this->mail->postTextToTicket(
				$parentId,
				$starter > 0 ? $starter : $parentId,
				$adminUserid > 0 ? $adminUserid : $this->supportUserid(),
				'VIP SeDiv added',
				$msg
			);
		}

		return array(
			'ok' => true,
			'token' => $record['token'],
			'userid' => $userid,
			'username' => $user['username'],
			'vip_usergroupid' => $ugid,
			'already_member' => $already ? 1 : 0,
		);
	}
}
