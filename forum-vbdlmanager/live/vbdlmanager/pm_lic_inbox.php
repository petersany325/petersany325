<?php
/**
 * SeDiv license inbox: poll IMAP for returned .src and post into Message Center tickets.
 *
 * Cron example:
 *   wget -q -O - "https://forum.hdd-land.com/vbdlmanager/pm_lic_inbox.php?key=YOUR_KEY&do=poll"
 *
 * Manual staff upload of .src is also available via pm_lic_email.php?do=return_upload
 */
define('THIS_SCRIPT', 'vbdl_pm_lic_inbox');
define('CSRF_PROTECTION', false);

header('Content-Type: application/json; charset=utf-8');

$forumRoot = dirname(__FILE__) . '/..';
chdir($forumRoot);

if (is_file($forumRoot . '/core/includes/init.php'))
{
	require_once $forumRoot . '/core/includes/init.php';
}
elseif (is_file($forumRoot . '/includes/init.php'))
{
	require_once $forumRoot . '/includes/init.php';
}
else
{
	echo json_encode(array('ok' => false, 'error' => 'Forum bootstrap missing'));
	exit;
}

require_once $forumRoot . '/core/packages/vbdlmanager/library/Bootstrap.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseMail.php';
require_once $forumRoot . '/core/packages/vbdlmanager/library/LicenseRequest.php';

global $vbulletin, $db, $table_prefix;
$prefix = isset($table_prefix) ? $table_prefix : '';
$database = isset($db) ? $db : (isset($vbulletin->db) ? $vbulletin->db : null);

try
{
	vbdl_Bootstrap::init($database, $prefix);
}
catch (Exception $e)
{
	echo json_encode(array('ok' => false, 'error' => 'Download Manager unavailable'));
	exit;
}

$repo = vbdl_Bootstrap::$repo;
$do = isset($_REQUEST['do']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_REQUEST['do'])) : 'poll';
$key = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
$expected = trim((string)$repo->getSetting('license_inbox_key', ''));

$userinfo = isset($vbulletin->userinfo) ? $vbulletin->userinfo : array('userid' => 0);
$isAdmin = !empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6;
$keyOk = ($expected !== '' && hash_equals($expected, $key));

if (!$keyOk && !$isAdmin)
{
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'Forbidden'));
	exit;
}

function vbdl_inbox_db()
{
	static $mysqli = null;
	if ($mysqli instanceof mysqli)
	{
		return $mysqli;
	}
	$config = array();
	$forumRoot = dirname(__FILE__) . '/..';
	$cfg = $forumRoot . '/core/includes/config.php';
	if (!is_file($cfg))
	{
		$cfg = $forumRoot . '/includes/config.php';
	}
	if (!is_file($cfg))
	{
		return null;
	}
	include $cfg;
	$host = $config['MasterServer']['servername'] ?? 'localhost';
	$port = !empty($config['MasterServer']['port']) ? (int)$config['MasterServer']['port'] : 3306;
	$user = $config['MasterServer']['username'] ?? '';
	$pass = $config['MasterServer']['password'] ?? '';
	$dbn = $config['Database']['dbname'] ?? '';
	$mysqli = @new mysqli($host, $user, $pass, $dbn, $port);
	if ($mysqli->connect_errno)
	{
		return null;
	}
	$mysqli->set_charset('utf8mb4');
	return $mysqli;
}

function vbdl_inbox_prefix()
{
	global $table_prefix;
	if (isset($table_prefix) && is_string($table_prefix))
	{
		return $table_prefix;
	}
	return '';
}

$m = vbdl_inbox_db();
if (!$m)
{
	echo json_encode(array('ok' => false, 'error' => 'Database unavailable'));
	exit;
}
$lm = new vbdl_LicenseMail($m, vbdl_inbox_prefix(), $repo, vbdl_Bootstrap::$acl);
$lr = new vbdl_LicenseRequest($m, vbdl_inbox_prefix(), $repo, vbdl_Bootstrap::$acl, $lm);

if ($do !== 'poll' && $do !== 'purge')
{
	echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
	exit;
}

