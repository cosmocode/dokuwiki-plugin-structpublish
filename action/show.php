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
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handleShow');
    }

    public function handleShow(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->permissionsHelper = plugin_load('helper', 'structpublish_permissions');

        global $ID;
        global $INFO;
        global $REV;

        $sqlite = $this->permissionsHelper->getDb();

        $currentRevision = new Revision($sqlite, $ID, $INFO['currentrev']);
        if ($currentRevision->getStatus() !== Revision::STATUS_PUBLISHED) {
            /** @var Revision $latestPublished */
            $latestPublished = $this->permissionsHelper->getLatestPublished();
            if (!$latestPublished) {
                $event->data = 'denied';

                $event->preventDefault();
                $event->stopPropagation();

                print p_locale_xhtml('denied');
            }

                $REV = $latestPublished->getRev();
        }
    }
}
