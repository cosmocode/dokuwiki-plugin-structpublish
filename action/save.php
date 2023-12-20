<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\plugin\struct\meta\StructException;
use dokuwiki\plugin\structpublish\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component to handle page save
 */
class action_plugin_structpublish_save extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'handleSave');
    }

    /**
     * Handle the page save event to store revision meta data
     *
     * @param Event $event
     * @return void
     */
    public function handleSave(Event $event)
    {
        /** @var helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');

        $id = $event->data['id'];

        $assignments = Assignments::getInstance();
        $assignments->updatePageAssignments($id);

        if (!$dbHelper->isPublishable()) {
            return;
        }

        $revision = new Revision($id, $event->data['newRevision']);
        $revision->setStatus(Constants::STATUS_DRAFT);

        try {
            $revision->save();
        } catch (StructException $e) {
            msg($e->getMessage(), -1);
        }
    }
}