if ($do === 'purge')
{
	$purged = $lm->purgeExpiredSrcFiles(80);
	echo json_encode(array(
		'ok' => true,
		'purged' => $purged,
		'retention_days' => $lm->srcRetentionDays(),
		'count' => count($purged),
	));
	exit;
}

$host = trim((string)$repo->getSetting('license_imap_host', ''));
$user = trim((string)$repo->getSetting('license_imap_user', ''));
$pass = (string)$repo->getSetting('license_imap_pass', '');
$port = (int)$repo->getSetting('license_imap_port', '993');
$flags = trim((string)$repo->getSetting('license_imap_flags', '/imap/ssl/novalidate-cert'));

// Prefer local Maildir when readable (same host as forum) — no IMAP password needed.
$maildir = trim((string)$repo->getSetting('license_maildir', '/home/hddrecov/mail/hdd-land.com/info'));
if ($maildir !== '' && is_dir($maildir) && @is_readable($maildir))
{
	$processed = array();
	$errors = array();
	$skipped = array();
	$files = array();
	vbdl_inbox_scan_maildir($maildir, $files, 0);
	// newest first
	usort($files, function ($a, $b) {
		return filemtime($b) - filemtime($a);
	});
	$files = array_slice($files, 0, 120);
	foreach ($files as $path)
	{
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '')
		{
			continue;
		}
		$result = vbdl_inbox_handle_rfc822($lm, $lr, $raw, 'maildir');
		if (!empty($result['processed']))
		{
			foreach ($result['processed'] as $row)
			{
				$processed[] = $row;
			}
		}
		if (!empty($result['errors']))
		{
			foreach ($result['errors'] as $row)
			{
				$errors[] = $row;
			}
		}
		if (!empty($result['skipped']))
		{
			foreach ($result['skipped'] as $row)
			{
				$skipped[] = $row;
			}
		}
	}
	$purged = $lm->purgeExpiredSrcFiles(40);
	echo json_encode(array(
		'ok' => true,
		'mode' => 'maildir',
		'maildir' => $maildir,
		'processed' => $processed,
		'errors' => $errors,
		'skipped' => array_slice($skipped, 0, 30),
		'checked' => count($files),
		'purged' => $purged,
		'purged_count' => count($purged),
	));
	exit;
}

if ($host === '' || $user === '' || $pass === '')
{
	echo json_encode(array(
		'ok' => true,
		'skipped' => 1,
		'message' => 'IMAP not configured — set license_imap_* in AdminCP. Manual .src upload still works.',
	));
	exit;
}

if (!function_exists('imap_open'))
{
	echo json_encode(array('ok' => false, 'error' => 'PHP IMAP extension not installed'));
	exit;
}

$mailbox = '{' . $host . ':' . ($port > 0 ? $port : 993) . $flags . '}INBOX';
$imap = @imap_open($mailbox, $user, $pass);
if (!$imap)
{
	echo json_encode(array('ok' => false, 'error' => 'IMAP login failed: ' . imap_last_error()));
	exit;
}

$processed = array();
$errors = array();
$skipped = array();
$ids = imap_search($imap, 'UNSEEN') ?: array();
// Also scan recent mail with .src in case flags are wrong
if (!$ids)
{
	$ids = imap_search($imap, 'ALL') ?: array();
	$ids = array_slice(array_reverse($ids), 0, 40);
}

foreach ($ids as $msgno)
{
	$header = imap_fetchheader($imap, $msgno);
	$body = imap_body($imap, $msgno);
	$raw = $header . "\n" . $body;
	$result = vbdl_inbox_handle_rfc822($lm, $lr, $raw, 'imap');
	$did = false;
	if (!empty($result['processed']))
	{
		foreach ($result['processed'] as $row)
		{
			$processed[] = $row;
			$did = true;
		}
	}
	if (!empty($result['errors']))
	{
		foreach ($result['errors'] as $row)
		{
			$errors[] = $row;
		}
	}
	if (!empty($result['skipped']))
	{
		foreach ($result['skipped'] as $row)
		{
			$skipped[] = $row;
		}
	}
	if ($did)
	{
		@imap_setflag_full($imap, (string)$msgno, '\\Seen');
	}
}

