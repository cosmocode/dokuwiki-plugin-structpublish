<?php
/**
 * Options for the structpublish plugin
 *
 * @author Anna Dabrowska <dokuwiki@cosmocode.de>
 */

$meta['restrict_admin'] = ['onoff'];
$meta['email_enable'] = ['onoff'];
$meta['email_status'] = ['multicheckbox', '_other' => 'never', '_choices' => ['approve', 'publish']];
