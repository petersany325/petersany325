<?php
if (!defined('VB_ENTRY'))
{
	die('Access denied.');
}

/**
 * Injects site-wide VIP DOWNLOAD link and optional attachment-link rewrite.
 */
class vbdlportal_Hooks
{
	public static $order = 45;

	public static function hookFrontendBeforeOutput($params)
	{
		if (empty($params['pageHtml']) || !is_string($params['pageHtml']))
		{
			return;
		}

		if (self::skipRequest())
		{
			return;
		}

		$o = self::options();
		if (empty($o['vbdlportal_enabled']))
		{
			return;
		}

		$html =& $params['pageHtml'];

		$html = self::injectVipDownloadLink($html);
		$html = self::injectPostUploadMenu($html);

		$forceDm = '0';
		if (isset($o['force_dm_for_attachments']))
		{
			$forceDm = (string)$o['force_dm_for_attachments'];
		}
		else
		{
			$forceDm = self::readVbdlSetting('force_dm_for_attachments', '0');
		}

		if ($forceDm === '1')
		{
			$html = self::rewriteAttachmentLinks($html, $o);
		}
	}

	/**
	 * Inject specialized Downloads upload menu on create/edit content pages.
	 */
	private static function injectPostUploadMenu($html)
	{
		$uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
		$want = (strpos($uri, 'create-content') !== false
			|| strpos($uri, 'edit-content') !== false
			|| strpos($uri, '/new-content') !== false
			|| strpos($uri, 'create-topic') !== false
			|| strpos($uri, '/blog/') !== false);
		if (!$want)
		{
			return $html;
		}
		if ((string)self::readVbdlSetting('post_upload_enabled', '1') !== '1')
		{
			return $html;
		}
		if (stripos($html, 'vbdl-post-upload.js') !== false)
		{
			return $html;
		}
		$assets = '<link rel="stylesheet" href="/vbdlmanager/assets/post-upload.css" />'
			. '<script defer src="/vbdlmanager/assets/post-upload.js"></script>';
		if (stripos($html, '</body>') !== false)
		{
			return preg_replace('/<\/body>/i', $assets . '</body>', $html, 1);
		}
		return $html . $assets;
	}

	private static function skipRequest()
	{
		$u = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
		foreach (array('/ajax/', '/api/', '/admincp/', '/modcp/', '/install/', 'sitebuilder', 'editsite=1', 'previewstyle=', '/vbdlmanager/', '/auth/') as $n)
		{
			if (strpos($u, $n) !== false)
			{
				return true;
			}
		}
		return false;
	}

	private static function options()
	{
		try
		{
			$o = vB::getDatastore()->getValue('options');
			return is_array($o) ? $o : array();
		}
		catch (Throwable $e)
		{
			return array();
		}
	}

	private static function readVbdlSetting($name, $default = '0')
	{
		try
		{
			$config = vB::getConfig();
			$prefix = isset($config['Database']['tableprefix']) ? $config['Database']['tableprefix'] : '';
			$host = $config['MasterServer']['servername'] ?? 'localhost';
			$port = (int)($config['MasterServer']['port'] ?? 3306);
			$user = $config['MasterServer']['username'] ?? '';
			$pass = $config['MasterServer']['password'] ?? '';
			$dbn = $config['Database']['dbname'] ?? '';
			$mysqli = @new mysqli($host, $user, $pass, $dbn, $port);
			if ($mysqli->connect_errno)
			{
				return $default;
			}
			$mysqli->set_charset('utf8mb4');
			$nameEsc = $mysqli->real_escape_string($name);
			$res = $mysqli->query("SELECT value FROM {$prefix}vbdl_setting WHERE varname='{$nameEsc}' LIMIT 1");
			if ($res && ($row = $res->fetch_assoc()))
			{
				$mysqli->close();
				return (string)$row['value'];
			}
			$mysqli->close();
		}
		catch (Throwable $e)
		{
		}
		return $default;
	}