imap_close($imap);
$purged = $lm->purgeExpiredSrcFiles(40);
echo json_encode(array(
	'ok' => true,
	'processed' => $processed,
	'errors' => $errors,
	'skipped' => array_slice($skipped, 0, 30),
	'checked' => count($ids),
	'purged' => $purged,
	'purged_count' => count($purged),
));
exit;

/**
 * Resolve a license reply RFC822 into ticket updates.
 * Matching priority (high → low):
 *   1) X-VBDL-Token header
 *   2) Token in Subject
 *   3) Token in body / quoted mail
 *   4) Unique open subject fallback (exactly one still-sent ticket)
 * Also ignores own outbound (From info@), auto-acks, and already-seen Message-IDs.
 */
function vbdl_inbox_handle_rfc822($lm, $lr, $raw, $via = 'maildir')
{
	$processed = array();
	$errors = array();
	$skipped = array();
	$raw = (string)$raw;

	$subject = '';
	if (preg_match('/^Subject:\s*(.+)$/mi', $raw, $sm))
	{
		$subject = trim(preg_replace('/\s+/', ' ', $sm[1]));
		if (function_exists('iconv_mime_decode'))
		{
			$decoded = @iconv_mime_decode($subject, 0, 'UTF-8');
			if (is_string($decoded) && $decoded !== '')
			{
				$subject = $decoded;
			}
		}
	}

	$from = '';
	if (preg_match('/^From:\s*(.+)$/mi', $raw, $fm))
	{
		$from = trim($fm[1]);
	}
	// Never process our own outbound copies that land in INBOX / cur.
	if ($from !== '' && preg_match('/info@hdd-land\.com/i', $from))
	{
		$skipped[] = array('reason' => 'own_outbound_from_info', 'subject' => $subject, 'from' => $from);
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}

	$messageId = '';
	if (preg_match('/^Message-ID:\s*(.+)$/mi', $raw, $mmid))
	{
		$messageId = trim($mmid[1]);
	}
	if ($messageId !== '' && $lm->hasSeenMessageId($messageId))
	{
		$skipped[] = array('reason' => 'already_seen_message_id', 'message_id' => $messageId, 'subject' => $subject);
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}

	$headerToken = '';
	if (preg_match('/^X-VBDL-Token:\s*([A-Za-z0-9\-]+)/mi', $raw, $htm))
	{
		$headerToken = strtoupper(trim($htm[1]));
	}
	// In-Reply-To / References often echo our outbound Message-ID which embeds the token.
	if ($headerToken === '')
	{
		$threadHeads = '';
		if (preg_match('/^In-Reply-To:\s*(.+)$/mi', $raw, $irm))
		{
			$threadHeads .= ' ' . $irm[1];
		}
		if (preg_match('/^References:\s*([\s\S]*?)(?=\r?\n(?![ \t])|\z)/mi', $raw, $rfm))
		{
			$threadHeads .= ' ' . $rfm[1];
		}
		if (preg_match('/\b(VBDL-(?:LIC|REQ)-[A-Z0-9\-]+)\b/i', $threadHeads, $ttm))
		{
			$headerToken = strtoupper($ttm[1]);
		}
	}
	$headerKind = '';
	if (preg_match('/^X-VBDL-Kind:\s*([A-Za-z0-9_\-]+)/mi', $raw, $hkm))
	{
		$headerKind = strtolower(trim($hkm[1]));
	}

	// Purchase-request path first (header token / subject / body).
	$reqTokens = array();
	if ($headerToken !== '' && strpos($headerToken, 'VBDL-REQ-') === 0)
	{
		$reqTokens[] = $headerToken;
	}
	foreach ($lr->extractAllTokensFromText($subject) as $t)
	{
		if (!in_array($t, $reqTokens, true))
		{
			$reqTokens[] = $t;
		}
	}
	foreach ($lr->extractAllTokensFromText($raw) as $t)
	{
		if (!in_array($t, $reqTokens, true))
		{
			$reqTokens[] = $t;
		}
	}
	$reqToken = $reqTokens ? $reqTokens[0] : '';
	if ($reqToken === '' && ($headerKind === 'req' || stripos($subject, 'License Request') !== false))
	{
		$bySubReq = $lr->findSentBySubject($subject !== '' ? $subject : $raw);
		if ($bySubReq)
		{
			$reqToken = $bySubReq['token'];
			$skipped[] = array('token' => $reqToken, 'reason' => 'matched_req_by_subject', 'subject' => $subject);
		}
	}

	if ($reqToken !== '')
	{
		$rec = $lr->findByToken($reqToken);
		if ($rec && $rec['status'] === 'sent')
		{
			$parsedTxt = vbdl_inbox_extract_txt_from_rfc822($raw);
			if (!empty($parsedTxt['bytes']))
			{
				$textBody = vbdl_inbox_extract_text_from_rfc822($raw);
				$result = $lr->approveWithLicense($rec, $parsedTxt['filename'], $parsedTxt['bytes'], $textBody, (int)$rec['staff_userid']);
				if (!empty($result['error']))
				{
					$errors[] = array('token' => $reqToken, 'error' => $result['error']);
				}
				else
				{
					$processed[] = array(
						'token' => $reqToken,
						'file' => $parsedTxt['filename'],
						'kind' => 'license_txt',
						'via' => $via,
						'match' => ($headerToken !== '' ? 'x_vbdl_token' : 'token'),
					);
					if ($messageId !== '')
					{
						$lm->markSeenMessageId($messageId, $reqToken, 'license_txt');
					}
				}
			}
			else
			{
				// Plain-text activator reply (instructions / rejection) — still post into the user ticket.
				$textBody = vbdl_inbox_extract_text_from_rfc822($raw);
				$textBody = vbdl_inbox_normalize_reply_charset($textBody);
				if ($textBody !== '' && !vbdl_inbox_is_auto_ack($textBody))
				{
					$result = $lr->rejectWithText($rec, $textBody, (int)$rec['staff_userid']);
					if (!empty($result['error']))
					{
						$errors[] = array('token' => $reqToken, 'error' => $result['error']);
					}
					else
					{
						$processed[] = array(
							'token' => $reqToken,
							'kind' => 'rejected_text',
							'text_node' => isset($result['text_nodeid']) ? $result['text_nodeid'] : 0,
							'via' => $via,
						);
						if ($messageId !== '')
						{
							$lm->markSeenMessageId($messageId, $reqToken, 'rejected_text');
						}
					}
				}
				else
				{
					$skipped[] = array(
						'token' => $reqToken,
						'reason' => ($textBody === '' ? 'no_txt_and_no_text' : 'auto_ack_ignored'),
						'subject' => $subject,
					);
					// Mark auto-acks seen so they do not keep reappearing.
					if ($messageId !== '' && $textBody !== '' && vbdl_inbox_is_auto_ack($textBody))
					{
						$lm->markSeenMessageId($messageId, $reqToken, 'auto_ack');
					}
				}
			}
		}
		elseif ($rec)
		{
			$skipped[] = array('token' => $reqToken, 'reason' => 'already_' . $rec['status']);
			if ($messageId !== '')
			{
				$lm->markSeenMessageId($messageId, $reqToken, 'already_' . $rec['status']);
			}
		}
		else
		{
			$skipped[] = array('token' => $reqToken, 'reason' => 'unknown_req_token');
		}
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}

	// Active License SeDiv (.src) path
	$tokens = array();
	if ($headerToken !== '' && strpos($headerToken, 'VBDL-LIC-') === 0)
	{
		$tokens[] = $headerToken;
	}
	foreach ($lm->extractAllTokensFromText($subject) as $t)
	{
		if (!in_array($t, $tokens, true))
		{
			$tokens[] = $t;
		}
	}
	foreach ($lm->extractAllTokensFromText($raw) as $t)
	{
		if (!in_array($t, $tokens, true))
		{
			$tokens[] = $t;
		}
	}

	$candidates = array();
	$matchHow = 'token';
	foreach ($tokens as $token)
	{
		$rec = $lm->findByToken($token);
		if (!$rec)
		{
			$skipped[] = array('token' => $token, 'reason' => 'unknown_token');
			continue;
		}
		if ($rec['status'] === 'returned' || $rec['status'] === 'rejected')
		{
			$skipped[] = array('token' => $token, 'reason' => 'already_' . $rec['status']);
			continue;
		}
		$candidates[] = $rec;
	}
	if ($headerToken !== '' && strpos($headerToken, 'VBDL-LIC-') === 0)
	{
		$matchHow = 'x_vbdl_token';
	}
	elseif ($tokens && preg_match('/\bVBDL-LIC-/i', $subject))
	{
		$matchHow = 'subject_token';
	}

	// Subject fallback when reply has .src but no usable open token — unique open ticket only.
	if (!$candidates)
	{
		$bySub = $lm->findSentBySubject($subject !== '' ? $subject : $raw);
		if ($bySub)
		{
			$candidates[] = $bySub;
			$matchHow = 'subject_unique';
			$skipped[] = array('token' => $bySub['token'], 'reason' => 'matched_by_subject', 'subject' => $subject);
		}
		else
		{
			// Detect ambiguity for diagnostics
			$cleanSub = trim(preg_replace('/^(?:Re|Fw|Fwd|AW|SV|Antw)\s*:\s*/i', '', $subject));
			if ($cleanSub !== '' && preg_match('/Subject license /i', $cleanSub))
			{
				$skipped[] = array('reason' => 'subject_ambiguous_or_none', 'subject' => $subject);
			}
		}
	}

	if (!$candidates)
	{
		$hasSrc = (bool)preg_match('/\.src/i', $raw);
		if ($hasSrc)
		{
			$alreadyHandled = false;
			foreach ($skipped as $sk)
			{
				if (!empty($sk['reason']) && strpos((string)$sk['reason'], 'already_') === 0)
				{
					$alreadyHandled = true;
					break;
				}
			}
			$skipped[] = array(
				'reason' => $alreadyHandled ? 'src_already_handled' : 'src_without_matching_ticket',
				'subject' => $subject,
			);
		}
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}

	$parsed = vbdl_inbox_extract_src_from_rfc822($raw);
	if (!empty($parsed['bytes']))
	{
		// One .src attachment → bind to the first still-sent candidate (header/subject token preferred).
		$rec = $candidates[0];
		$result = $lm->returnSrcToTicket($rec, $parsed['filename'], $parsed['bytes'], (int)$rec['staff_userid']);
		if (!empty($result['error']))
		{
			$errors[] = array('token' => $rec['token'], 'error' => $result['error']);
		}
		else
		{
			$processed[] = array(
				'token' => $rec['token'],
				'file' => $parsed['filename'],
				'node' => $result['attach_nodeid'],
				'via' => $via,
				'kind' => 'src',
				'match' => $matchHow,
			);
			if ($messageId !== '')
			{
				$lm->markSeenMessageId($messageId, $rec['token'], 'src');
			}
		}
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}

	// No .src — plain-text rejection for the primary candidate only (never auto-acks).
	$rec = $candidates[0];
	$text = vbdl_inbox_extract_text_from_rfc822($raw);
	$text = vbdl_inbox_normalize_reply_charset($text);
	if ($text === '')
	{
		$errors[] = array('token' => $rec['token'], 'error' => 'No .src attachment and no usable reply text');
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}
	if (vbdl_inbox_is_auto_ack($text))
	{
		$skipped[] = array('token' => $rec['token'], 'reason' => 'auto_ack_ignored', 'subject' => $subject);
		if ($messageId !== '')
		{
			$lm->markSeenMessageId($messageId, $rec['token'], 'auto_ack');
		}
		return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
	}
	$result = $lm->rejectReplyToTicket($rec, $text, (int)$rec['staff_userid']);
	if (!empty($result['error']))
	{
		$errors[] = array('token' => $rec['token'], 'error' => $result['error']);
	}
	else
	{
		$processed[] = array(
			'token' => $rec['token'],
			'kind' => 'rejected',
			'text_node' => isset($result['text_nodeid']) ? $result['text_nodeid'] : 0,
			'via' => $via,
			'match' => $matchHow,
		);
		if ($messageId !== '')
		{
			$lm->markSeenMessageId($messageId, $rec['token'], 'rejected');
		}
	}
	return array('processed' => $processed, 'errors' => $errors, 'skipped' => $skipped);
}

