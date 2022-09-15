<?php

use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

class action_plugin_structpublish_publish extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_db */
    protected $dbHelper;

    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleApprove');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handlePublish');
    }

    /**
     * Handle the publish button and version field
     *
     * @param Doku_Event $event
     * @return void
     */
    public function handlePublish(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        global $INPUT;
        $in = $INPUT->arr('structpublish');
        if (!$in || !isset($in[Constants::ACTION_PUBLISH])) {
            return;
        }

        if(checkSecurityToken()) {
            $this->saveRevision(Constants::STATUS_PUBLISHED, $INPUT->str('version'));
            $this->updateSchemaData();
        }
    }

    /**
     * Handle the approve button
     *
     * @param Doku_Event $event
     * @return void
     */
    public function handleApprove(Doku_Event $event)
    {
        if ($event->data != 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        global $INPUT;
        $in = $INPUT->arr('structpublish');
        if (!$in || !isset($in[Constants::ACTION_APPROVE])) {
            return;
        }

        if(checkSecurityToken()) {
            $this->saveRevision(Constants::STATUS_APPROVED);
        }
    }

    /**
     * Save publish data
     *
     * @todo check user role
     * @param string $status
     * @return void
     */
    protected function saveRevision($status, $newversion='')
    {
        global $ID;
        global $INFO;

        // FIXME prevent bumping an already published revision
        $sqlite = $this->dbHelper->getDB();
        $revision = new Revision($sqlite, $ID, $INFO['currentrev']);

        if ($status === Constants::STATUS_PUBLISHED) {
            $revision->setVersion($newversion);
        }
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus($status);
        $revision->setTimestamp(time());
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
