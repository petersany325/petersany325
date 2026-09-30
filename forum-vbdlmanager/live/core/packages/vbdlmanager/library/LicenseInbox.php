<?php
/**
 * Unified SeDiv license inbox importer.
 *
 * Pulls activator replies from local info@ maildir (preferred) or IMAP and posts them
 * into the matching Message Center tickets:
 *   - VBDL-REQ-*  → .txt (or body) → LicenseRequest::approveWithLicense
 *   - VBDL-LIC-*  → .src attach    → LicenseMail::returnSrcToTicket
 *                 → text-only     → LicenseMail::rejectReplyToTicket
 *
 * Matching is driven by pending DB tokens first so older mails are not missed when
 * the mailbox is large or cron has been idle.
 */
class vbdl_LicenseInbox
{
	/** @var mysqli */
	protected $db;
	/** @var string */
	protected $prefix;
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_LicenseMail */
	protected $mail;
	/** @var vbdl_LicenseRequest */
	protected $request;

	public function __construct(
		mysqli $db,
		$prefix,
		vbdl_Repository $repo,
		vbdl_LicenseMail $mail,
		vbdl_LicenseRequest $request
	)
	{
		$this->db = $db;
		$this->prefix = (string)$prefix;
		$this->repo = $repo;
		$this->mail = $mail;
		$this->request = $request;
	}

	/**
	 * Poll maildir then (optionally) IMAP. Returns JSON-ready result array.
	 *
	 * @param array $opts keys: auto (bool), max_files (int), only_token (string)
	 * @return array
	 */
	public function poll(array $opts = array())
	{
		$auto = !empty($opts['auto']);
		$onlyToken = isset($opts['only_token']) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$opts['only_token']) : '';
		$maxFiles = isset($opts['max_files']) ? (int)$opts['max_files'] : 0;

		if ($auto && $this->isThrottled(45))
		{
			return array(
				'ok' => true,
				'throttled' => 1,
				'message' => 'Auto-poll throttled (ran recently)',
				'processed' => array(),
				'errors' => array(),
			);
		}

		$maildir = trim((string)$this->repo->getSetting('license_maildir', '/home/hddrecov/mail/hdd-land.com/info'));
		$result = null;
		if ($maildir !== '' && is_dir($maildir) && @is_readable($maildir))
		{
			$result = $this->pollMaildir($maildir, $maxFiles > 0 ? $maxFiles : 400, $onlyToken);
			$result['mode'] = 'maildir';
			$result['maildir'] = $maildir;
		}
		else
		{
			$host = trim((string)$this->repo->getSetting('license_imap_host', ''));
			$user = trim((string)$this->repo->getSetting('license_imap_user', ''));
			$pass = (string)$this->repo->getSetting('license_imap_pass', '');
			$port = (int)$this->repo->getSetting('license_imap_port', '993');
			$flags = trim((string)$this->repo->getSetting('license_imap_flags', '/imap/ssl/novalidate-cert'));
			if ($host === '' || $user === '' || $pass === '')
			{
				$result = array(
					'ok' => true,
					'skipped' => 1,
					'message' => 'Maildir not readable and IMAP not configured. Manual upload still works.',
					'processed' => array(),
					'errors' => array(),
					'maildir' => $maildir,
					'maildir_ok' => 0,
				);
			}
			elseif (!function_exists('imap_open'))
			{
				$result = array(
					'ok' => false,
					'error' => 'PHP IMAP extension not installed',
					'processed' => array(),
					'errors' => array(),
				);
			}
			else
			{
				$result = $this->pollImap($host, $user, $pass, $port, $flags, $onlyToken);
				$result['mode'] = 'imap';
			}
		}

		$purged = $this->mail->purgeExpiredSrcFiles(40);
		$result['purged'] = $purged;
		$result['purged_count'] = count($purged);

		// Finish VIP for requests that already have license .txt but never got vip_added
		// (e.g. older installs before auto-VIP, or a previous grant failure).
		$vipFixed = $this->finishPendingVipGrants(40);
		if ($vipFixed)
		{
			if (empty($result['processed']) || !is_array($result['processed']))
			{
				$result['processed'] = array();
			}
			foreach ($vipFixed as $row)
			{
				$result['processed'][] = $row;
			}
		}
		$result['vip_auto_fixed'] = count($vipFixed);