function vbdl_inbox_is_auto_ack($text)
{
	$t = strtolower(trim((string)$text));
	if ($t === '')
	{
		return true;
	}
	if (strlen($t) < 40 && preg_match('/thank you for your email/i', $t))
	{
		return true;
	}
	if (preg_match('/thank you for your email,\s*i will reply as soon as possible/i', $t)
		&& strlen($t) < 500)
	{
		return true;
	}
	// Common mailbox / vacation / delivery acks that must never hard-reject a ticket.
	if (strlen($t) < 600 && preg_match(
		'/(out of office|automatic reply|auto[- ]?reply|delivery status notification|mail delivery subsystem|undeliverable|i received your (mail|message|email))/i',
		$t
	))
	{
		return true;
	}
	return false;
}

/**
 * Best-effort decode of activator replies (often Windows-1251 from SeDiv).
 */
function vbdl_inbox_normalize_reply_charset($text)
{
	$text = (string)$text;
	if ($text === '')
	{
		return '';
	}
	// Already valid UTF-8 with Cyrillic?
	if (preg_match('/[\x{0400}-\x{04FF}]/u', $text))
	{
		return $text;
	}
	// Common mojibake: CP1251 interpreted as Latin-1/ISO-8859-1
	if (function_exists('mb_convert_encoding'))
	{
		$try = @mb_convert_encoding($text, 'UTF-8', 'Windows-1251');
		if (is_string($try) && $try !== '' && preg_match('/[\x{0400}-\x{04FF}]/u', $try))
		{
			return $try;
		}
	}
	if (function_exists('iconv'))
	{
		$try = @iconv('Windows-1251', 'UTF-8//IGNORE', $text);
		if (is_string($try) && $try !== '' && preg_match('/[\x{0400}-\x{04FF}]/u', $try))
		{
			return $try;
		}
	}
	return $text;
}

