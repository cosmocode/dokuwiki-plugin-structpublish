<?php

use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_publish extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_db */
    protected $dbHelper;

    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleApprove');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handlePublish');
    }

    public function handlePublish(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        global $INPUT;
        $in = $INPUT->arr('structpublish');
        if (!$in || !isset($in[$this->dbHelper::ACTION_PUBLISH])) {
            return;
        }

        // FIXME prevent bumping published version

        global $ID;
        global $INFO;
        $sqlite = $this->dbHelper->getDB();
        $revision = new Revision($sqlite, $ID, $INFO['currentrev']);
        // TODO do not autoincrement version, make it a string
        $revision->setVersion($revision->getVersion() + 1);
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus(Revision::STATUS_PUBLISHED);
        $revision->setDate(time());

        $revision->save();
    }

    public function handleApprove(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        global $INPUT;
        $in = $INPUT->arr('structpublish');
        if (!$in || !isset($in[$this->dbHelper::ACTION_APPROVE])) {
            return;
        }

        global $ID;
        global $INFO;
        $sqlite = $this->dbHelper->getDB();
        $revision = new Revision($sqlite, $ID, $INFO['currentrev']);
        $revision->setVersion($revision->getVersion());
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus(Revision::STATUS_APPROVED);
        $revision->setDate(time());

        $revision->save();
    }
}
