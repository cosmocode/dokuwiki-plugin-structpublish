<?php

namespace dokuwiki\plugin\structpublish\meta;

/**
 * Defines some constants used throughout the plugin
 *
 * @todo this might need to be replaced later if we want to have user configurable status
 */
class Constants
{
    // a page can be in one current status
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';

    // an action transforms a page from one status to another
    const ACTION_APPROVE = 'approve';
    const ACTION_PUBLISH = 'publish';
}
