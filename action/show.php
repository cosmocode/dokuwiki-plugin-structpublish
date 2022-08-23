<?php

use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_show extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_permissions */
    protected $permissionsHelper;

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

        $this->permissionsHelper = plugin_load('helper', 'structpublish_permissions');
        /** @var helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$this->permissionsHelper->isPublishable()) return;

        $currentRevision = new Revision($dbHelper->getDB(), $ID, $INFO['currentrev']);
        if (
            $currentRevision->getStatus() !== Revision::STATUS_PUBLISHED
            && !$dbHelper->IS_PUBLISHER($ID)
        ) {
            /** @var Revision $latestPublished */
            $latestPublished = $currentRevision->getLatestPublishedRev();
            if (!$latestPublished) {
                $event->data = 'denied';

                $event->preventDefault();
                $event->stopPropagation();

                print p_locale_xhtml('denied');
            }

            $REV = $latestPublished;
            $INFO['rev'] = $latestPublished;
        }
    }
}
