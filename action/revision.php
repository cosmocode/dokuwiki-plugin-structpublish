<?php

use dokuwiki\plugin\struct\meta\StructException;
use dokuwiki\plugin\structpublish\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component to handle page save
 */
class action_plugin_structpublish_revision extends DokuWiki_Action_Plugin
{
    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'handleSave');
    }

    /**
     * Handle the page save event to store revision meta data
     *
     * @param Doku_Event $event
     * @return void
     */
    public function handleSave(Doku_Event $event)
    {
        /** @var helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');

        // FIXME evaluate changeType?
        $id = $event->data['id'];

        // before checking for isPublishable() we have to update assignments @todo is that true?
        $assignments = Assignments::getInstance();
        $assignments->updatePageAssignments($id, true);

        if (!$dbHelper->isPublishable()) {
            return;
        }

        $revision = new Revision($dbHelper->getDB(), $id, $event->data['newRevision']);
        $revision->setStatus(Constants::STATUS_DRAFT);

        try {
            $revision->save();
        } catch(StructException $e) {
            msg($e->getMessage(), -1);
        }
    }
}
