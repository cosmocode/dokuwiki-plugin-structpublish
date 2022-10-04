<?php

use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_show extends DokuWiki_Action_Plugin
{
    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleShow');
    }

    /**
     * Decide which revision to show based on role assignments
     *
     * @param Doku_Event $event
     * @return void
     */
    public function handleShow(Doku_Event $event)
    {
        if ($event->data != 'show') {
            return;
        }

        global $ID;
        global $REV;
        global $INFO;

        /** @var helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$dbHelper->isPublishable()) {
            return;
        }

        $currentRevision = new Revision($dbHelper->getDB(), $ID, $REV ?: $INFO['currentrev']);

        /** @var action_plugin_structpublish_sqlitefunction $functions */
        $functions = plugin_load('action', 'structpublish_sqlitefunction');
        if (
            $currentRevision->getStatus() !== Constants::STATUS_PUBLISHED
            && !$functions->IS_PUBLISHER($ID)
        ) {
            $latestPublished = $currentRevision->getLatestPublishedRevision();
            if (is_null($latestPublished)) {
                $event->data = 'denied';
                // FIXME we could add our own action to display a custom message instead of standard denied action
                return;
            }

            $latestPublishedRev = $latestPublished->getRev();
            $REV = $latestPublishedRev;
            $INFO['rev'] = $latestPublishedRev;
        }
    }
}