		$result['ok'] = isset($result['ok']) ? $result['ok'] : true;
		$this->touchPollStamp();
		return $result;
	}

	/**
	 * Grant VIP SeDiv for approved License Requests that are still waiting.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function finishPendingVipGrants($limit = 40)
	{
		$out = array();
		$rows = $this->request->listPendingVip($limit);
		foreach ($rows as $rec)
		{
			$actor = !empty($rec['staff_userid']) ? (int)$rec['staff_userid'] : $this->mail->supportUserid();
			$vip = $this->request->addCustomerToVip($rec, $actor, true);
			if (!empty($vip['error']))
			{
				continue;
			}
			$out[] = array(
				'token' => $rec['token'],
				'kind' => 'vip_auto',
				'status' => 'vip_added',
				'via' => 'pending_vip_grant',
				'vip_usergroupid' => isset($vip['vip_usergroupid']) ? (int)$vip['vip_usergroupid'] : 0,
				'message_url' => '/messagecenter/view/'
					. (!empty($rec['starter_nodeid']) ? (int)$rec['starter_nodeid'] : (int)$rec['message_nodeid']),
			);
		}
		return $out;
	}

	/**
	 * @param string $maildir
	 * @param int $maxFiles
	 * @param string $onlyToken
	 * @return array
	 */
	public function pollMaildir($maildir, $maxFiles = 400, $onlyToken = '')
	{
		$pendingLic = $this->indexByToken($this->mail->listPendingReturn(200));
		$pendingReq = $this->indexByToken($this->request->listPendingLicenseReturn(200));
		if ($onlyToken !== '')
		{
			$pendingLic = isset($pendingLic[$onlyToken]) ? array($onlyToken => $pendingLic[$onlyToken]) : array();
			$pendingReq = isset($pendingReq[$onlyToken]) ? array($onlyToken => $pendingReq[$onlyToken]) : array();
			// Also allow importing a specific token even if status filter missed it
			if (!$pendingLic && !$pendingReq)
			{
				if (stripos($onlyToken, 'VBDL-REQ-') === 0)
				{
					$rec = $this->request->findByToken($onlyToken);
					if ($rec)
					{
						$pendingReq[$onlyToken] = $rec;
					}
				}
				else
				{
					$rec = $this->mail->findByToken($onlyToken);
					if ($rec)
					{
						$pendingLic[$onlyToken] = $rec;
					}
				}
			}
		}

		$files = array();
		$this->scanMaildir($maildir, $files, 0);
		// Prefer maildir new/ then cur/ by sorting path, then newest mtime.
		usort($files, function ($a, $b) {
			$ap = (strpos($a, '/new/') !== false) ? 0 : ((strpos($a, '/cur/') !== false) ? 1 : 2);
			$bp = (strpos($b, '/new/') !== false) ? 0 : ((strpos($b, '/cur/') !== false) ? 1 : 2);
			if ($ap !== $bp)
			{
				return $ap - $bp;
			}
			return filemtime($b) - filemtime($a);
		});
		$maxFiles = max(40, min(800, (int)$maxFiles));
		$files = array_slice($files, 0, $maxFiles);

		$processed = array();
		$errors = array();
		$doneTokens = array();
		$checked = 0;

		// Pass 1: walk newest mail; process any pending token found.
		foreach ($files as $path)
		{
			$raw = @file_get_contents($path);
			if ($raw === false || $raw === '')
			{
				continue;
			}
			$checked++;
			$tokens = $this->extractAllTokens($raw);
			if (!$tokens)
			{
				continue;
			}
			foreach ($tokens as $tok)
			{
				if (isset($doneTokens[$tok]))
				{
					continue;
				}
				if (isset($pendingReq[$tok]))
				{
					$rec = $pendingReq[$tok];
					if (!empty($rec['status']) && $rec['status'] !== 'sent' && $onlyToken === '')
					{
						continue;
					}
					$result = $this->approveReqFromRaw($rec, $raw, 'maildir');
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $tok, 'error' => $result['error'], 'mail_file' => basename($path));
					}
					else
					{
						$result['mail_file'] = basename($path);
						$processed[] = $result;
						$doneTokens[$tok] = 1;
						unset($pendingReq[$tok]);
					}
				}
				elseif (isset($pendingLic[$tok]))
				{
					$rec = $pendingLic[$tok];
					$result = $this->returnLicFromRaw($rec, $raw, 'maildir');
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $tok, 'error' => $result['error'], 'mail_file' => basename($path));
					}
					else
					{
						$result['mail_file'] = basename($path);
						$processed[] = $result;
						$doneTokens[$tok] = 1;
						unset($pendingLic[$tok]);
					}
				}
			}
		}

		// Pass 2: for still-pending tokens, targeted scan of remaining mailbox files.
		$still = array_merge(array_keys($pendingReq), array_keys($pendingLic));
		if ($still)
		{
			$extra = array();
			$this->scanMaildir($maildir, $extra, 0);
			foreach ($extra as $path)
			{
				if (in_array($path, $files, true))
				{
					continue;
				}
				// Cheap needle check before full read when possible
				$probe = @file_get_contents($path, false, null, 0, 512000);
				if ($probe === false || $probe === '')
				{
					continue;
				}
				$hit = '';
				foreach ($still as $tok)
				{
					if (stripos($probe, $tok) !== false)
					{
						$hit = $tok;
						break;
					}
				}
				if ($hit === '')
				{
					// Token might be deeper in a large message
					if (strlen($probe) < 512000)
					{
						continue;
					}
					$rawFull = @file_get_contents($path);
					if ($rawFull === false)
					{
						continue;
					}
					foreach ($still as $tok)
					{
						if (stripos($rawFull, $tok) !== false)
						{
							$hit = $tok;
							$probe = $rawFull;
							break;
						}
					}
					if ($hit === '')
					{
						continue;
					}
				}
				else
				{
					// Ensure full body for attachment decode
					$probe = @file_get_contents($path);
					if ($probe === false)
					{
						continue;
					}
				}

				$checked++;
				$raw = $probe;
				if (isset($pendingReq[$hit]) && empty($doneTokens[$hit]))
				{
					$result = $this->approveReqFromRaw($pendingReq[$hit], $raw, 'maildir');
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $hit, 'error' => $result['error'], 'mail_file' => basename($path));
					}
					else
					{
						$result['mail_file'] = basename($path);
						$processed[] = $result;
						$doneTokens[$hit] = 1;
						unset($pendingReq[$hit]);
					}
				}
				elseif (isset($pendingLic[$hit]) && empty($doneTokens[$hit]))
				{
					$result = $this->returnLicFromRaw($pendingLic[$hit], $raw, 'maildir');
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $hit, 'error' => $result['error'], 'mail_file' => basename($path));
					}
					else
					{
						$result['mail_file'] = basename($path);
						$processed[] = $result;
						$doneTokens[$hit] = 1;
						unset($pendingLic[$hit]);
					}
				}
				$still = array_merge(array_keys($pendingReq), array_keys($pendingLic));
				if (!$still)
				{
					break;
				}
			}
		}

		return array(
			'ok' => true,
			'processed' => $processed,
			'errors' => $errors,
			'checked' => $checked,
			'pending_lic_left' => count($pendingLic),
			'pending_req_left' => count($pendingReq),
			'pending_lic' => array_keys($pendingLic),
			'pending_req' => array_keys($pendingReq),
		);
	}

	/**
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param int $port
	 * @param string $flags
	 * @param string $onlyToken
	 * @return array
	 */
	public function pollImap($host, $user, $pass, $port, $flags, $onlyToken = '')
	{
		$mailbox = '{' . $host . ':' . ($port > 0 ? $port : 993) . $flags . '}INBOX';
		$imap = @imap_open($mailbox, $user, $pass);
		if (!$imap)
		{
			return array('ok' => false, 'error' => 'IMAP login failed: ' . imap_last_error(), 'processed' => array(), 'errors' => array());
		}

		$pendingLic = $this->indexByToken($this->mail->listPendingReturn(200));
		$pendingReq = $this->indexByToken($this->request->listPendingLicenseReturn(200));
		if ($onlyToken !== '')
		{
			$pendingLic = isset($pendingLic[$onlyToken]) ? array($onlyToken => $pendingLic[$onlyToken]) : array();
			$pendingReq = isset($pendingReq[$onlyToken]) ? array($onlyToken => $pendingReq[$onlyToken]) : array();
		}

		$processed = array();
		$errors = array();
		$ids = imap_search($imap, 'UNSEEN') ?: array();
		if (!$ids)
		{
			$ids = imap_search($imap, 'ALL') ?: array();
			$ids = array_slice(array_reverse($ids), 0, 80);
		}
		else
		{
			// Also include recent ALL so Seen-but-unprocessed replies are caught
			$all = imap_search($imap, 'ALL') ?: array();
			$all = array_slice(array_reverse($all), 0, 60);
			$ids = array_values(array_unique(array_merge($ids, $all)));
		}

		foreach ($ids as $msgno)
		{
			$overview = imap_fetch_overview($imap, (string)$msgno, 0);
			$subject = '';
			if (!empty($overview[0]->subject))
			{
				$subject = imap_utf8($overview[0]->subject);
			}
			$body = imap_body($imap, $msgno);
			$header = imap_fetchheader($imap, $msgno);
			$raw = $header . "\n" . $body;
			$blob = $subject . "\n" . $raw;
			$tokens = $this->extractAllTokens($blob);
			if (!$tokens)
			{
				continue;
			}

			foreach ($tokens as $tok)
			{
				if (isset($pendingReq[$tok]))
				{
					$structure = imap_fetchstructure($imap, $msgno);
					$parts = array();
					$this->flattenImapParts($structure, '', $parts);
					$txtBytes = null;
					$txtName = 'license.txt';
					foreach ($parts as $part)
					{
						$name = isset($part['filename']) ? (string)$part['filename'] : '';
						if ($name === '' || !preg_match('/\.txt$/i', $name))
						{
							continue;
						}
						$data = $this->decodeImapPart($imap, $msgno, $part);
						if ($data !== null && trim($data) !== '')
						{
							$txtBytes = $data;
							$txtName = $name;
							break;
						}
					}
					if ($txtBytes !== null)
					{
						$textBody = $this->extractTextFromRfc822($raw);
						$result = $this->request->approveWithLicense($pendingReq[$tok], $txtName, $txtBytes, $textBody, (int)$pendingReq[$tok]['staff_userid']);
					}
					else
					{
						$result = $this->approveReqFromRaw($pendingReq[$tok], $raw, 'imap');
					}
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $tok, 'error' => $result['error']);
					}
					else
					{
						$processed[] = array(
							'token' => $tok,
							'file' => isset($result['filename']) ? $result['filename'] : $txtName,
							'kind' => 'license_txt',
							'via' => 'imap',
							'status' => 'approved',
							'message_url' => isset($result['message_url']) ? $result['message_url'] : '',
						);
						unset($pendingReq[$tok]);
						@imap_setflag_full($imap, (string)$msgno, '\\Seen');
					}
				}
				elseif (isset($pendingLic[$tok]))
				{
					$structure = imap_fetchstructure($imap, $msgno);
					$parts = array();
					$this->flattenImapParts($structure, '', $parts);
					$srcBytes = null;
					$srcName = 'Source.src';
					foreach ($parts as $part)
					{
						$name = isset($part['filename']) ? (string)$part['filename'] : '';
						if ($name === '' || !preg_match('/\.src$/i', $name))
						{
							continue;
						}
						$data = $this->decodeImapPart($imap, $msgno, $part);
						if ($data !== null && $data !== '')
						{
							$srcBytes = $data;
							$srcName = $name;
							break;
						}
					}
					if ($srcBytes !== null)
					{
						$result = $this->mail->returnSrcToTicket($pendingLic[$tok], $srcName, $srcBytes, (int)$pendingLic[$tok]['staff_userid']);
						if (!empty($result['error']))
						{
							$errors[] = array('token' => $tok, 'error' => $result['error']);
						}
						else
						{
							$processed[] = array(
								'token' => $tok,
								'file' => $srcName,
								'node' => isset($result['attach_nodeid']) ? $result['attach_nodeid'] : 0,
								'kind' => 'src',
								'via' => 'imap',
							);
							unset($pendingLic[$tok]);
							@imap_setflag_full($imap, (string)$msgno, '\\Seen');
						}
					}
					else
					{
						$result = $this->returnLicFromRaw($pendingLic[$tok], $raw, 'imap');
						if (!empty($result['error']))
						{
							$errors[] = array('token' => $tok, 'error' => $result['error']);
						}
						else
						{
							$processed[] = $result;
							unset($pendingLic[$tok]);
							@imap_setflag_full($imap, (string)$msgno, '\\Seen');
						}
					}
				}
			}
		}

		imap_close($imap);
		return array(
			'ok' => true,
			'processed' => $processed,
			'errors' => $errors,
			'checked' => count($ids),
			'pending_lic_left' => count($pendingLic),
			'pending_req_left' => count($pendingReq),
		);
	}

	/**
	 * @param array $rec
	 * @param string $raw
	 * @param string $via
	 * @return array
	 */
	public function approveReqFromRaw(array $rec, $raw, $via = 'maildir')
	{
		$parsedTxt = $this->extractAttachmentFromRfc822($raw, 'txt');
		$textBody = $this->extractTextFromRfc822($raw);
		$filename = !empty($parsedTxt['filename']) ? $parsedTxt['filename'] : 'license.txt';
		$bytes = !empty($parsedTxt['bytes']) ? $parsedTxt['bytes'] : null;
		$kind = 'license_txt';
		if ($bytes === null || trim((string)$bytes) === '')
		{
			$body = trim((string)$textBody);
			if (strlen($body) >= 12)
			{
				$bytes = $body . "\n";
				$filename = 'license.txt';
				$kind = 'license_txt_body';
				$textBody = '';
			}
		}
		if ($bytes === null || trim((string)$bytes) === '')
		{
			return array('error' => 'No .txt license attachment or usable reply text for purchase request');
		}
		$result = $this->request->approveWithLicense($rec, $filename, $bytes, $textBody, (int)$rec['staff_userid']);
		if (!empty($result['error']))
		{
			return $result;
		}
		return array(
			'token' => $rec['token'],
			'file' => isset($result['filename']) ? $result['filename'] : $filename,
			'kind' => $kind,
			'via' => $via,
			'status' => 'approved',
			'message_url' => isset($result['message_url']) ? $result['message_url'] : '',
			'text_nodeid' => isset($result['text_nodeid']) ? $result['text_nodeid'] : 0,
		);
	}

	/**
	 * @param array $rec
	 * @param string $raw
	 * @param string $via
	 * @return array
	 */
	public function returnLicFromRaw(array $rec, $raw, $via = 'maildir')
	{
		$parsed = $this->extractAttachmentFromRfc822($raw, 'src');
		if (!empty($parsed['bytes']))
		{
			$result = $this->mail->returnSrcToTicket($rec, $parsed['filename'], $parsed['bytes'], (int)$rec['staff_userid']);
			if (!empty($result['error']))
			{
				return $result;
			}
			return array(
				'token' => $rec['token'],
				'file' => $parsed['filename'],
				'node' => isset($result['attach_nodeid']) ? $result['attach_nodeid'] : 0,
				'via' => $via,
				'kind' => 'src',
			);
		}
		$text = $this->extractTextFromRfc822($raw);
		if ($text === '')
		{
			return array('error' => 'No .src attachment and no usable reply text');
		}
		$result = $this->mail->rejectReplyToTicket($rec, $text, (int)$rec['staff_userid']);
		if (!empty($result['error']))
		{
			return $result;
		}
		return array(
			'token' => $rec['token'],
			'kind' => 'rejected',
			'text_node' => isset($result['text_nodeid']) ? $result['text_nodeid'] : 0,
			'via' => $via,
		);
	}

	/**
	 * Extract .src / .txt (or other ext) attachment bytes from RFC822.
	 * Handles nested multipart, base64, quoted-printable, 7bit/8bit.
	 *
	 * @param string $raw
	 * @param string $ext e.g. src|txt
	 * @return array{filename:string,bytes:?string}
	 */
	public function extractAttachmentFromRfc822($raw, $ext)
	{
		$ext = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$ext));
		$defaultName = ($ext === 'src') ? 'Source.src' : ('license.' . $ext);
		$filename = $defaultName;
		$raw = (string)$raw;

		if (preg_match('/filename\*=(?:UTF-8\'\')?([^;\r\n]+)/i', $raw, $m)
			&& preg_match('/\.' . preg_quote($ext, '/') . '$/i', urldecode(trim($m[1], "\"' "))))
		{
			$filename = basename(urldecode(trim($m[1], "\"' ")));
		}
		elseif (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.' . preg_quote($ext, '/') . ')"?/i', $raw, $m)
			|| preg_match('/name="?([^";\r\n]+\.' . preg_quote($ext, '/') . ')"?/i', $raw, $m))
		{
			$filename = basename(urldecode(trim($m[1], "\"' ")));
		}

		$parts = $this->splitMimeParts($raw);
		foreach ($parts as $part)
		{
			if (!is_string($part) || $part === '')
			{
				continue;
			}
			$isAttach = preg_match('/\.' . preg_quote($ext, '/') . '/i', $part)
				&& preg_match('/filename|name=/i', $part);
			// Also accept octet-stream / unnamed when Content-Disposition attachment and we know ext from outer filename
			if (!$isAttach && $ext === 'src' && preg_match('/Content-Disposition:\s*attachment/i', $part)
				&& preg_match('/\.' . preg_quote($ext, '/') . '/i', $part))
			{
				$isAttach = true;
			}
			if (!$isAttach)
			{
				continue;
			}
			if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.' . preg_quote($ext, '/') . ')"?/i', $part, $fm)
				|| preg_match('/name="?([^";\r\n]+\.' . preg_quote($ext, '/') . ')"?/i', $part, $fm))
			{
				$filename = basename(urldecode(trim($fm[1], "\"' ")));
			}
			if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
			{
				continue;
			}
			$bodyTxt = preg_replace('/\r?\n--[^\r\n]*$/s', '', $body[1]);
			$bytes = $this->decodeTransferEncoding($part, $bodyTxt);
			$minLen = ($ext === 'src') ? 20 : 3;
			if (is_string($bytes) && strlen(trim($bytes)) > $minLen)
			{
				return array('filename' => $filename, 'bytes' => $bytes);
			}
		}

		// Fallback regex for base64 bodies near .ext filenames
		$reExt = preg_quote($ext, '/');
		if (preg_match('/(?:filename|name)="?[^"\n]+\.' . $reExt . '"?[\s\S]*?Content-Transfer-Encoding:\s*base64[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m)
			|| preg_match('/Content-Transfer-Encoding:\s*base64[\s\S]*?(?:filename|name)="?[^"\n]+\.' . $reExt . '"?[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m))
		{
			$bytes = base64_decode(preg_replace('/\s+/', '', $m[1]));
			if (is_string($bytes) && $bytes !== '')
			{
				return array('filename' => $filename, 'bytes' => $bytes);
			}
		}

		return array('filename' => $filename, 'bytes' => null);
	}

	/**
	 * @param string $raw
	 * @return string
	 */
	public function extractTextFromRfc822($raw)
	{
		$raw = (string)$raw;
		$candidates = array();
		$parts = $this->splitMimeParts($raw);
		foreach ($parts as $part)
		{
			if (!is_string($part) || $part === '')
			{
				continue;
			}
			if (!preg_match('/Content-Type:\s*text\/plain/i', $part))
			{
				continue;
			}
			if (preg_match('/name=|filename=/i', $part) && preg_match('/\.(src|txt|lic|zip|pdf)\b/i', $part))
			{
				// skip attachments that happen to be text/*
				continue;
			}
			if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
			{
				continue;
			}
			$chunk = $this->decodeTransferEncoding($part, preg_replace('/\r?\n--[^\r\n]*$/s', '', $body[1]));
			if (is_string($chunk) && $chunk !== '')
			{
				$candidates[] = $chunk;
			}
		}
		if (!$candidates)
		{
			if (preg_match_all(
				'/Content-Type:\s*text\/plain[^\r\n]*\r?\n(?:[^\r\n]+\r?\n)*\r?\n([\s\S]*?)(?=\r?\n--|\z)/i',
				$raw,
				$mm,
				PREG_SET_ORDER
			))
			{
				foreach ($mm as $row)
				{
					$candidates[] = $this->decodeTransferEncoding($row[0], $row[1]);
				}
			}
		}
		if (!$candidates && preg_match('/\r?\n\r?\n([\s\S]+)$/', $raw, $m))
		{
			$candidates[] = $m[1];
		}

		$best = '';
		foreach ($candidates as $c)
		{
			$t = $this->cleanReplyText($c);
			if (strlen($t) > strlen($best))
			{
				$best = $t;
			}
		}
		return $best;
	}

	/**
	 * @param string $text
	 * @return string[]
	 */
	public function extractAllTokens($text)
	{
		$out = array();
		if (preg_match_all('/\b(VBDL-(?:REQ|LIC)-[A-Z0-9\-]+)\b/i', (string)$text, $mm))
		{
			foreach ($mm[1] as $t)
			{
				$u = strtoupper($t);
				$out[$u] = $u;
			}
		}
		return array_values($out);
	}

	/**
	 * @param array $rows
	 * @return array
	 */
	protected function indexByToken(array $rows)
	{
		$out = array();
		foreach ($rows as $row)
		{
			if (!empty($row['token']))
			{
				$out[strtoupper((string)$row['token'])] = $row;
			}
		}
		return $out;
	}

	/**
	 * Recursively collect MIME parts (handles nested multiparts).
	 *
	 * @param string $raw
	 * @return string[]
	 */
	protected function splitMimeParts($raw)
	{
		$raw = (string)$raw;
		$parts = array($raw);
		$frontiers = array($raw);
		$seenBoundaries = array();
		$seen = 0;
		while ($frontiers && $seen < 40)
		{
			$chunk = array_shift($frontiers);
			$seen++;
			if (!preg_match('/boundary=("?)([^";\r\n]+)\1/i', $chunk, $b))
			{
				continue;
			}
			$boundary = $b[2];
			if (isset($seenBoundaries[$boundary]))
			{
				continue;
			}
			$seenBoundaries[$boundary] = 1;
			$bits = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?\r?\n/', $chunk);
			if (!$bits || count($bits) < 2)
			{
				continue;
			}
			foreach ($bits as $idx => $bit)
			{
				if (!is_string($bit) || trim($bit) === '')
				{
					continue;
				}
				$parts[] = $bit;
				// Skip preamble (idx 0): it still carries the parent Content-Type/boundary.
				if ($idx === 0)
				{
					continue;
				}
				if (preg_match('/Content-Type:\s*multipart\//i', $bit)
					&& preg_match('/boundary=("?)([^";\r\n]+)\1/i', $bit, $nb)
					&& empty($seenBoundaries[$nb[2]]))
				{
					$frontiers[] = $bit;
				}
			}
		}
		return $parts;
	}

	/**
	 * @param string $headersOrPart
	 * @param string $body
	 * @return string|null
	 */
	protected function decodeTransferEncoding($headersOrPart, $body)
	{
		$body = (string)$body;
		if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $headersOrPart))
		{
			$decoded = base64_decode(preg_replace('/\s+/', '', $body), true);
			if ($decoded === false)
			{
				$decoded = base64_decode(preg_replace('/\s+/', '', $body));
			}
			return is_string($decoded) ? $decoded : null;
		}
		if (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $headersOrPart))
		{
			return quoted_printable_decode($body);
		}
		// 7bit / 8bit / binary / missing
		return $body;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected function cleanReplyText($text)
	{
		$text = (string)$text;
		$text = preg_replace('/\r\n?/', "\n", $text);
		if (stripos($text, '<html') !== false || stripos($text, '<body') !== false)
		{
			$text = preg_replace('/<style[\s\S]*?<\/style>/i', '', $text);
			$text = preg_replace('/<script[\s\S]*?<\/script>/i', '', $text);
			$text = strip_tags($text);
			$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		}
		$lines = preg_split('/\n/', $text);
		$out = array();
		foreach ($lines as $line)
		{
			$trim = rtrim($line);
			if (preg_match('/^>/', $trim))
			{
				continue;
			}
			if (preg_match('/^(-{2,}\s*$|_{5,}|From:\s|Sent:\s|To:\s|Subject:\s|Content-Type:)/i', $trim))
			{
				break;
			}
			if (preg_match('/^On .+ wrote:$/i', $trim))
			{
				break;
			}
			$out[] = $trim;
		}
		$text = trim(implode("\n", $out));
		$text = preg_replace("/\n{3,}/", "\n\n", $text);
		if (strlen($text) < 8)
		{
			return '';
		}
		return $text;
	}

	/**
	 * @param string $dir
	 * @param array $files
	 * @param int $depth
	 */
	public function scanMaildir($dir, array &$files, $depth = 0)
	{
		if ($depth > 6 || !is_dir($dir) || !@is_readable($dir))
		{
			return;
		}
		$ents = @scandir($dir);
		if (!$ents)
		{
			return;
		}
		foreach ($ents as $e)
		{
			if ($e === '.' || $e === '..')
			{
				continue;
			}
			if (strpos($e, 'dovecot') === 0 || $e === 'subscriptions' || $e === 'maildirfolder'
				|| $e === 'dovecot-uidlist' || $e === 'dovecot.index' || $e === 'dovecot.index.cache')
			{
				continue;
			}
			$path = $dir . '/' . $e;
			if (is_dir($path))
			{
				$this->scanMaildir($path, $files, $depth + 1);
			}
			elseif (is_file($path) && @is_readable($path) && filesize($path) > 80)
			{
				$files[] = $path;
			}
		}
	}

	protected function flattenImapParts($structure, $prefix, array &$out)
	{
		if (!isset($structure->parts) || !is_array($structure->parts))
		{
			$out[] = array(
				'section' => $prefix === '' ? '1' : $prefix,
				'filename' => $this->imapPartFilename($structure),
				'encoding' => isset($structure->encoding) ? (int)$structure->encoding : 0,
			);
			return;
		}
		foreach ($structure->parts as $i => $part)
		{
			$section = ($prefix === '' ? '' : ($prefix . '.')) . (string)($i + 1);
			if (!empty($part->parts))
			{
				$this->flattenImapParts($part, $section, $out);
			}
			else
			{
				$out[] = array(
					'section' => $section,
					'filename' => $this->imapPartFilename($part),
					'encoding' => isset($part->encoding) ? (int)$part->encoding : 0,
				);
			}
		}
	}

	protected function imapPartFilename($part)
	{
		$filename = '';
		if (!empty($part->dparameters) && is_array($part->dparameters))
		{
			foreach ($part->dparameters as $p)
			{
				if (strtolower($p->attribute) === 'filename')
				{
					$filename = $p->value;
				}
			}
		}
		if ($filename === '' && !empty($part->parameters) && is_array($part->parameters))
		{
			foreach ($part->parameters as $p)
			{
				if (strtolower($p->attribute) === 'name')
				{
					$filename = $p->value;
				}
			}
		}
		return (string)$filename;
	}

	/**
	 * @param resource $imap
	 * @param int $msgno
	 * @param array $part
	 * @return string|null
	 */
	protected function decodeImapPart($imap, $msgno, array $part)
	{
		$data = imap_fetchbody($imap, $msgno, $part['section']);
		if ($data === false || $data === '')
		{
			return null;
		}
		$enc = (int)$part['encoding'];
		if ($enc === 3)
		{
			$data = base64_decode($data);
		}
		elseif ($enc === 4)
		{
			$data = quoted_printable_decode($data);
		}
		return ($data !== false && $data !== '') ? $data : null;
	}

	protected function pollStampPath()
	{
		$candidates = array(
			dirname(__FILE__) . '/../../../../cache/vbdl_inbox_poll.stamp',
			dirname(__FILE__) . '/../../../cache/vbdl_inbox_poll.stamp',
			sys_get_temp_dir() . '/vbdl_inbox_poll.stamp',
		);
		foreach ($candidates as $p)
		{
			$dir = dirname($p);
			if (is_dir($dir) && @is_writable($dir))
			{
				return $p;
			}
		}
		return $candidates[count($candidates) - 1];
	}

	protected function isThrottled($seconds)
	{
		$path = $this->pollStampPath();
		if (!is_file($path))
		{
			return false;
		}
		$age = time() - (int)@filemtime($path);
		return $age >= 0 && $age < (int)$seconds;
	}

	protected function touchPollStamp()
	{
		$path = $this->pollStampPath();
		@file_put_contents($path, (string)time());
	}
}
