<?php

use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_publish extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_permissions */
    protected $permissionsHelper;

    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handlePublish');
    }

    public function handlePublish(Doku_Event $event)
    {
        if ($event->data != 'show') return;
        if (!isset($_GET['structpublish'])) return;

        $this->permissionsHelper = plugin_load('helper', 'structpublish_permissions');

        global $ID;
        global $INFO;
        $sqlite = $this->permissionsHelper->getDb();
        $revision = new Revision($sqlite, $ID);
        $revision->setRev($INFO['currentrev']);
        $revision->setVersion($revision->getVersion() + 1);
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus(Revision::STATUS_PUBLISHED);
        // FIXME update core table

        $revision->save();

    }

    protected function publish($rev)
    {
        global $ID;
    }
}
