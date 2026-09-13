<?php
$repo = vbdl_Bootstrap::$repo;
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	vbdl_check_token();
	$fields = array(
		'max_upload_bytes',
		'allowed_extensions',
		'signed_url_ttl',
		'download_mode',
		'guest_downloads',
		'rate_limit_per_hour',
		'downloads_per_page',
		'vip_usergroupids',
		'vip_badge_label',
		'vip_contact_title',
		'vip_contact_message',
		'vip_contact_email',
		'vip_contact_url',
		'vip_contact_button_label',
		'vip_contact_telegram',
		'vip_contact_whatsapp',
		'vip_telegram_button_label',
		'vip_whatsapp_button_label',
		'force_dm_for_attachments',
	);
	foreach ($fields as $field)
	{
		if (isset($_POST[$field]))
		{
			$repo->setSetting($field, $_POST[$field]);
		}
	}
	// Sync attachment-routing flag into vB options for vbdlportal frontend hook.
	if (isset($_POST['force_dm_for_attachments']))
	{
		$forceVal = (string)$_POST['force_dm_for_attachments'];
		$repo->setSetting('force_dm_for_attachments', $forceVal);
		if (function_exists('vbdl_sync_force_dm_option'))
		{
			vbdl_sync_force_dm_option($forceVal);
		}
		else
		{
			global $db, $table_prefix;
			$pfx = isset($table_prefix) ? $table_prefix : '';
			$v = ($forceVal === '1') ? '1' : '0';
			if ($db)
			{
				$chk = $db->query_first("SELECT varname FROM {$pfx}setting WHERE varname='force_dm_for_attachments'");
				if ($chk)
				{
					$db->query_write("UPDATE {$pfx}setting SET value='" . $db->escape_string($v) . "' WHERE varname='force_dm_for_attachments'");
				}
				else
				{
					$db->query_write("INSERT INTO {$pfx}setting (varname, grouptitle, value, defaultvalue, datatype, optioncode, displayorder, volatile) VALUES ('force_dm_for_attachments', 'vbdlportal', '" . $db->escape_string($v) . "', '0', 'boolean', 'yesno', 20, 1)");
				}
				$opt = $db->query_first("SELECT data FROM {$pfx}datastore WHERE title='options'");
				if ($opt && !empty($opt['data']))
				{
					$options = @unserialize($opt['data']);
					if (!is_array($options))
					{
						$options = array();
					}
					$options['force_dm_for_attachments'] = $v;
					$db->query_write("UPDATE {$pfx}datastore SET data='" . $db->escape_string(serialize($options)) . "' WHERE title='options'");
				}
			}
		}
	}
	print_cp_redirect(vbdl_admin_url('settings'), 1);
	exit;
}
$s = $repo->getAllSettings();
$groups = vbdl_list_usergroups();
vbdl_admin_header('Settings', 'settings');

echo '<div class="vbdl-panel"><div class="vbdl-panel-h">Runtime settings</div><div class="vbdl-panel-b"><form method="post" action="admincp/vbdlmanager.php">';
vbdl_request_token_field();
echo '<input type="hidden" name="do" value="settings" />';
echo '<div class="vbdl-form-row"><label>Max upload (bytes)</label><input class="vbdl-input" name="max_upload_bytes" value="' . vbdl_h(isset($s['max_upload_bytes']) ? $s['max_upload_bytes'] : '52428800') . '" /></div>';
echo '<div class="vbdl-form-row"><label>Allowed extensions</label><input class="vbdl-input" name="allowed_extensions" value="' . vbdl_h(isset($s['allowed_extensions']) ? $s['allowed_extensions'] : '') . '" /></div>';
echo '<div class="vbdl-form-row"><label>Signed URL TTL</label><input class="vbdl-input" name="signed_url_ttl" value="' . vbdl_h(isset($s['signed_url_ttl']) ? $s['signed_url_ttl'] : '300') . '" /></div>';
$mode = isset($s['download_mode']) ? $s['download_mode'] : 'proxy';
echo '<div class="vbdl-form-row"><label>Download mode</label><select class="vbdl-select" name="download_mode"><option value="proxy"' . ($mode === 'proxy' ? ' selected' : '') . '>Proxy stream</option><option value="redirect"' . ($mode === 'redirect' ? ' selected' : '') . '>Signed S3 redirect</option></select></div>';
$guest = isset($s['guest_downloads']) ? $s['guest_downloads'] : '0';
echo '<div class="vbdl-form-row"><label>Guest downloads</label><select class="vbdl-select" name="guest_downloads"><option value="0"' . ($guest === '0' ? ' selected' : '') . '>No</option><option value="1"' . ($guest === '1' ? ' selected' : '') . '>Yes</option></select></div>';
echo '<div class="vbdl-form-row"><label>Rate limit / hour</label><input class="vbdl-input" name="rate_limit_per_hour" value="' . vbdl_h(isset($s['rate_limit_per_hour']) ? $s['rate_limit_per_hour'] : '60') . '" /></div>';
echo '<div class="vbdl-form-row"><label>Per page</label><input class="vbdl-input" name="downloads_per_page" value="' . vbdl_h(isset($s['downloads_per_page']) ? $s['downloads_per_page'] : '20') . '" /></div>';

