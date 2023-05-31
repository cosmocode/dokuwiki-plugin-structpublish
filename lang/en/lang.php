<?php
/**
 * English language file for structpublish plugin
 *
 * @author Anna Dabrowska <dokuwiki@cosmocode.de>
 */

// menu entry for admin plugins
$lang['menu'] = 'Structured publish data';

// banner
$lang['version'] = 'Version';
$lang['newversion'] = 'New Version';
$lang['status'] = 'Publish status of the page you are currently viewing';
$lang['actions'] = 'Actions';
$lang['status_draft'] = 'Draft';
$lang['status_approved'] = 'Approved';
$lang['status_published'] = 'Published';
$lang['action_approve'] = 'Approve';
$lang['action_publish'] = 'Publish';

$lang['diff'] = 'Show differences';
$lang['banner_status_draft'] = 'This page revision is a <strong>working draft</strong>, created on {revision}.';
$lang['banner_status_approved'] = 'This page revision has been <strong>approved for publishing</strong> on {datetime} by {user}.';
$lang['banner_status_published'] = 'This page revision has been <strong>published <span class="plugin-structpublish-version">as version "{version}"</span></strong> on {datetime} by {user}.';
$lang['banner_latest_publish'] = 'The page was most recently published as version {version} by {user} on {datetime}.';
$lang['banner_previous_publish'] = 'The page was previously published as version {version} by {user} on {datetime}.';
$lang['banner_latest_draft'] = 'A newer working draft created on {revision} exists.';
$lang['compact_banner_status_draft'] = 'Draft';
$lang['compact_banner_status_approved'] = 'Approved';
$lang['compact_banner_status_published'] = 'Published as version "{version} on {datetime} by {user}"';
$lang['compact_banner_latest_publish'] = '';
$lang['compact_banner_previous_publish'] = '';
$lang['compact_banner_latest_draft'] = 'Newer draft: {revision}';

// admin
$lang['assign_pattern'] = 'Pattern';
$lang['assign_user'] = 'User or @group';
$lang['assign_status'] = 'Status';
$lang['assign_add'] = 'Add assignment';
$lang['assign_del'] = 'Remove assignment';

// email
$lang['email_subject'] = 'Publish status of a wiki page has changed';
$lang['email_error_norecipients'] = 'No recipients found to notify!';
