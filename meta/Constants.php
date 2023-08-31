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

    // an action transitions a page from one status to another
    const ACTION_APPROVE = 'approve';
    const ACTION_PUBLISH = 'publish';

    /**
     * Convenience function mapping transition actions to resulting status
     *
     * @param string $action
     * @return string
     */
    public static function transitionBy($action)
    {
        $map = [
            self::ACTION_APPROVE => self::STATUS_APPROVED,
            self::ACTION_PUBLISH => self::STATUS_PUBLISHED,
        ];

        return $map[$action];
    }

    public static function workflowSteps($action)
    {
        $map = [
            self::ACTION_APPROVE => [
                'fromStatus' => self::STATUS_DRAFT,
                'currentStatus' => self::STATUS_APPROVED,
                'toStatus' => self::STATUS_PUBLISHED,
                'previousAction' => null,
                'nextAction' => self::ACTION_PUBLISH
            ],
            self::ACTION_PUBLISH => [
                'fromStatus' => self::STATUS_APPROVED,
                'currentStatus' => self::STATUS_PUBLISHED,
                'toStatus' => null,
                'previousAction' => self::ACTION_APPROVE,
                'nextAction' => null
            ],
        ];

        return $map[$action];
    }
}