$forceDm = isset($s['force_dm_for_attachments']) ? $s['force_dm_for_attachments'] : '0';
echo '<hr style="border:0;border-top:1px solid #d9e2ec;margin:20px 0" />';
echo '<h3 style="margin:0 0 10px">Centralize downloads (phase 1)</h3>';
echo '<p class="vbdl-muted">Safe first step toward routing all downloads through Download Manager. When enabled, visible attachment links in posts are rewritten to <strong>Open VIP DOWNLOAD</strong> pointing at <code>/vbdlmanager/</code>. Native attachment serving is not blocked yet (phase 2).</p>';
echo '<div class="vbdl-form-row"><label>Route attachment downloads through Download Manager notice</label><select class="vbdl-select" name="force_dm_for_attachments"><option value="0"' . ($forceDm !== '1' ? ' selected' : '') . '>No (default)</option><option value="1"' . ($forceDm === '1' ? ' selected' : '') . '>Yes</option></select></div>';

echo '<hr style="border:0;border-top:1px solid #d9e2ec;margin:20px 0" />';
echo '<h3 style="margin:0 0 10px">Paid / VIP downloads</h3>';
echo '<p class="vbdl-muted">Paid files can only be downloaded by members of the VIP usergroups below. Everyone else sees the contact-admin message (English).</p>';

$vipIds = isset($s['vip_usergroupids']) ? $s['vip_usergroupids'] : '';
echo '<div class="vbdl-form-row"><label>VIP usergroup IDs</label><div>';
echo '<input class="vbdl-input" name="vip_usergroupids" value="' . vbdl_h($vipIds) . '" placeholder="e.g. 8,12" />';
echo '<p class="vbdl-muted">Comma-separated usergroup IDs. Members of these groups can download Paid files.</p>';
if (!empty($groups))
{
	echo '<p class="vbdl-muted">Available groups: ';
	$bits = array();
	foreach ($groups as $g)
	{
		$bits[] = vbdl_h($g['title']) . ' (#' . (int)$g['usergroupid'] . ')';
	}
	echo implode(', ', $bits) . '</p>';
}
echo '</div></div>';

echo '<div class="vbdl-form-row"><label>VIP badge label</label><input class="vbdl-input" name="vip_badge_label" value="' . vbdl_h(isset($s['vip_badge_label']) ? $s['vip_badge_label'] : 'VIP') . '" /></div>';
echo '<div class="vbdl-form-row"><label>Contact title</label><input class="vbdl-input" name="vip_contact_title" value="' . vbdl_h(isset($s['vip_contact_title']) ? $s['vip_contact_title'] : 'VIP Membership Required') . '" /></div>';
echo '<div class="vbdl-form-row"><label>Contact message</label><textarea class="vbdl-textarea" name="vip_contact_message" rows="4">' . vbdl_h(isset($s['vip_contact_message']) ? $s['vip_contact_message'] : "This is a paid VIP download.\nTo purchase VIP access, please contact the administrator.") . '</textarea></div>';
echo '<div class="vbdl-form-row"><label>Contact email</label><input class="vbdl-input" name="vip_contact_email" value="' . vbdl_h(isset($s['vip_contact_email']) ? $s['vip_contact_email'] : 'info@hdd-land.com') . '" placeholder="admin@example.com" /></div>';
echo '<div class="vbdl-form-row"><label>Contact URL</label><input class="vbdl-input" name="vip_contact_url" value="' . vbdl_h(isset($s['vip_contact_url']) ? $s['vip_contact_url'] : '') . '" placeholder="https://forum.hdd-land.com/contact-us" /></div>';
echo '<div class="vbdl-form-row"><label>Contact button label</label><input class="vbdl-input" name="vip_contact_button_label" value="' . vbdl_h(isset($s['vip_contact_button_label']) ? $s['vip_contact_button_label'] : 'Contact Administrator') . '" /></div>';

echo '<hr style="border:0;border-top:1px solid #d9e2ec;margin:20px 0" />';
echo '<h3 style="margin:0 0 10px">VIP contact links (Telegram / WhatsApp)</h3>';
echo '<p class="vbdl-muted">When set, these buttons appear on Paid/VIP denial messages. Leave empty to hide a channel. Field names: <code>vip_contact_telegram</code>, <code>vip_contact_whatsapp</code>.</p>';
echo '<div class="vbdl-form-row"><label>Telegram bot / channel URL</label><input class="vbdl-input" name="vip_contact_telegram" value="' . vbdl_h(isset($s['vip_contact_telegram']) ? $s['vip_contact_telegram'] : '') . '" placeholder="https://t.me/YourBot or @username" /></div>';
echo '<div class="vbdl-form-row"><label>WhatsApp</label><input class="vbdl-input" name="vip_contact_whatsapp" value="' . vbdl_h(isset($s['vip_contact_whatsapp']) ? $s['vip_contact_whatsapp'] : '') . '" placeholder="https://wa.me/98912... or phone digits" /></div>';
echo '<div class="vbdl-form-row"><label>Telegram button label</label><input class="vbdl-input" name="vip_telegram_button_label" value="' . vbdl_h(isset($s['vip_telegram_button_label']) ? $s['vip_telegram_button_label'] : 'Telegram') . '" /></div>';
echo '<div class="vbdl-form-row"><label>WhatsApp button label</label><input class="vbdl-input" name="vip_whatsapp_button_label" value="' . vbdl_h(isset($s['vip_whatsapp_button_label']) ? $s['vip_whatsapp_button_label'] : 'WhatsApp') . '" /></div>';

echo '<div class="vbdl-actions"><button class="vbdl-btn" type="submit">Save settings</button></div></form></div></div>';
vbdl_admin_footer();