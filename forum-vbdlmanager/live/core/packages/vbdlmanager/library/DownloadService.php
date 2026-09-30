<?php
/**
 * Download + upload service.
 * English Free / Paid messaging.
 */

require_once dirname(__FILE__) . '/Storage/StorageFactory.php';

class vbdl_DownloadService
{
	/** @var vbdl_Repository */
	protected $repo;
	/** @var vbdl_Acl */
	protected $acl;

	public function __construct(vbdl_Repository $repo, vbdl_Acl $acl)
	{
		$this->repo = $repo;
		$this->acl = $acl;
	}

	public function allowedExtension($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$allowed = array_filter(array_map('trim', explode(',', strtolower($this->repo->getSetting('allowed_extensions', '')))));
		return $ext !== '' && in_array($ext, $allowed, true);
	}

	public function maxUploadBytes()
	{
		return (int)$this->repo->getSetting('max_upload_bytes', '52428800');
	}

	/**
	 * English VIP / contact-admin message for paid downloads.
	 * Configured entirely from AdminCP settings.
	 */
	public function vipContactMessageHtml()
	{
		$title = trim((string)$this->repo->getSetting(
			'vip_contact_title',
			'VIP Membership Required'
		));
		$message = trim((string)$this->repo->getSetting(
			'vip_contact_message',
			'This is a paid VIP download. To purchase VIP access, please contact the administrator.'
		));
		$email = trim((string)$this->repo->getSetting('vip_contact_email', ''));
		$url = trim((string)$this->repo->getSetting('vip_contact_url', ''));
		$label = trim((string)$this->repo->getSetting('vip_contact_button_label', 'Contact Administrator'));
		$telegram = $this->normalizeTelegramContactUrl(trim((string)$this->repo->getSetting('vip_contact_telegram', '')));
		$whatsapp = $this->normalizeWhatsappContactUrl(trim((string)$this->repo->getSetting('vip_contact_whatsapp', '')));
		$tgLabel = trim((string)$this->repo->getSetting('vip_telegram_button_label', 'Telegram'));
		$waLabel = trim((string)$this->repo->getSetting('vip_whatsapp_button_label', 'WhatsApp'));

		$html = '<div class="vbdl-vip-box">';
		if ($title !== '')
		{
			$html .= '<strong class="vbdl-vip-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>';
		}
		if ($message !== '')
		{
			$html .= '<p class="vbdl-vip-msg">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
		}
		$parts = array();
		if ($email !== '')
		{
			$parts[] = '<a class="vbdl-vip-link" href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a>';
		}
		if ($url !== '')
		{
			$parts[] = '<a class="vbdl-vip-btn" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="noopener">' . htmlspecialchars($label !== '' ? $label : 'Contact Administrator', ENT_QUOTES, 'UTF-8') . '</a>';
		}
		if ($telegram !== '')
		{
			$parts[] = '<a class="vbdl-vip-btn vbdl-vip-telegram" href="' . htmlspecialchars($telegram, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($tgLabel !== '' ? $tgLabel : 'Telegram', ENT_QUOTES, 'UTF-8') . '</a>';
		}
		if ($whatsapp !== '')
		{
			$parts[] = '<a class="vbdl-vip-btn vbdl-vip-whatsapp" href="' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($waLabel !== '' ? $waLabel : 'WhatsApp', ENT_QUOTES, 'UTF-8') . '</a>';
		}
		if ($parts)
		{
			$html .= '<p class="vbdl-vip-actions">' . implode(' &nbsp;|&nbsp; ', $parts) . '</p>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Normalize Telegram contact value to a URL.
	 * @username -> https://t.me/username; leave https://t.me/... unchanged.
	 */
	protected function normalizeTelegramContactUrl($value)
	{
		$value = trim((string)$value);
		if ($value === '')
		{
			return '';
		}
		if (isset($value[0]) && $value[0] === '@')
		{
			$user = ltrim($value, '@');
			$user = preg_replace('/[^A-Za-z0-9_]/', '', $user);
			return $user !== '' ? ('https://t.me/' . $user) : '';
		}
		return $value;
	}

	/**
	 * Normalize WhatsApp contact value to a URL.
	 * Digits / +phone -> https://wa.me/DIGITS; leave https://wa.me/... unchanged.
	 */
	protected function normalizeWhatsappContactUrl($value)
	{
		$value = trim((string)$value);
		if ($value === '')
		{
			return '';
		}
		if (preg_match('#^https?://#i', $value))
		{
			return $value;
		}
		$digits = preg_replace('/\D+/', '', $value);
		return $digits !== '' ? ('https://wa.me/' . $digits) : '';
	}

	public function limitedAccessMessageHtml()
	{
		$html = '<div class="panel" style="background:#fff8e8;border-color:#f0c36d">';
		$html .= '<p class="sub" style="margin:0">Limited library access. Free category files may still be available below. Contact an administrator for VIP / grant-required categories.</p>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Public download URL for a file (site-relative or absolute).
	 */
	public function downloadUrl($fileid, $absolute = true)
	{
		$fileid = (int)$fileid;
		$rel = 'vbdlmanager/download.php?fileid=' . $fileid;
		if (!$absolute)
		{
			return $rel;
		}
		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
			|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
		$host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'forum.hdd-land.com';
		return ($https ? 'https://' : 'http://') . $host . '/' . $rel;
	}

	public function deniedDownloadMessage(array $file, array $userinfo)
	{
		if ($this->acl->needsVipPurchase($file, $userinfo))
		{
			return $this->vipContactMessageHtml();
		}
		return '<p class="vbdl-denied">You do not have permission to download this file.</p>';
	}

	/**
	 * @param array $userinfo
	 * @param array $fileMeta title, description, categoryid, storageid, inherit_category_perms, active, access_type
	 * @param array $upload $_FILES-like array
	 * @param array $perms usergroupid => [can_view, can_download]
	 * @return array{ok:bool,fileid?:int,message:string}
	 */
	public function uploadFile(array $userinfo, array $fileMeta, array $upload, array $perms = array())
	{
		if (!$this->acl->canUpload($userinfo) && empty($this->acl->globalPerms($userinfo)['admin_bypass']))
		{
			return array('ok' => false, 'message' => 'You do not have permission to upload.');
		}
		if (empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name']))
		{
			return array('ok' => false, 'message' => 'No valid upload received.');
		}
		$original = isset($upload['name']) ? $upload['name'] : 'file.bin';
		if (!$this->allowedExtension($original))
		{
			return array('ok' => false, 'message' => 'File extension is not allowed.');
		}
		$size = isset($upload['size']) ? (int)$upload['size'] : filesize($upload['tmp_name']);
		if ($size > $this->maxUploadBytes())
		{
			return array('ok' => false, 'message' => 'File exceeds maximum upload size.');
		}

		$storageid = !empty($fileMeta['storageid']) ? (int)$fileMeta['storageid'] : 0;
		$storage = $storageid ? $this->repo->getStorage($storageid) : $this->repo->getDefaultStorage();
		if (!$storage)
		{
			return array('ok' => false, 'message' => 'No storage profile configured.');
		}

		$driver = vbdl_StorageFactory::fromRow($storage);
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		$key = date('Y/m/') . bin2hex(random_bytes(8)) . ($ext ? '.' . $ext : '');
		$mime = !empty($upload['type']) ? $upload['type'] : 'application/octet-stream';

		if (!$driver->put($key, $upload['tmp_name'], $mime))
		{
			return array('ok' => false, 'message' => 'Failed to store file in storage profile.');
		}

		$fileid = $this->repo->saveFile(array(
			'title' => !empty($fileMeta['title']) ? $fileMeta['title'] : $original,
			'description' => isset($fileMeta['description']) ? $fileMeta['description'] : '',
			'categoryid' => isset($fileMeta['categoryid']) ? (int)$fileMeta['categoryid'] : 0,
			'storageid' => (int)$storage['storageid'],
			'filename' => $original,
			'storage_key' => $key,
			'mime' => $mime,
			'filesize' => $size,
			'active' => !isset($fileMeta['active']) || $fileMeta['active'] ? 1 : 0,
			'inherit_category_perms' => !isset($fileMeta['inherit_category_perms']) || $fileMeta['inherit_category_perms'] ? 1 : 0,
			'access_type' => !empty($fileMeta['access_type']) && $fileMeta['access_type'] === 'paid' ? 'paid' : 'free',
			'require_grant' => !empty($fileMeta['require_grant']) ? 1 : 0,
			'createdby' => !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0,
		));

		if (empty($fileMeta['inherit_category_perms']) && !empty($perms))
		{
			$this->repo->saveEntityPerms($fileid, 0, $perms);
		}

		return array('ok' => true, 'fileid' => $fileid, 'message' => 'File uploaded successfully.');
	}

	/**
	 * Process a download request. May redirect or stream and exit.
	 * @return array{ok:bool,message:string,html?:string} when not exiting
	 */
	public function handleDownload($fileid, array $userinfo, $ip, $userAgent)
	{
		$file = $this->repo->getFile((int)$fileid);
		if (!$file)
		{
			$this->repo->addLog((int)$fileid, (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'error', 'File not found');
			return array('ok' => false, 'message' => 'File not found.', 'html' => '<p>File not found.</p>');
		}

		if (!$this->acl->canAccessFile($file, $userinfo, 'download'))
		{
			$reason = $this->acl->needsVipPurchase($file, $userinfo) ? 'VIP required' : 'Permission denied';
			$this->repo->addLog((int)$file['fileid'], (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'denied', $reason);
			return array(
				'ok' => false,
				'message' => $reason,
				'html' => $this->deniedDownloadMessage($file, $userinfo),
			);
		}

		$limit = (int)$this->repo->getSetting('rate_limit_per_hour', '60');
		if ($limit > 0)
		{
			$used = $this->repo->countDownloadsLastHour((int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip);
			if ($used >= $limit)
			{
				$this->repo->addLog((int)$file['fileid'], (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'denied', 'Rate limit');
				return array('ok' => false, 'message' => 'Download rate limit exceeded. Try again later.', 'html' => '<p>Download rate limit exceeded. Try again later.</p>');
			}
		}

		$storage = $this->repo->getStorage((int)$file['storageid']);
		if (!$storage)
		{
			$this->repo->addLog((int)$file['fileid'], (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'error', 'Storage missing');
			return array('ok' => false, 'message' => 'Storage profile missing.', 'html' => '<p>Storage profile missing.</p>');
		}

		$driver = vbdl_StorageFactory::fromRow($storage);
		$mode = $this->repo->getSetting('download_mode', 'proxy');
		$ttl = (int)$this->repo->getSetting('signed_url_ttl', '300');

		$this->repo->addLog((int)$file['fileid'], (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'ok', 'Download started');
		$this->repo->incrementDownloads((int)$file['fileid']);

		if ($mode === 'redirect' && $storage['storage_type'] === 's3')
		{
			$url = $driver->signedUrl($file['storage_key'], $ttl);
			if ($url)
			{
				header('Location: ' . $url);
				exit;
			}
		}

		if (!$driver->stream($file['storage_key'], $file['filename'], $file['mime']))
		{
			$this->repo->addLog((int)$file['fileid'], (int)(isset($userinfo['userid']) ? $userinfo['userid'] : 0), $ip, $userAgent, 'error', 'Stream failed');
			return array('ok' => false, 'message' => 'Unable to stream file.', 'html' => '<p>Unable to stream file.</p>');
		}
		exit;
	}

	public function deleteFileFully($fileid)
	{
		$file = $this->repo->getFile((int)$fileid);
		if (!$file)
		{
			return false;
		}
		$storage = $this->repo->getStorage((int)$file['storageid']);
		if ($storage)
		{
			$driver = vbdl_StorageFactory::fromRow($storage);
			@$driver->delete($file['storage_key']);
		}
		$this->repo->deleteFile((int)$fileid);
		return true;
	}

	public function renderBbcode($fileid, array $userinfo, $label = '')
	{
		$file = $this->repo->getFile((int)$fileid);
		if (!$file || !$this->acl->canAccessFile($file, $userinfo, 'view'))
		{
			return '<div class="vbdl-denied">You do not have permission to view this download.</div>';
		}
		$canDl = $this->acl->canAccessFile($file, $userinfo, 'download');
		$title = htmlspecialchars($label !== '' ? $label : $file['title'], ENT_QUOTES, 'UTF-8');
		$size = $this->formatBytes((int)$file['filesize']);
		$url = 'vbdlmanager/download.php?fileid=' . (int)$file['fileid'];
		$badge = $this->acl->isPaidFile($file)
			? ' <span class="vbdl-badge vbdl-badge-paid">' . htmlspecialchars($this->repo->getSetting('vip_badge_label', 'VIP'), ENT_QUOTES, 'UTF-8') . '</span>'
			: ' <span class="vbdl-badge vbdl-badge-free">Free</span>';

		if ($canDl)
		{
			return '<div class="vbdl-bbcode" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center"><a class="vbdl-download-btn" style="display:inline-block;background:#0f6cbd;color:#fff;text-decoration:none;padding:8px 14px;border-radius:6px;font-weight:700" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">⬇ Download: ' . $title . '</a>' . $badge . ' <span class="vbdl-meta">(' . $size . ', ' . (int)$file['downloads_count'] . ' downloads)</span></div>';
		}
		if ($this->acl->needsVipPurchase($file, $userinfo))
		{
			return '<div class="vbdl-bbcode vbdl-locked"><span class="vbdl-title">' . $title . '</span>' . $badge . ' <span class="vbdl-meta">(' . $size . ')</span>' . $this->vipContactMessageHtml() . '</div>';
		}
		return '<div class="vbdl-bbcode vbdl-locked"><span class="vbdl-title">' . $title . '</span>' . $badge . ' <span class="vbdl-meta">(' . $size . ')</span> — You do not have permission to download this file.</div>';
	}

	public function formatBytes($bytes)
	{
		$bytes = (float)$bytes;
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$i = 0;
		while ($bytes >= 1024 && $i < count($units) - 1)
		{
			$bytes /= 1024;
			$i++;
		}
		return round($bytes, 2) . ' ' . $units[$i];
	}
}
