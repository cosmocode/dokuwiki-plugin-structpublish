<?php

use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_show extends DokuWiki_Action_Plugin
{
    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleShow');
    }

    public function handleShow(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        global $ID;
        global $REV;
        global $INFO;

        /** @var helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$dbHelper->isPublishable()) return;

        $currentRevision = new Revision($dbHelper->getDB(), $ID, $INFO['currentrev']);
        if (
            $currentRevision->getStatus() !== Revision::STATUS_PUBLISHED
            && !$dbHelper->IS_PUBLISHER($ID)
        ) {
            $latestPublishedRev = $currentRevision->getLatestPublished('revision');
            if (!$latestPublishedRev) {
                $event->data = 'denied';
                $event->preventDefault();
                $event->stopPropagation();
                print p_locale_xhtml('denied');
            }

            $REV = $latestPublishedRev;
            $INFO['rev'] = $latestPublishedRev;
        }
    }
}
