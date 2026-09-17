<?php
/**
 * AdminCP: Download Manager dashboard — license / VIP / File Manager entry points.
 */
vbdl_admin_header('Dashboard', 'dashboard');

echo '<div class="vbdl-card"><div class="vbdl-card-h">License &amp; VIP operations</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">Review customer license requests, Active License SeDiv tickets, VIP membership, and English support auto-replies.</p>';
echo '<p>';
echo '<a class="vbdl-btn" href="' . vbdl_h(vbdl_admin_url('licenserequests')) . '">License Requests</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('licensetickets')) . '">Active License Tickets</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('vipusers')) . '">VIP Users</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('ticketautoreply')) . '">Ticket Auto-Reply</a>';
echo '</p></div></div>';

echo '<div class="vbdl-card"><div class="vbdl-card-h">File Manager (separate from tickets)</div><div class="vbdl-card-b">';
echo '<p class="vbdl-muted">Downloads Manager uploads never appear in Message Center ticket compose. '
	. 'Users need an administrator Access Grant (or full admin) before the post-editor upload widget is available.</p>';
echo '<p>';
echo '<a class="vbdl-btn" href="' . vbdl_h(vbdl_admin_url('grants')) . '">Access Grants</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('files')) . '">Files</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('categories')) . '">Categories</a> ';
echo '<a class="vbdl-btn secondary" href="' . vbdl_h(vbdl_admin_url('settings')) . '">Settings</a>';
echo '</p></div></div>';

vbdl_admin_footer();
