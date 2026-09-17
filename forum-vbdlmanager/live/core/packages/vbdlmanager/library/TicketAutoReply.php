<?php
/**
 * Auto-reply for new Message Center tickets sent to SeDiv / HDD Land support.
 * English only. Skips license system tickets (VBDL-LIC / VBDL-REQ).
 */
class vbdl_TicketAutoReply
{
	/** @var object */
	protected $db;
	/** @var string */
	protected $prefix;
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_LicenseMail */
	protected $mail;

	public function __construct($db, $prefix, vbdl_Repository $repo, vbdl_LicenseMail $mail)
	{
		$this->db = $db;
		$this->prefix = (string)$prefix;
		$this->repo = $repo;
		$this->mail = $mail;
		$this->ensureTable();
	}

	public function ensureTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}vbdl_ticket_autoreply (
			nodeid INT UNSIGNED NOT NULL,
			starter_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			customer_userid INT UNSIGNED NOT NULL DEFAULT 0,
			support_userid INT UNSIGNED NOT NULL DEFAULT 0,
			reply_nodeid INT UNSIGNED NOT NULL DEFAULT 0,
			dateline INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (nodeid),
			KEY starter_nodeid (starter_nodeid),
			KEY dateline (dateline)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@$this->db->query($sql);
	}

	public function enabled()
	{
		return (string)$this->repo->getSetting('ticket_autoreply_enabled', '1') !== '0';
	}

	public function supportUserid()
	{
		$id = (int)$this->repo->getSetting('license_support_userid', '0');
		if ($id < 1)
		{
			$id = (int)$this->repo->getSetting('ticket_autoreply_userid', '1');
		}
		return $id > 0 ? $id : 1;
	}

	/**
	 * Comma-separated extra recipient userids that should also trigger auto-reply
	 * (e.g. additional admin accounts users ticket).
	 */
	public function watchUserids()
	{
		$ids = array($this->supportUserid());
		$raw = trim((string)$this->repo->getSetting('ticket_autoreply_watch_userids', ''));
		foreach (explode(',', $raw) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$ids[] = $g;
			}
		}
		// Always include primary admin group owner userid 1 as fallback watch.
		$ids[] = 1;
		return array_values(array_unique($ids));
	}

	public function replySubject()
	{
		$s = trim((string)$this->repo->getSetting('ticket_autoreply_subject', 'Welcome to SeDiv Support'));
		return $s !== '' ? $s : 'Welcome to SeDiv Support';
	}

	public function replyBody()
	{
		$default = "Welcome to the SeDiv Support Team at HDD Land Company.\n\n"
			. "Thank you for contacting us. We have received your ticket and our support team will reply as soon as possible during business hours.\n\n"
			. "For new license purchases, please use Message Center → License Request.\n"
			. "For SeDiv VIP license activation, please use Message Center → active license sediv.\n\n"
			. "Please keep this ticket for all follow-up messages.\n\n"
			. "Best regards,\n"
			. "HDD Land Company · SeDiv Support";
		$s = trim((string)$this->repo->getSetting('ticket_autoreply_body', ''));
		return $s !== '' ? $s : $default;
	}

	public function alreadyReplied($nodeid)
	{
		$nodeid = (int)$nodeid;
		if ($nodeid < 1)
		{
			return true;
		}
		$res = @$this->db->query(
			'SELECT 1 FROM ' . $this->prefix . 'vbdl_ticket_autoreply WHERE nodeid=' . $nodeid . ' LIMIT 1'
		);
		return ($res && $res->fetch_row());
	}

	public function shouldSkipTicket($title, $rawtext = '')
	{
		$blob = strtoupper((string)$title . "\n" . (string)$rawtext);
		if (strpos($blob, 'VBDL-LIC-') !== false || strpos($blob, 'VBDL-REQ-') !== false)
		{
			return true;
		}
		// System-generated license desks already include their own first message.
		if (preg_match('/\b(LICENSE REQUEST|ACTIVE LICENSE SEDIV|ACTIVATED LICENSE|SEDIV VIP LICENSE)\b/i', $blob))
		{
			return true;
		}
		return false;
	}

	/**
	 * Process one starter PM node if it is a customer ticket to watched support accounts.
	 */
	public function processNode($nodeid)
	{
		if (!$this->enabled())
		{
			return array('skipped' => 1, 'reason' => 'disabled');
		}
		$nodeid = (int)$nodeid;
		if ($nodeid < 1 || $this->alreadyReplied($nodeid))
		{
			return array('skipped' => 1, 'reason' => 'already_or_invalid');
		}

		$p = $this->prefix;
		$res = $this->db->query(
			'SELECT nodeid, userid, authorname, title, starter, parentid, created FROM ' . $p . 'node WHERE nodeid=' . $nodeid . ' LIMIT 1'
		);
		if (!$res || !($node = $res->fetch_assoc()))
		{
			return array('error' => 'node_not_found');
		}
		$starter = !empty($node['starter']) ? (int)$node['starter'] : (int)$node['nodeid'];
		// Only auto-reply on conversation starters (new tickets), not every reply.
		if ((int)$node['nodeid'] !== $starter && (int)$node['parentid'] > 0 && (int)$node['parentid'] !== $starter)
		{
			// Allow parentid=0 or nodeid==starter
			if ((int)$node['parentid'] !== 0)
			{
				return array('skipped' => 1, 'reason' => 'not_starter');
			}
		}
		// Prefer working on the starter node id.
		if ($starter > 0 && $starter !== $nodeid)
		{
			if ($this->alreadyReplied($starter))
			{
				return array('skipped' => 1, 'reason' => 'already_starter');
			}
			$nodeid = $starter;
			$res = $this->db->query(
				'SELECT nodeid, userid, authorname, title, starter, parentid, created FROM ' . $p . 'node WHERE nodeid=' . $nodeid . ' LIMIT 1'
			);
			if (!$res || !($node = $res->fetch_assoc()))
			{
				return array('error' => 'starter_not_found');
			}
		}

		$title = isset($node['title']) ? (string)$node['title'] : '';
		$rawtext = '';
		$resT = $this->db->query('SELECT rawtext FROM ' . $p . 'text WHERE nodeid=' . $nodeid . ' LIMIT 1');
		if ($resT && ($t = $resT->fetch_assoc()))
		{
			$rawtext = (string)$t['rawtext'];
		}
		if ($this->shouldSkipTicket($title, $rawtext))
		{
			return array('skipped' => 1, 'reason' => 'license_system_ticket');
		}

		$authorId = (int)$node['userid'];
		$watch = $this->watchUserids();
		if (in_array($authorId, $watch, true))
		{
			// Support/admin opened the ticket themselves — do not auto-reply.
			return array('skipped' => 1, 'reason' => 'authored_by_support');
		}

		// Must include at least one watched support userid as participant.
		$watchList = implode(',', array_map('intval', $watch));
		$resS = $this->db->query(
			'SELECT userid FROM ' . $p . 'sentto WHERE nodeid=' . $nodeid . ' AND userid IN (' . $watchList . ') LIMIT 1'
		);
		if (!$resS || !($srow = $resS->fetch_assoc()))
		{
			return array('skipped' => 1, 'reason' => 'not_to_support');
		}
		$supportId = (int)$srow['userid'];
		if ($supportId < 1)
		{
			$supportId = $this->supportUserid();
		}

		$body = $this->replyBody();
		$subject = $this->replySubject();
		$replyNode = $this->mail->postTextToTicket($nodeid, $starter > 0 ? $starter : $nodeid, $supportId, $subject, $body);
		if ($replyNode < 1)
		{
			return array('error' => 'failed_posting_reply');
		}

		$now = time();
		$this->db->query(
			'INSERT IGNORE INTO ' . $p . 'vbdl_ticket_autoreply (nodeid, starter_nodeid, customer_userid, support_userid, reply_nodeid, dateline) VALUES ('
			. $nodeid . ',' . (int)$starter . ',' . $authorId . ',' . $supportId . ',' . (int)$replyNode . ',' . $now . ')'
		);

		return array(
			'ok' => true,
			'nodeid' => $nodeid,
			'reply_nodeid' => (int)$replyNode,
			'customer_userid' => $authorId,
			'support_userid' => $supportId,
		);
	}

	/**
	 * Scan recent inbound tickets to watched support accounts and auto-reply.
	 * @param int $onlyUserid When >0, only process tickets authored by that user (poll_self).
	 */
	public function pollRecent($limit = 40, $maxAgeSeconds = 86400, $onlyUserid = 0)
	{
		if (!$this->enabled())
		{
			return array('ok' => true, 'processed' => array(), 'skipped' => 1, 'reason' => 'disabled');
		}
		$limit = max(1, min(100, (int)$limit));
		$maxAgeSeconds = max(300, min(7 * 86400, (int)$maxAgeSeconds));
		$since = time() - $maxAgeSeconds;
		$watch = $this->watchUserids();
		$watchList = implode(',', array_map('intval', $watch));
		$onlyUserid = (int)$onlyUserid;
		$p = $this->prefix;

		$authorFilter = 'AND n.userid>0 AND n.userid NOT IN (' . $watchList . ') ';
		if ($onlyUserid > 0)
		{
			$authorFilter = 'AND n.userid=' . $onlyUserid . ' ';
		}

		// Recent PM starters where support is a participant and author is not support.
		$sql = 'SELECT DISTINCT n.nodeid FROM ' . $p . 'node n '
			. 'INNER JOIN ' . $p . 'sentto s ON s.nodeid=n.nodeid AND s.userid IN (' . $watchList . ') '
			. 'WHERE n.created>=' . (int)$since . ' '
			. $authorFilter
			. 'AND (n.nodeid=n.starter OR n.parentid=0 OR n.starter=n.nodeid) '
			. 'AND n.nodeid NOT IN (SELECT nodeid FROM ' . $p . 'vbdl_ticket_autoreply) '
			. 'ORDER BY n.nodeid DESC LIMIT ' . $limit;

		$processed = array();
		$skipped = array();
		$errors = array();
		$res = @$this->db->query($sql);
		if (!$res)
		{
			// Fallback without NOT IN subquery for older MySQL modes
			$sql2 = 'SELECT DISTINCT n.nodeid FROM ' . $p . 'node n '
				. 'INNER JOIN ' . $p . 'sentto s ON s.nodeid=n.nodeid AND s.userid IN (' . $watchList . ') '
				. 'LEFT JOIN ' . $p . 'vbdl_ticket_autoreply a ON a.nodeid=n.nodeid '
				. 'WHERE a.nodeid IS NULL AND n.created>=' . (int)$since . ' '
				. $authorFilter
				. 'ORDER BY n.nodeid DESC LIMIT ' . $limit;
			$res = @$this->db->query($sql2);
		}
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$result = $this->processNode((int)$row['nodeid']);
				if (!empty($result['ok']))
				{
					$processed[] = $result;
				}
				elseif (!empty($result['error']))
				{
					$errors[] = $result;
				}
				else
				{
					$skipped[] = $result;
				}
			}
		}
		return array(
			'ok' => true,
			'processed' => $processed,
			'skipped' => $skipped,
			'errors' => $errors,
			'watched' => $watch,
			'only_userid' => $onlyUserid,
		);
	}

	/**
	 * Recent auto-replies for AdminCP overview.
	 */
	public function listRecent($limit = 40)
	{
		$limit = max(1, min(200, (int)$limit));
		$p = $this->prefix;
		$out = array();
		$res = @$this->db->query(
			'SELECT a.*, n.title, u.username AS customer_username FROM ' . $p . 'vbdl_ticket_autoreply a '
			. 'LEFT JOIN ' . $p . 'node n ON n.nodeid=a.nodeid '
			. 'LEFT JOIN ' . $p . 'user u ON u.userid=a.customer_userid '
			. 'ORDER BY a.dateline DESC LIMIT ' . $limit
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
}
