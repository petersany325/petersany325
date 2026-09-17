<?php
/**
 * AdminCP: License Request review queue (VIP SeDiv add + all requests).
 */
$repo = vbdl_Bootstrap::$repo;
require_once vbdl_admin_paths()['package'] . '/library/Db.php';
require_once vbdl_admin_paths()['package'] . '/library/LicenseMail.php';
require_once vbdl_admin_paths()['package'] . '/library/LicenseRequest.php';

$db = vbdl_Db::mysqli();
$tp = vbdl_Db::tablePrefix();
if (!$db)
{
	print_cp_message('Database (mysqli) unavailable for License Requests.');
	exit;
}
$lm = new vbdl_LicenseMail($db, $tp, $repo, vbdl_Bootstrap::$acl);
$lr = new vbdl_LicenseRequest($db, $tp, $repo, vbdl_Bootstrap::$acl, $lm);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token']) && !empty($_POST['action']) && $_POST['action'] === 'add_vip')
{
	vbdl_check_token();
	global $vbulletin;
	$adminId = !empty($vbulletin->userinfo['userid']) ? (int)$vbulletin->userinfo['userid'] : 1;
	$rec = $lr->findByToken((string)$_POST['token']);
	if ($rec)
	{
		$result = $lr->addCustomerToVip($rec, $adminId);
		if (!empty($result['error']))
		{
			print_cp_message('VIP add failed: ' . vbdl_h($result['error']));
			exit;
		}
		print_cp_message('User added to VIP SeDiv: ' . vbdl_h($result['username']) . ' (token ' . vbdl_h($result['token']) . ')');
		exit;
	}
	print_cp_message('Unknown token');
	exit;
}

vbdl_admin_header('License Requests');
$pending = $lr->listPendingVip(80);
$all = $lr->listAll(80);
$vipGid = $lr->targetVipGroupId();

echo '<div class="vbdl-card"><div class="vbdl-card-h">Pending VIP SeDiv add</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">Approved License Requests waiting for admin to add the customer to VIP SeDiv (usergroup #'
	. (int)$vipGid . '). These users already received their <code>.txt</code> license in Message Center.</p>';
if (!$pending)
{
	echo '<p>No pending VIP adds.</p>';
}
else
{
	echo '<table class="vbdl-table"><thead><tr><th>Token</th><th>User</th><th>License</th><th>Approved</th><th>Ticket</th><th></th></tr></thead><tbody>';
	foreach ($pending as $row)
	{
		$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['message_nodeid'];
		echo '<tr>';
		echo '<td><code>' . vbdl_h($row['token']) . '</code></td>';
		echo '<td>' . vbdl_h($row['customer_username']) . ' <span class="vbdl-muted">#' . (int)$row['customer_userid'] . '</span></td>';
		echo '<td>' . vbdl_h($row['license_filename']) . '</td>';
		echo '<td>' . (!empty($row['approved_dateline']) ? date('Y-m-d H:i', (int)$row['approved_dateline']) : '') . '</td>';
		echo '<td>' . ($msgId > 0 ? '<a href="/messagecenter/view/' . $msgId . '" target="_blank">Open</a>' : '') . '</td>';
		echo '<td><form method="post" action="' . vbdl_h(vbdl_admin_url('licenserequests')) . '" style="margin:0">';
		vbdl_request_token_field();
		echo '<input type="hidden" name="action" value="add_vip" />';
		echo '<input type="hidden" name="token" value="' . vbdl_h($row['token']) . '" />';
		echo '<button class="vbdl-btn" type="submit">Add to VIP SeDiv</button></form></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
}
echo '</div></div>';

echo '<div class="vbdl-card"><div class="vbdl-card-h">All License Requests</div><div class="vbdl-card-b">';
echo '<table class="vbdl-table"><thead><tr><th>Token</th><th>User</th><th>Status</th><th>Sent</th><th>Ticket</th></tr></thead><tbody>';
foreach ($all as $row)
{
	$msgId = !empty($row['starter_nodeid']) ? (int)$row['starter_nodeid'] : (int)$row['message_nodeid'];
	echo '<tr>';
	echo '<td><code>' . vbdl_h($row['token']) . '</code></td>';
	echo '<td>' . vbdl_h($row['customer_username']) . '</td>';
	echo '<td>' . vbdl_h($row['status']) . '</td>';
	echo '<td>' . (!empty($row['sent_dateline']) ? date('Y-m-d H:i', (int)$row['sent_dateline']) : '') . '</td>';
	echo '<td>' . ($msgId > 0 ? '<a href="/messagecenter/view/' . $msgId . '" target="_blank">Open</a>' : '') . '</td>';
	echo '</tr>';
}
if (!$all)
{
	echo '<tr><td colspan="5">No license requests yet.</td></tr>';
}
echo '</tbody></table></div></div>';
vbdl_admin_footer();