function vbdl_inbox_flatten_parts($structure, $prefix, array &$out)
{
	if (!isset($structure->parts) || !is_array($structure->parts))
	{
		$filename = vbdl_inbox_part_filename($structure);
		$out[] = array(
			'section' => $prefix === '' ? '1' : $prefix,
			'filename' => $filename,
			'encoding' => isset($structure->encoding) ? (int)$structure->encoding : 0,
		);
		return;
	}
	foreach ($structure->parts as $i => $part)
	{
		$section = ($prefix === '' ? '' : ($prefix . '.')) . (string)($i + 1);
		if (!empty($part->parts))
		{
			vbdl_inbox_flatten_parts($part, $section, $out);
		}
		else
		{
			$out[] = array(
				'section' => $section,
				'filename' => vbdl_inbox_part_filename($part),
				'encoding' => isset($part->encoding) ? (int)$part->encoding : 0,
			);
		}
	}
}

function vbdl_inbox_part_filename($part)
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

function vbdl_inbox_scan_maildir($dir, array &$files, $depth = 0)
{
	if ($depth > 3 || !is_dir($dir) || !@is_readable($dir))
	{
		return;
	}
	// Mailbox root: only INBOX cur/ + new/. Never scan .Sent / .Drafts / Archive
	// (those contain our own outbound copies and can false-match tickets).
	if ($depth === 0)
	{
		foreach (array('cur', 'new') as $sub)
		{
			$path = rtrim($dir, '/') . '/' . $sub;
			if (is_dir($path) && @is_readable($path))
			{
				vbdl_inbox_scan_maildir($path, $files, 1);
			}
		}
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
		// Skip dovecot index files and nested folders (cur/new are flat).
		if (strpos($e, 'dovecot') === 0 || $e === 'subscriptions' || $e === 'maildirfolder')
		{
			continue;
		}
		if (preg_match('/\.(cache|log|index|uidlist|tmp)$/i', $e))
		{
			continue;
		}
		// Explicitly ignore IMAP special folders if somehow nested.
		if ($e[0] === '.' || strcasecmp($e, 'Sent') === 0 || strcasecmp($e, 'Drafts') === 0
			|| strcasecmp($e, 'Trash') === 0 || strcasecmp($e, 'Archive') === 0
			|| strcasecmp($e, 'Junk') === 0 || strcasecmp($e, 'Spam') === 0)
		{
			continue;
		}
		$path = $dir . '/' . $e;
		if (is_dir($path))
		{
			continue;
		}
		if (is_file($path) && @is_readable($path) && filesize($path) > 200)
		{
			$files[] = $path;
		}
	}
}

