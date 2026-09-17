<?php
/**
 * AdminCP: Active License SeDiv tickets (VIP send + .src return overview).
 */
$repo = vbdl_Bootstrap::$repo;
require_once vbdl_admin_paths()['package'] . '/library/Db.php';
require_once vbdl_admin_paths()['package'] . '/library/LicenseMail.php';

$db = vbdl_Db::mysqli();
$tp = vbdl_Db::tablePrefix();
if (!$db)
{
	print_cp_message('Database (mysqli) unavailable for Active License Tickets.');
	exit;
}
$lm = new vbdl_LicenseMail($db, $tp, $repo, vbdl_Bootstrap::$acl);
$rows = $lm->listAll(120);

vbdl_admin_header('Active License Tickets');
echo '<div class="vbdl-card"><div class="vbdl-card-h">SeDiv VIP license tickets</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">All Active License SeDiv sends from VIP members. Status <code>sent</code> waits for activator; '
	. '<code>returned</code> means <code>.src</code> is in the Message Center ticket; <code>rejected</code> is a text-only reply.</p>';
echo '<table class="vbdl-table"><thead><tr>'
	. '<th>Token</th><th>VIP user</th><th>Type</th><th>Status</th><th>Sent</th><th>Returned</th><th>Ticket</th>'
	. '</tr></thead><tbody>';
foreach ($rows as $row)
{
	$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['message_nodeid'];
	echo '<tr>';
	echo '<td><code>' . vbdl_h($row['token']) . '</code></td>';
	echo '<td>' . vbdl_h($row['customer_username']) . ' <span class="vbdl-muted">#' . (int)$row['customer_userid'] . '</span></td>';
	echo '<td>' . vbdl_h(isset($row['license_label']) ? $row['license_label'] : $row['license_type']) . '</td>';
	echo '<td>' . vbdl_h($row['status']) . '</td>';
	echo '<td>' . (!empty($row['sent_dateline']) ? date('Y-m-d H:i', (int)$row['sent_dateline']) : '') . '</td>';
	echo '<td>' . (!empty($row['returned_dateline']) ? date('Y-m-d H:i', (int)$row['returned_dateline']) : '') . '</td>';
	echo '<td>' . ($msgId > 0 ? '<a href="/messagecenter/view/' . $msgId . '" target="_blank">Open</a>' : '') . '</td>';
	echo '</tr>';
}
if (!$rows)
{
	echo '<tr><td colspan="7">No Active License tickets yet.</td></tr>';
}
echo '</tbody></table></div></div>';

echo '<div class="vbdl-card"><div class="vbdl-card-h">VIP Users admin</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">Manage VIP SeDiv membership (and other VIP groups) in '
	. '<a href="' . vbdl_h(vbdl_admin_url('vipusers')) . '">VIP Users</a>. '
	. 'Access Grants for File Manager / Downloads uploads: '
	. '<a href="' . vbdl_h(vbdl_admin_url('grants')) . '">Access Grants</a>.</p>';
echo '</div></div>';
vbdl_admin_footer();
