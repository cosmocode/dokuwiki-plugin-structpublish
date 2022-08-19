<?php

use dokuwiki\plugin\struct\meta\StructException;
use dokuwiki\plugin\structpublish\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component to create draft revisions
 */
class action_plugin_structpublish_revision extends DokuWiki_Action_Plugin
{
    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'saveDraft');
    }

    public function saveDraft(Doku_Event $event)
    {
        /** @var helper_plugin_structpublish_permissions $permissionsHelper */
        $permissionsHelper = plugin_load('helper', 'structpublish_permissions');

        // FIXME evaluate changeType
        $id = $event->data['id'];

        $assignments = Assignments::getInstance();
        $assignments->updatePageAssignments($id);

        if (!$permissionsHelper->isPublishable()) return;
        $revision = new Revision($permissionsHelper->getDb(), $id, $event->data['newRevision']);
        $revision->setStatus(Revision::STATUS_DRAFT);
        try {
            $revision->save();
        } catch (StructException $e) {
            msg($e->getMessage(), -1);
        }
    }
}