function vbdl_inbox_extract_src_from_rfc822($raw)
{
	$filename = 'Source.src';
	if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.src)"?/i', $raw, $m)
		|| preg_match('/name="?([^";\r\n]+\.src)"?/i', $raw, $m))
	{
		$filename = basename(urldecode(trim($m[1], "\"' ")));
	}

	// Split on ANY MIME boundary so nested multipart/mixed replies still expose the .src part.
	$parts = preg_split('/\r?\n--[^\r\n]+(?:--)?\r?\n/', (string)$raw);
	if (!$parts || count($parts) < 2)
	{
		$parts = array($raw);
	}

	foreach ($parts as $part)
	{
		if (!is_string($part) || $part === '')
		{
			continue;
		}
		if (!preg_match('/\.src/i', $part) || !preg_match('/filename|name=/i', $part))
		{
			continue;
		}
		// Skip container parts that only declare nested multipart
		if (preg_match('/Content-Type:\s*multipart\//i', $part) && !preg_match('/Content-Transfer-Encoding:\s*(base64|quoted-printable)/i', $part))
		{
			continue;
		}
		if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
		{
			continue;
		}
		$bodyTxt = $body[1];
		$bodyTxt = preg_replace('/\r?\n--[^\r\n]*\s*$/s', '', $bodyTxt);

		$bytes = null;
		if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $part))
		{
			$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt), true);
			if ($bytes === false)
			{
				$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt));
			}
		}
		elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $part))
		{
			$bytes = quoted_printable_decode($bodyTxt);
		}
		else
		{
			// Some activators send binary/8bit attachments
			$bytes = $bodyTxt;
		}
		if (is_string($bytes) && strlen($bytes) > 20)
		{
			if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.src)"?/i', $part, $fm)
				|| preg_match('/name="?([^";\r\n]+\.src)"?/i', $part, $fm))
			{
				$filename = basename(urldecode(trim($fm[1], "\"' ")));
			}
			return array('filename' => $filename, 'bytes' => $bytes);
		}
	}

	// Fallback: name/filename .src then later base64 body (any header order)
	if (preg_match('/(?:filename|name)="?[^"\n]+\.src"?[\s\S]*?Content-Transfer-Encoding:\s*base64[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m)
		|| preg_match('/Content-Transfer-Encoding:\s*base64[\s\S]*?(?:filename|name)="?[^"\n]+\.src"?[\s\S]*?\r?\n\r?\n([A-Za-z0-9\/+\r\n=]+)/i', $raw, $m))
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
 * Extract usable plain-text body from an RFC822 message (activation failure replies).
 */
function vbdl_inbox_extract_text_from_rfc822($raw)
{
	$raw = (string)$raw;
	$candidates = array();
	$charset = 'UTF-8';
	if (preg_match('/Content-Type:\s*text\/plain[^\r\n]*charset=["\']?([^\s"\';\r\n]+)/i', $raw, $cm))
	{
		$charset = trim($cm[1]);
	}

	// Prefer text/plain parts
	if (preg_match_all(
		'/Content-Type:\s*text\/plain[^\r\n]*\r?\n(?:[^\r\n]+\r?\n)*\r?\n([\s\S]*?)(?=\r?\n--|\z)/i',
		$raw,
		$mm,
		PREG_SET_ORDER
	))
	{
		foreach ($mm as $row)
		{
			$chunk = $row[1];
			$headerBlock = $row[0];
			if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $headerBlock))
			{
				$decoded = base64_decode(preg_replace('/\s+/', '', $chunk));
				if (is_string($decoded) && $decoded !== '')
				{
					$chunk = $decoded;
				}
			}
			elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $headerBlock))
			{
				$chunk = quoted_printable_decode($chunk);
			}
			$candidates[] = $chunk;
		}
	}

	if (!$candidates)
	{
		// Single-part body after headers
		if (preg_match('/\r?\n\r?\n([\s\S]+)$/', $raw, $m))
		{
			$chunk = $m[1];
			if (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $raw))
			{
				$chunk = quoted_printable_decode($chunk);
			}
			elseif (preg_match('/Content-Transfer-Encoding:\s*base64/i', $raw))
			{
				$decoded = base64_decode(preg_replace('/\s+/', '', $chunk));
				if (is_string($decoded) && $decoded !== '')
				{
					$chunk = $decoded;
				}
			}
			$candidates[] = $chunk;
		}
	}

	$best = '';
	foreach ($candidates as $c)
	{
		if (stripos($charset, '1251') !== false || stripos($charset, 'koi8') !== false)
		{
			$c = vbdl_inbox_normalize_reply_charset($c);
		}
		$t = vbdl_inbox_clean_reply_text($c);
		if (strlen($t) > strlen($best))
		{
			$best = $t;
		}
	}
	return vbdl_inbox_normalize_reply_charset($best);
}

