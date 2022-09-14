<?php

use dokuwiki\plugin\structpublish\meta\Constants;
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
        if (!$in || !isset($in[Constants::ACTION_PUBLISH])) {
            return;
        }

        $this->saveRevision(Constants::STATUS_PUBLISHED);

        $this->updateSchemaData();

    }

    public function handleApprove(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        global $INPUT;
        $in = $INPUT->arr('structpublish');
        if (!$in || !isset($in[Constants::ACTION_APPROVE])) {
            return;
        }

        $this->saveRevision(Constants::STATUS_APPROVED);
    }

    /**
     * Save publish data
     *
     * @param string $status
     * @return void
     */
    protected function saveRevision($status)
    {
        global $ID;
        global $INFO;

        // FIXME prevent bumping an already published revision
        $sqlite = $this->dbHelper->getDB();
        $revision = new Revision($sqlite, $ID, $INFO['currentrev']);

        // TODO do not autoincrement version, make it a string
        if ($status === Constants::STATUS_PUBLISHED) {
            $revision->setVersion($revision->getVersion() + 1);
        }
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus($status);
        $revision->setDate(time());
        $revision->save();
    }

    /**
     * Set "published" status in all assigned schemas
     *
     * @return void
     */
    protected function updateSchemaData()
    {
        global $ID;
        global $INFO;

        $schemaAssignments = \dokuwiki\plugin\struct\meta\Assignments::getInstance();
        $tables = $schemaAssignments->getPageAssignments($ID);

        if (empty($tables)) return;

        $sqlite = $this->dbHelper->getDB();

        foreach ($tables as $table) {
            // TODO unpublish earlier revisions
            $sqlite->query( "UPDATE data_$table SET published = 1 WHERE pid = ? AND rev = ?", [$ID, $INFO['currentrev']]);
            $sqlite->query( "UPDATE multi_$table SET published = 1 WHERE pid = ? AND rev = ?", [$ID, $INFO['currentrev']]);
        }
    }
}
