<?php
/**
 * AdminCP: Support ticket auto-reply (English welcome when users open tickets to admin).
 */
$repo = vbdl_Bootstrap::$repo;
require_once vbdl_admin_paths()['package'] . '/library/Db.php';
require_once vbdl_admin_paths()['package'] . '/library/LicenseMail.php';
require_once vbdl_admin_paths()['package'] . '/library/TicketAutoReply.php';

$db = vbdl_Db::mysqli();
$tp = vbdl_Db::tablePrefix();
if (!$db)
{
	print_cp_message('Database (mysqli) unavailable for Ticket Auto-Reply.');
	exit;
}
$lm = new vbdl_LicenseMail($db, $tp, $repo, vbdl_Bootstrap::$acl);
$ar = new vbdl_TicketAutoReply($db, $tp, $repo, $lm);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'poll_now')
{
	vbdl_check_token();
	$result = $ar->pollRecent(50, 172800);
	$count = is_array($result['processed']) ? count($result['processed']) : 0;
	print_cp_message('Auto-reply poll finished. New replies posted: ' . $count);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'save')
{
	vbdl_check_token();
	$fields = array(
		'ticket_autoreply_enabled',
		'ticket_autoreply_watch_userids',
		'ticket_autoreply_subject',
		'ticket_autoreply_body',
		'license_support_userid',
	);
	foreach ($fields as $f)
	{
		if (isset($_POST[$f]))
		{
			$repo->setSetting($f, (string)$_POST[$f]);
		}
	}
	print_cp_message('Auto-reply settings saved.');
	exit;
}

$s = array();
foreach (array(
	'ticket_autoreply_enabled',
	'ticket_autoreply_watch_userids',
	'ticket_autoreply_subject',
	'ticket_autoreply_body',
	'license_support_userid',
	'license_inbox_key',
) as $k)
{
	$s[$k] = $repo->getSetting($k, '');
}

vbdl_admin_header('Ticket Auto-Reply');
echo '<div class="vbdl-card"><div class="vbdl-card-h">English welcome reply for new support tickets</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">When a customer opens a <strong>new Message Center ticket</strong> to support/admin, the system posts an automatic English welcome reply. '
	. 'License desks (<code>VBDL-LIC</code> / <code>VBDL-REQ</code>) are skipped. '
	. 'Message Center also triggers a light <code>poll_self</code> for the signed-in user; cron can use the same inbox key as license import.</p>';

echo '<form method="post" action="' . vbdl_h(vbdl_admin_url('ticketautoreply')) . '">';
vbdl_request_token_field();
echo '<input type="hidden" name="action" value="save" />';
echo '<div class="vbdl-form-row"><label>Support userid</label><input class="vbdl-input" name="license_support_userid" value="'
	. vbdl_h($s['license_support_userid'] !== '' ? $s['license_support_userid'] : '1') . '" /></div>';
$arOn = $s['ticket_autoreply_enabled'] !== '' ? $s['ticket_autoreply_enabled'] : '1';
echo '<div class="vbdl-form-row"><label>Enable auto-reply</label><select class="vbdl-select" name="ticket_autoreply_enabled">'
	. '<option value="1"' . ($arOn !== '0' ? ' selected' : '') . '>Yes</option>'
	. '<option value="0"' . ($arOn === '0' ? ' selected' : '') . '>No</option></select></div>';
echo '<div class="vbdl-form-row"><label>Extra watch userids</label><input class="vbdl-input" name="ticket_autoreply_watch_userids" value="'
	. vbdl_h($s['ticket_autoreply_watch_userids']) . '" placeholder="optional comma-separated admin userids" /></div>';
echo '<div class="vbdl-form-row"><label>Subject</label><input class="vbdl-input" name="ticket_autoreply_subject" value="'
	. vbdl_h($s['ticket_autoreply_subject'] !== '' ? $s['ticket_autoreply_subject'] : 'Welcome to SeDiv Support') . '" /></div>';
$arBodyDefault = $ar->replyBody();
echo '<div class="vbdl-form-row"><label>Body (English)</label><textarea class="vbdl-input" name="ticket_autoreply_body" rows="10">'
	. vbdl_h($s['ticket_autoreply_body'] !== '' ? $s['ticket_autoreply_body'] : $arBodyDefault)
	. '</textarea></div>';
echo '<div class="vbdl-actions"><button class="vbdl-btn" type="submit">Save</button></div></form>';
echo '</div></div>';

echo '<div class="vbdl-card"><div class="vbdl-card-h">Run poll now</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">Cron: <code>/vbdlmanager/pm_ticket_autoreply.php?key=INBOX_KEY&amp;do=poll</code></p>';
echo '<form method="post" action="' . vbdl_h(vbdl_admin_url('ticketautoreply')) . '">';
vbdl_request_token_field();
echo '<input type="hidden" name="action" value="poll_now" />';
echo '<button class="vbdl-btn secondary" type="submit">Poll recent tickets now</button></form>';
echo '</div></div>';

$recent = $ar->listRecent(40);
echo '<div class="vbdl-card"><div class="vbdl-card-h">Recent auto-replies</div><div class="vbdl-card-b">';
echo '<table class="vbdl-table"><thead><tr><th>When</th><th>Customer</th><th>Ticket</th><th>Reply node</th></tr></thead><tbody>';
foreach ($recent as $row)
{
	$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['nodeid'];
	echo '<tr>';
	echo '<td>' . (!empty($row['dateline']) ? date('Y-m-d H:i', (int)$row['dateline']) : '') . '</td>';
	echo '<td>' . vbdl_h(isset($row['customer_username']) ? $row['customer_username'] : '') . ' <span class="vbdl-muted">#' . (int)$row['customer_userid'] . '</span></td>';
	echo '<td>' . ($msgId > 0 ? '<a href="/messagecenter/view/' . $msgId . '" target="_blank">' . vbdl_h(isset($row['title']) ? $row['title'] : ('#' . $msgId)) . '</a>' : '') . '</td>';
	echo '<td>' . (int)$row['reply_nodeid'] . '</td>';
	echo '</tr>';
}
if (!$recent)
{
	echo '<tr><td colspan="4">No auto-replies yet.</td></tr>';
}
echo '</tbody></table></div></div>';
vbdl_admin_footer();