function vbdl_inbox_clean_reply_text($text)
{
	$text = (string)$text;
	$text = preg_replace('/\r\n?/', "\n", $text);
	// Strip HTML if present
	if (stripos($text, '<html') !== false || stripos($text, '<body') !== false)
	{
		$text = preg_replace('/<style[\s\S]*?<\/style>/i', '', $text);
		$text = preg_replace('/<script[\s\S]*?<\/script>/i', '', $text);
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	}
	$lines = preg_split('/\n/', $text);
	$out = array();
	$kept = 0;
	foreach ($lines as $line)
	{
		$trim = rtrim($line);
		// Drop common quoted / signature / mailer noise — but only after real content starts.
		if (preg_match('/^>/', $trim))
		{
			continue;
		}
		if ($kept > 0 && preg_match('/^(-{5,}\s*$|_{5,}|From:\s|Sent:\s|To:\s|Subject:\s|Content-Type:)/i', $trim))
		{
			break;
		}
		if ($kept > 0 && preg_match('/^On .+ wrote:$/i', $trim))
		{
			break;
		}
		// Leading dashed banners from SeDiv are kept as content (not treated as signature).
		$out[] = $trim;
		if (trim($trim) !== '')
		{
			$kept++;
		}
	}
	$text = trim(implode("\n", $out));
	$text = preg_replace("/\n{3,}/", "\n\n", $text);
	// Ignore tiny auto-ack fluff
	if (strlen($text) < 8)
	{
		return '';
	}
	return $text;
}