	private static function injectVipDownloadLink($html)
	{
		if (stripos($html, 'id="vbdlportal-vip-download"') !== false || stripos($html, 'vbdlportal-vip-download') !== false)
		{
			return $html;
		}

		$li = self::vipBarHtml(true);
		$div = self::vipBarHtml(false);
		$style = self::vipStyleHtml();

		$injected = false;
		$ulPatterns = array(
			'/(<ul[^>]*class="[^"]*b-top-menu--user[^"]*"[^>]*>)/i',
			'/(<ul[^>]*class="[^"]*b-top-menu--sitebuilder[^"]*"[^>]*>)/i',
			'/(<ul[^>]*class="[^"]*channel-tabbar-list[^"]*"[^>]*>)/i',
		);
		foreach ($ulPatterns as $pattern)
		{
			$new = preg_replace($pattern, '$1' . $li, $html, 1, $count);
			if (is_string($new) && $count > 0)
			{
				$html = $new;
				$injected = true;
				break;
			}
		}

		if (!$injected)
		{
			$divPatterns = array(
				'/(<div[^>]*class="[^"]*b-top-menu__background[^"]*js-top-menu-user[^"]*"[^>]*>)/i',
				'/(<div[^>]*class="[^"]*b-top-menu__container[^"]*"[^>]*>)/i',
			);
			foreach ($divPatterns as $pattern)
			{
				$new = preg_replace($pattern, '$1' . $div, $html, 1, $count);
				if (is_string($new) && $count > 0)
				{
					$html = $new;
					$injected = true;
					break;
				}
			}
		}

		if (!$injected)
		{
			if (stripos($html, '</body>') !== false)
			{
				$html = preg_replace('/<\/body>/i', $div . '</body>', $html, 1);
			}
			else
			{
				$html .= $div;
			}
		}

		if (stripos($html, 'vbdlportal-vip-style') === false)
		{
			if (stripos($html, '</head>') !== false)
			{
				$html = preg_replace('/<\/head>/i', $style . '</head>', $html, 1);
			}
			else
			{
				$html = $style . $html;
			}
		}

		return $html;
	}

	private static function vipStyleHtml()
	{
		return '<style id="vbdlportal-vip-style">'
			. '.vbdlportal-vip-link{display:inline-flex;align-items:center;gap:6px;margin:0 8px;padding:6px 12px;'
			. 'background:#c50f1f;color:#fff!important;font-weight:700;font-size:13px;line-height:1.2;'
			. 'text-decoration:none!important;border-radius:4px;border:1px solid #a50d19;white-space:nowrap;'
			. 'box-shadow:0 1px 0 rgba(255,255,255,.15) inset}'
			. '.vbdlportal-vip-link:hover{background:#a50d19;color:#fff!important;text-decoration:none!important}'
			. '.vbdlportal-vip-item{list-style:none;display:inline-flex;align-items:center;margin:0;padding:4px 0}'
			. '@media (max-width:720px){.vbdlportal-vip-link{padding:5px 10px;font-size:12px}}'
			. '</style>';
	}

	private static function vipBarHtml($asListItem = true)
	{
		$href = '/vbdlmanager/';
		$title = htmlspecialchars('Official downloads & VIP files', ENT_QUOTES, 'UTF-8');
		$label = 'VIP DOWNLOAD';
		$link = '<a class="vbdlportal-vip-link" href="' . $href . '" title="' . $title . '">' . $label . '</a>';
		if ($asListItem)
		{
			return '<li class="b-top-menu__item vbdlportal-vip-item" id="vbdlportal-vip-download">' . $link . '</li>';
		}
		return '<div class="vbdlportal-vip-item" id="vbdlportal-vip-download" style="display:inline-flex;align-items:center;padding:4px 0">' . $link . '</div>';
	}

	/**
	 * Safe first step: rewrite visible attachment links in post HTML to VIP DOWNLOAD.
	 * Does not block native attachment serving (phase 2).
	 */
	private static function rewriteAttachmentLinks($html, array $o)
	{
		$guestsOnly = !empty($o['vbdlportal_force_dm_guests_only']);
		if ($guestsOnly && !self::isGuest())
		{
			return $html;
		}

		$button = '<a class="vbdlportal-vip-link" href="/vbdlmanager/" title="Official downloads &amp; VIP files">Open VIP DOWNLOAD</a>'
			. ' <span class="vbdlportal-dm-note" style="font-size:0.9em;opacity:.85">(files are in the VIP DOWNLOAD center)</span>';

		$patterns = array(
			'/(<(?:div|article|section)[^>]*(?:class|id)=["\'][^"\']*(?:b-post__content|js-post__content|post-content|postbody|b-content-entry|js-content-entry|bbcode_container|post-content-text|attachments|b-media)[^"\']*["\'][^>]*>)(.*?)(<\/(?:div|article|section)>)/is',
		);

		foreach ($patterns as $pattern)
		{
			$new = preg_replace_callback($pattern, function ($m) use ($button) {
				return $m[1] . self::rewriteLinksInFragment($m[2], $button) . $m[3];
			}, $html);
			if (is_string($new))
			{
				$html = $new;
			}
		}

		return $html;
	}

	private static function rewriteLinksInFragment($fragment, $button)
	{
		return preg_replace_callback(
			'/<a\b([^>]*)\bhref\s*=\s*(["\'])([^"\']*(?:filedata\/fetch|\/attachment\/|content_attach|attachment\.php)[^"\']*)\2([^>]*)>.*?<\/a>/is',
			function ($m) use ($button) {
				return $button;
			},
			$fragment
		);
	}

	private static function isGuest()
	{
		try
		{
			$user = vB::getCurrentSession()->fetch_userinfo();
			return empty($user['userid']);
		}
		catch (Throwable $e)
		{
			return true;
		}
	}
}