/**
 * Extract .txt license attachment from purchase-request replies.
 */
function vbdl_inbox_extract_txt_from_rfc822($raw)
{
	$filename = 'license.txt';
	if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.txt)"?/i', $raw, $m)
		|| preg_match('/name="?([^";\r\n]+\.txt)"?/i', $raw, $m))
	{
		$filename = basename(urldecode(trim($m[1], "\"' ")));
	}

	$parts = preg_split('/\r?\n--[^\r\n]+(?:--)?\r?\n/', (string)$raw);
	if (!$parts || count($parts) < 2)
	{
		$parts = array($raw);
	}
	foreach ($parts as $part)
	{
		if (!is_string($part) || $part === '')
		{
			continue;
		}
		if (!preg_match('/\.txt/i', $part) || !preg_match('/filename|name=/i', $part))
		{
			continue;
		}
		if (preg_match('/Content-Type:\s*multipart\//i', $part) && !preg_match('/Content-Transfer-Encoding:\s*(base64|quoted-printable|8bit|7bit)/i', $part))
		{
			continue;
		}
		if (!preg_match('/\r?\n\r?\n([\s\S]+)$/', $part, $body))
		{
			continue;
		}
		$bodyTxt = preg_replace('/\r?\n--[^\r\n]*\s*$/s', '', $body[1]);
		if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $part))
		{
			$bytes = base64_decode(preg_replace('/\s+/', '', $bodyTxt));
		}
		elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $part))
		{
			$bytes = quoted_printable_decode($bodyTxt);
		}
		else
		{
			$bytes = $bodyTxt;
		}
		if (is_string($bytes) && strlen(trim($bytes)) > 3)
		{
			if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";\r\n]+\.txt)"?/i', $part, $fm)
				|| preg_match('/name="?([^";\r\n]+\.txt)"?/i', $part, $fm))
			{
				$filename = basename(urldecode(trim($fm[1], "\"' ")));
			}
			return array('filename' => $filename, 'bytes' => $bytes);
		}
	}
	return array('filename' => $filename, 'bytes' => null);
}
